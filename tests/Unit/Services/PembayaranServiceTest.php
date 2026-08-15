<?php

namespace Tests\Unit\Services;

use App\Models\Pembayaran;
use App\Models\PenghuniKamar;
use App\Models\User;
use App\Repositories\Contracts\NotifikasiRepositoryInterface;
use App\Repositories\Contracts\PembayaranRepositoryInterface;
use App\Services\PembayaranService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class PembayaranServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function verify_payment_sends_notification()
    {
        $pembayaranRepo = Mockery::mock(PembayaranRepositoryInterface::class);
        $notifikasiRepo = Mockery::mock(NotifikasiRepositoryInterface::class);

        // Buat mock PenghuniKamar dengan property langsung
        $penghuniKamar = Mockery::mock(PenghuniKamar::class)->makePartial();
        $penghuniKamar->penghuni_id = 5;
        $penghuniKamar->shouldAllowMockingProtectedMethods();

        // Buat Pembayaran real dengan relasi mock
        $pembayaran = new Pembayaran();
        $pembayaran->setRelation('penghuniKamar', $penghuniKamar);
        $pembayaran->jumlah = 1000000;

        $pembayaranRepo->shouldReceive('verify')->with(1, 2, null)->once()->andReturn(true);
        $pembayaranRepo->shouldReceive('find')->with(1)->once()->andReturn($pembayaran);
        $notifikasiRepo->shouldReceive('create')->once()->with(Mockery::on(function ($data) {
            return $data['judul'] === 'Pembayaran Terverifikasi' && $data['user_id'] === 5;
        }));

        $service = new PembayaranService($pembayaranRepo, $notifikasiRepo);
        $result = $service->verify(1, 2);

        $this->assertTrue($result);
    }

    #[Test]
    public function reject_payment_sends_notification_with_reason()
    {
        $pembayaranRepo = Mockery::mock(PembayaranRepositoryInterface::class);
        $notifikasiRepo = Mockery::mock(NotifikasiRepositoryInterface::class);

        $penghuniKamar = Mockery::mock(PenghuniKamar::class)->makePartial();
        $penghuniKamar->penghuni_id = 5;

        $pembayaran = new Pembayaran();
        $pembayaran->setRelation('penghuniKamar', $penghuniKamar);

        $pembayaranRepo->shouldReceive('reject')->with(1, 2, 'Bukti buram')->once()->andReturn(true);
        $pembayaranRepo->shouldReceive('find')->with(1)->once()->andReturn($pembayaran);
        $notifikasiRepo->shouldReceive('create')->once()->with(Mockery::on(function ($data) {
            return str_contains($data['pesan'], 'Bukti buram');
        }));

        $service = new PembayaranService($pembayaranRepo, $notifikasiRepo);
        $result = $service->reject(1, 2, 'Bukti buram');

        $this->assertTrue($result);
    }
}
