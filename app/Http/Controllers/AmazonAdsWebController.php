<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AmazonAdsWebController extends Controller
{
    /**
     * Tela Principal (Dashboard do Robô / Lista de SKUs)
     */
    public function index()
    {
        // View for the Ads Automator Dashboard where the user toggles SKUs and checks the Logs
        return view('amazon-ads.dashboard');
    }

    /**
     * Tela de Configurações Globais (LWA Credentials + Margem)
     */
    public function settings()
    {
        // View for LWA Credentials setup
        return view('amazon-ads.settings');
    }
}
