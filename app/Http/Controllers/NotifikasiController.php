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
        $notifikasis = $this->service->getByUser(Auth::id());
        return view('notifikasi.index', compact('notifikasis'));
    }

    public function markAsRead(int $id)
    {
        $this->service->markAsRead($id);
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
