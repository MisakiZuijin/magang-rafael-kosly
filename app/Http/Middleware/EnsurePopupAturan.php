<?php

namespace App\Http\Middleware;

use App\Services\AturanKosService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePopupAturan
{
    public function __construct(
        protected AturanKosService $aturanKosService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'penghuni') {
            $penghuniKamar = $user->penghuniKamar()->where('status', 'aktif')->first();

            if ($penghuniKamar) {
                $kosId = $penghuniKamar->kamar->kos_id;

                if ($this->aturanKosService->shouldShowPopup($user->id, $kosId)) {
                    session()->flash('show_aturan_popup', true);
                    session()->flash('kos_id_popup', $kosId);
                }
            }
        }

        return $next($request);
    }
}
