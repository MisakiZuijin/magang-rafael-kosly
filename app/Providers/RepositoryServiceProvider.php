<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $bindings = [
            \App\Repositories\Contracts\UserRepositoryInterface::class => \App\Repositories\UserRepository::class,
            \App\Repositories\Contracts\KosRepositoryInterface::class => \App\Repositories\KosRepository::class,
            \App\Repositories\Contracts\KamarRepositoryInterface::class => \App\Repositories\KamarRepository::class,
            \App\Repositories\Contracts\PenghuniKamarRepositoryInterface::class => \App\Repositories\PenghuniKamarRepository::class,
            \App\Repositories\Contracts\PembayaranRepositoryInterface::class => \App\Repositories\PembayaranRepository::class,
            \App\Repositories\Contracts\AturanKosRepositoryInterface::class => \App\Repositories\AturanKosRepository::class,
            \App\Repositories\Contracts\LogPopupAturanRepositoryInterface::class => \App\Repositories\LogPopupAturanRepository::class,
            \App\Repositories\Contracts\PengumumanRepositoryInterface::class => \App\Repositories\PengumumanRepository::class,
            \App\Repositories\Contracts\PengumumanTargetRepositoryInterface::class => \App\Repositories\PengumumanTargetRepository::class,
            \App\Repositories\Contracts\NotifikasiRepositoryInterface::class => \App\Repositories\NotifikasiRepository::class,
            \App\Repositories\Contracts\LogAktivitasRepositoryInterface::class => \App\Repositories\LogAktivitasRepository::class,
        ];

        foreach ($bindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        //
    }
}
