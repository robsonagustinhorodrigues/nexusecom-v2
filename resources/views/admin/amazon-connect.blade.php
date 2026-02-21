@extends('layouts.alpine')

@section('title', 'Conectar Amazon - NexusEcom')

@section('content')
<div class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-3xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fab fa-amazon text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-black text-white italic uppercase">Conectar Amazon SP-API</h1>
            <p class="text-slate-400 mt-2">Preencha suas credenciais do Amazon Seller Central</p>
        </div>

        <form action="/integrations/amazon/connect" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Client ID</label>
                <input type="text" name="client_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Client Secret</label>
                <input type="password" name="client_secret" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Refresh Token</label>
                <input type="text" name="refresh_token" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Seller ID (opcional)</label>
                <input type="text" name="seller_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Marketplace ID (opcional)</label>
                <input type="text" name="marketplace_id" placeholder="ATVPDKIKX0DER" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nome da Conta</label>
                <input type="text" name="nome_conta" placeholder="Minha Loja Amazon" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-white focus:border-orange-500 outline-none">
            </div>

            <div class="flex gap-3 pt-4">
                <a href="/integrations" class="flex-1 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-bold text-center transition-all">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 py-3 bg-orange-500 hover:bg-orange-400 text-white rounded-xl font-bold transition-all">
                    Conectar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
