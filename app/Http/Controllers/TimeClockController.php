<?php

namespace App\Http\Controllers;

class TimeClockController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard');
    }
}
