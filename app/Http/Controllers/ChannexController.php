<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

class ChannexController extends Controller
{
    public function sync()
    {
        Artisan::call('sync:channex-properties');

        return back()->with('updated', 'Đồng bộ dữ liệu đã được thực hiện.');
    }
}
