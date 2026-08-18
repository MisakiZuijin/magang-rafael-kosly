<?php

namespace App\Http\Controllers;

use App\Services\NotifikasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    public function __construct(
        protected NotifikasiService $service
    ) {}

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $notifikasis = $this->service->getByUser($user->id);

        $userKosId = null;
        $userKamarId = null;

        if ($user->role === 'penghuni') {
            $activePk = $user->penghuniKamar()->with('kamar')->where('status', 'aktif')->first();
            if ($activePk) {
                $userKamarId = $activePk->kamar_id;
                $userKosId = $activePk->kamar->kos_id ?? null;
            }
        }

        // Fetch announcements matching user scope
        $pengumumanQuery = \App\Models\Pengumuman::with('targets');
        if ($user->role === 'penghuni') {
            $pengumumanQuery->where(function($q) use ($userKosId, $userKamarId) {
                $q->whereDoesntHave('targets')
                  ->orWhereHas('targets', function($tq) use ($userKosId, $userKamarId) {
                      if ($userKosId) {
                          $tq->orWhere(function($sub) use ($userKosId) {
                              $sub->where('target_tipe', 'kos')->where('target_id', $userKosId);
                          });
                      }
                      if ($userKamarId) {
                          $tq->orWhere(function($sub) use ($userKamarId) {
                              $sub->where('target_tipe', 'kamar')->where('target_id', $userKamarId);
                          });
                      }
                  });
            });
        }
        $pengumumans = $pengumumanQuery->latest()->get();

        foreach ($pengumumans as $p) {
            $alreadyExists = $notifikasis->contains(function($n) use ($p) {
                return str_contains($n->judul, $p->judul) && abs(($n->created_at?->timestamp ?? 0) - ($p->created_at?->timestamp ?? 0)) < 300;
            });

            if (!$alreadyExists) {
                $notifItem = new \App\Models\Notifikasi([
                    'user_id' => $user->id,
                    'judul' => '[Pengumuman] ' . $p->judul,
                    'pesan' => $p->isi,
                    'channel' => 'web',
                    'status' => 'terkirim',
                ]);
                $notifItem->id = 0;
                $notifItem->created_at = $p->created_at;
                $notifikasis->push($notifItem);
            }
        }

        $notifikasis = $notifikasis->sortByDesc('created_at')->values();

        return view('notifikasi.index', compact('notifikasis'));
    }

    public function markAsRead(int $id)
    {
        if ($id > 0) {
            $this->service->markAsRead($id);
        }
        return redirect()->back();
    }

    public function markAllAsRead()
    {
        $this->service->markAllAsRead(Auth::id());
        return redirect()->back()->with('success', 'Semua notifikasi telah dibaca.');
    }

    public function getUnreadCount()
    {
        $count = $this->service->getUnread(Auth::id())->count();
        return response()->json(['unread_count' => $count]);
    }
}
