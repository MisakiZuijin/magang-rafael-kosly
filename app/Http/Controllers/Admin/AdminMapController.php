<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\KosService;

class AdminMapController extends Controller
{
    public function __construct(
        protected KosService $kosService
    ) {}

    public function index()
    {
        $locations = $this->kosService->getAllLocations();
        return view('admin.map.index', compact('locations'));
    }
}
