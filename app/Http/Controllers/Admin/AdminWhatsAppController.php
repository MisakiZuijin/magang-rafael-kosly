<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\LogAktivitasService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class AdminWhatsAppController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService,
        protected LogAktivitasService $logAktivitasService
    ) {}

    public function index()
    {
        $apiKey = $this->whatsAppService->getApiKey();
        $endpoint = $this->whatsAppService->getEndpoint();
        $deviceInfo = $this->whatsAppService->checkDeviceStatus();

        $view = request()->is('superadmin*') ? 'superadmin.whatsapp.index' : 'admin.whatsapp.index';
        return view($view, compact('apiKey', 'endpoint', 'deviceInfo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fonnte_api_key' => 'nullable|string|max:255',
            'fonnte_endpoint' => 'nullable|url|max:255',
        ]);

        Setting::setKey('fonnte_api_key', trim($validated['fonnte_api_key'] ?? ''));
        Setting::setKey('fonnte_endpoint', trim($validated['fonnte_endpoint'] ?? 'https://api.fonnte.com/send'));

        $this->logAktivitasService->log('konfigurasi_wa', "Memperbarui API Token & Endpoint Fonnte WhatsApp Gateway");

        $prefix = request()->is('superadmin*') ? 'superadmin.' : 'admin.';
        return redirect()->route($prefix . 'whatsapp.index')->with('success', 'Pengaturan Fonnte WhatsApp Gateway berhasil diperbarui.');
    }

    public function testSend(Request $request)
    {
        $validated = $request->validate([
            'target' => 'required|string|max:100',
            'judul' => 'required|string|max:150',
            'pesan' => 'required|string',
        ]);

        $result = $this->whatsAppService->sendDirect($validated['target'], $validated['judul'], $validated['pesan']);

        $this->logAktivitasService->log('tes_kirim_wa', "Melakukan tes pengiriman WhatsApp ke target: {$validated['target']}");

        $prefix = request()->is('superadmin*') ? 'superadmin.' : 'admin.';
        if ($result['success']) {
            return redirect()->route($prefix . 'whatsapp.index')->with('success', $result['message']);
        } else {
            return redirect()->route($prefix . 'whatsapp.index')->with('error', $result['message']);
        }
    }
}
