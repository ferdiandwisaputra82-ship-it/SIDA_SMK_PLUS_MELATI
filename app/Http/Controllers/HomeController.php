<?php

namespace App\Http\Controllers;

use App\Models\Document;

class HomeController extends Controller
{
    public function index()
    {
        return view('landing');
    }
}

