<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth;

class WebController extends Controller
{
    public function index()
    {
        return view('index');
    }
}
