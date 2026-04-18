<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class InertiaController extends Controller
{
    public function welcome()
    {
        return Inertia::render('Welcome', [
            'nfesCount' => 0,
            'adsCount' => 0,
        ]);
    }
}
