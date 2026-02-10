<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $jumlahKelas = 0;
        $jumlahMusyrif = 0;
        $setoranBulanIni = 0;

        return view('admin.dashboard', compact(
            'jumlahKelas',
            'jumlahMusyrif',
            'setoranBulanIni'
        ));
    }
}
