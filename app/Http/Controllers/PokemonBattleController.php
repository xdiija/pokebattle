<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class PokemonBattleController extends Controller
{
    public function index(Request $request)
    {
        return ['1 2 3 testando'];
    }
}
