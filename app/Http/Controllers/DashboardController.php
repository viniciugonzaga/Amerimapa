<?php

namespace App\Http\Controllers;

use App\Models\Jogador;
use App\Models\Partida;
use App\Models\RegiaoMapa;

class DashboardController extends Controller
{
    public function index()
    {
        $totalJogadores = Jogador::count();
        $totalPartidas = Partida::count();
        $totalRegioes = RegiaoMapa::count();

        return view('dashboard', compact('totalJogadores', 'totalPartidas', 'totalRegioes'));
    }
}