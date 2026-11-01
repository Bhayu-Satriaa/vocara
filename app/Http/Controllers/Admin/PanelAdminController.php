<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/** Halaman utama wilayah admin (`/admin-klinik/panel-admin`). */
class PanelAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.panel-admin');
    }
}
