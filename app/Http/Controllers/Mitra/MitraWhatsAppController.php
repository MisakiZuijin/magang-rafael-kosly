<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LogAktivitasService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MitraWhatsAppController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $deviceStatus = $this->whatsAppService->checkDeviceStatus($user->wa_gateway_token, false);

        return view('mitra.whatsapp.index', [
            'token' => $user->wa_gateway_token,
            'endpoint' => $user->wa_gateway_endpoint ?? 'https://api.fonnte.com/send',
            'deviceStatus' => $deviceStatus,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'wa_gateway_token' => 'nullable|string|max:255',
            'wa_gateway_endpoint' => 'nullable|url|max:255',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'wa_gateway_token' => $validated['wa_gateway_token'] ?? null,
            'wa_gateway_endpoint' => $validated['wa_gateway_endpoint'] ?? 'https://api.fonnte.com/send',
        ]);

        $this->logAktivitasService->log('update_wa_gateway_mitra_pro', "Mitra Pro {$user->nama} memperbarui konfigurasi WhatsApp Gateway pribadi.");

        return redirect()->back()->with('success', 'Konfigurasi WhatsApp Gateway berhasil disimpan.');
    }

    public function testSend(Request $request)
    {
        $validated = $request->validate([
            'target' => 'required|string',
            'pesan' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (empty($user->wa_gateway_token)) {
            return redirect()->back()->with('error', 'Token WhatsApp Gateway belum diatur. Silakan simpan token terlebih dahulu.');
        }

        $result = $this->whatsAppService->sendDirect(
            $validated['target'],
            'TES GATEWAY WHATSAPP MITRA PRO',
            $validated['pesan'],
            $user->wa_gateway_token,
            $user->wa_gateway_endpoint
        );

        if ($result['success']) {
            return redirect()->back()->with('success', 'Pesan uji coba berhasil dikirim via gateway pribadi Anda!');
        } else {
            return redirect()->back()->with('error', 'Gagal mengirim pesan uji coba: ' . ($result['message'] ?? 'Unknown error'));
        }
    }
}
