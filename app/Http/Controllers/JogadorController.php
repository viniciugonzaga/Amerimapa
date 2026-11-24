<?php

namespace App\Http\Controllers;

use App\Models\Jogador;

class JogadorController extends Controller
{
    public function index()
    {
        $jogadores = Jogador::all();
        return view('jogadores.index', compact('jogadores'));
    }
}