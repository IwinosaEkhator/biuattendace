<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Campus $campus)
    {
        // automatically resolved via route model binding
        return response()->json([
            'campus'   => $campus->name,
            'services' => $campus->services()->where('active', true)->get(),
        ]);
    }
}
