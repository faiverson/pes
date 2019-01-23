<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index(Request $request, $schema = null)
    {
        return false;
    }

    public function create()
    {
        return view('games.create');
    }
}
