<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ServiceAccessController extends Controller
{
    public function pending(): View
    {
        return view('auth.access-pending');
    }
}
