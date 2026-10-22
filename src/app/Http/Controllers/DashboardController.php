<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Área privada del organizador (HU-02, CA-02.5 y CA-02.6).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard', ['user' => $request->user()]);
    }
}
