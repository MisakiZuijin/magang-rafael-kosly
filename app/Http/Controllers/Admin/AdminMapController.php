<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kantor;
use App\Services\KosService;

class AdminMapController extends Controller
{
    public function __construct(
        protected KosService $kosService
    ) {}

    public function index()
    {
        $locations = $this->kosService->getAllLocations();
        $kantors = Kantor::where('is_active', true)->get();

        $view = request()->is('superadmin*') ? 'superadmin.map.index' : 'admin.map.index';
        return view($view, compact('locations', 'kantors'));
    }
}
