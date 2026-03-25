@extends('layouts.alpine')

@section('title', 'Amazon Ads: Configurações - NexusEcom')
@section('header_title', 'Amazon Ads - Configurações Gerais')

@section('content')
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h2 class="font-bold text-2xl text-white flex items-center gap-3">
            <div class="p-2 bg-indigo-500/20 rounded-xl">
                <i class="fab fa-amazon text-indigo-400"></i>
            </div>
            Configurações Gerais
        </h2>
    </div>

    <div class="py-2" x-data="amazonAdsSettings()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-slate-800/80 backdrop-blur-md rounded-2xl shadow-xl border border-slate-700/50 overflow-hidden relative">
                
                <!-- Loading Overlay -->
                <div x-show="loading" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center transition-all">
                    <div class="bg-slate-800 p-4 rounded-xl shadow-2xl flex items-center gap-3 border border-slate-700/50">
                        <i class="fas fa-circle-notch fa-spin text-indigo-500 text-xl"></i>
                        <span class="text-white font-medium">Carregando configurações...</span>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <form @submit.prevent="saveConfig" class="space-y-6">
                        
                        <div class="flex items-center justify-between bg-slate-900/50 p-4 rounded-xl border border-slate-700/50">
                            <div>
                                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                    <i class="fas fa-power-off text-indigo-400"></i>
                                    Ativar Módulo Ads
                                </h3>
                                <p class="text-slate-400 text-sm mt-1">Habilita os robôs de automação e métricas para a sua conta.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="config.is_active" class="sr-only peer">
                                <div class="w-14 h-7 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-500"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- API Credentials (LWA) -->
                            <div class="space-y-4 bg-slate-900/30 p-5 rounded-2xl border border-slate-700/50">
                                <h3 class="text-md font-bold text-white mb-4 border-b border-slate-700/50 pb-2">Credenciais LWA (Login with Amazon)</h3>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1">Profile ID</label>
                                    <input type="text" x-model="config.profile_id" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none" placeholder="Ex: 1234567890">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1">Client ID</label>
                                    <input type="text" x-model="config.client_id" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none" placeholder="amzn1.application-oa2-client.xx">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1">Client Secret</label>
                                    <input type="password" x-model="config.client_secret" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none" placeholder="••••••••••••••">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1">Refresh Token</label>
                                    <input type="text" x-model="config.refresh_token" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none" placeholder="AtZa|IwEBI...">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1">Região</label>
                                    <select x-model="config.region" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none cursor-pointer">
                                        <option value="na">North America (NA) - Inclui Brasil</option>
                                        <option value="eu">Europe (EU)</option>
                                        <option value="sa">South America (SA)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Regras Engine -->
                            <div class="space-y-4 bg-slate-900/30 p-5 rounded-2xl border border-slate-700/50">
                                <h3 class="text-md font-bold text-white mb-4 border-b border-slate-700/50 pb-2">Regras de Automação</h3>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-1">Margem Alvo Padrão (%)</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" x-model="config.margem_alvo_padrao" class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-4 pr-10 py-2 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-2">Esta é a margem de lucro projetada padrão. O ACOS não deve ultrapassar essa métrica. Os robôs vão pausar campanhas automaticamente se o ACOS for superior a este valor.</p>
                                </div>

                                <div class="border-t border-slate-700/50 pt-4 mt-4">
                                    <h3 class="text-md font-bold text-white mb-4 flex items-center gap-2">
                                        <i class="fas fa-robot text-indigo-400"></i>
                                        Configurações de IA
                                    </h3>
                                    
                                    <label class="block text-sm font-medium text-slate-300 mb-1">Modelo Gemini (Google AI Studio)</label>
                                    <select x-model="config.gemini_model" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 text-white focus:ring-2 focus:ring-indigo-500/50 outline-none cursor-pointer">
                                        <option value="gemini-flash-latest">Gemini Flash Latest (Recomendado)</option>
                                        <option value="gemini-3.1-flash-lite-preview">Gemini 3.1 Flash Lite (Ultra Rápido)</option>
                                        <option value="gemini-2.5-flash-lite">Gemini 2.5 Flash Lite (Estável)</option>
                                    </select>
                                    <p class="text-xs text-slate-500 mt-2">Escolha o modelo que será usado para gerar sugestões de palavras-chave e ASINs no dashboard.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end pt-4 border-t border-slate-700/50">
                            <button type="submit" 
                                class="bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl px-6 py-2.5 font-bold flex items-center gap-2 transition-all shadow-lg active:scale-95"
                                :disabled="saving">
                                <i class="fas fa-save" :class="{'fa-spinner fa-spin': saving}"></i>
                                <span x-text="saving ? 'Salvando...' : 'Salvar Configurações'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('amazonAdsSettings', () => ({
                empresaId: {{ auth()->user()->current_empresa_id ?? 0 }},
                loading: true,
                saving: false,
                config: {
                    is_active: false,
                    profile_id: '',
                    client_id: '',
                    client_secret: '',
                    refresh_token: '',
                    region: 'na',
                    margem_alvo_padrao: 20.00,
                    gemini_model: 'gemini-flash-latest'
                },

                init() {
                    this.loadConfig();
                },

                loadConfig() {
                    this.loading = true;
                    fetch(`/api/amazon-ads/config?empresa_id=${this.empresaId}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data) {
                                this.config = data;
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao carregar configurações', error);
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                saveConfig() {
                    this.saving = true;
                    const payload = { ...this.config, empresa_id: this.empresaId };
                    
                    fetch(`/api/amazon-ads/config`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(r => {
                            if (!r.ok) throw new Error('Network error');
                            return r.json();
                        })
                        .then(data => {
                            alert('Configurações salvas com sucesso!');
                        })
                        .catch(error => {
                            console.error('Erro ao salvar', error);
                            alert('Erro ao salvar as configurações.');
                        })
                        .finally(() => {
                            this.saving = false;
                        });
                }
            }));
        });
    </script>
    @endsection
