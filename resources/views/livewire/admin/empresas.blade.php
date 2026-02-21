<div class="space-y-6">
    <!-- Header com Informações Principais -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fas fa-building text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Hub de Empresas</h2>
                <p class="text-sm text-slate-500">Gerencie suas empresas e configurações</p>
            </div>
        </div>

        @if(!$isEditing && !$isCreating)
        <button wire:click="create" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
            <i class="fas fa-plus text-xs"></i>
            Nova Empresa
        </button>
        @endif
    </div>

    <!-- Alertas -->
    @if (session()->has('message'))
        <div class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 px-4 py-3 rounded-xl font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('message') }}
        </div>
    @endif

    <!-- Formulário de Criação/Edição -->
    @if($isEditing || $isCreating)
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <!-- Abas -->
        <div class="flex border-b border-slate-200 dark:border-dark-800 bg-slate-50 dark:bg-dark-950">
            <button 
                wire:click="$set('activeTab', 'basic')" 
                class="px-6 py-3 text-sm font-semibold transition-all border-b-2 {{ $activeTab == 'basic' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-white' }}"
            >
                <i class="fas fa-building mr-2"></i>
                Dados Básicos
            </button>
            <button 
                wire:click="$set('activeTab', 'fiscal')" 
                class="px-6 py-3 text-sm font-semibold transition-all border-b-2 {{ $activeTab == 'fiscal' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-white' }}"
            >
                <i class="fas fa-file-invoice-dollar mr-2"></i>
                Fiscal
            </button>
            <button 
                wire:click="$set('activeTab', 'danfe')" 
                class="px-6 py-3 text-sm font-semibold transition-all border-b-2 {{ $activeTab == 'danfe' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-white' }}"
            >
                <i class="fas fa-print mr-2"></i>
                DANFE
            </button>
        </div>

        <form wire:submit.prevent="save" class="p-6">
            <!-- Tab: Dados Básicos -->
            @if($activeTab == 'basic')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Razão Social</label>
                    <input type="text" wire:model="razao_social" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="Razão social oficial...">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nome Fantasia</label>
                    <input type="text" wire:model="nome" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="Nome de exibição...">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Apelido (Interno)</label>
                    <input type="text" wire:model="apelido" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="Ex: Matriz, Filial 1...">
                </div>
            </div>
            @endif

            <!-- Tab: Fiscal -->
            @if($activeTab == 'fiscal')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 space-y-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">CNPJ / CPF</label>
                        <input 
                            type="text" 
                            wire:model="cnpj" 
                            x-data 
                            x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2')"
                            maxlength="18"
                            class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-mono focus:border-indigo-500" 
                            placeholder="00.000.000/0000-00"
                        >
                        @error('cnpj') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tipo de Atividade</label>
                        <select wire:model="tipo_atividade" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500">
                            <option value="anexo_i">Anexo I - Comércio</option>
                            <option value="anexo_ii">Anexo II - Indústria</option>
                            <option value="anexo_iii">Anexo III - Serviços</option>
                            <option value="anexo_iv">Anexo IV - Serviços (Obs: não optante)</option>
                            <option value="anexo_v">Anexo V - Serviços Intelectuais</option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Define a tabela do Simples Nacional a ser utilizada</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">E-mail Contabilidade</label>
                        <input type="email" wire:model="email_contabil" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="contabil@empresa.com.br">
                    </div>
                    
                    <!-- Certificado Digital -->
                    <div class="p-5 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                <i class="fas fa-certificate text-indigo-500"></i>
                                Certificado Digital A1
                            </h4>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="auto_ciencia" class="rounded bg-dark-800 border-dark-700 text-indigo-600">
                                <span class="text-xs font-semibold text-slate-500">Auto-Ciência</span>
                            </label>
                        </div>

                        @php
                            $hasCert = false;
                            if ($empresaId) {
                                $empresaDb = \App\Models\Empresa::find($empresaId);
                                $hasCert = $empresaDb && $empresaDb->certificado_a1_path;
                            }
                        @endphp

                        @if($hasCert && !$showUpload && !$certificado)
                            <div class="flex items-center justify-between bg-emerald-500/10 border border-emerald-500/20 p-4 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                                    <div>
                                        <p class="text-xs font-bold text-emerald-400">Certificado Configurado</p>
                                        <p class="text-[10px] text-slate-500">Armazenado com segurança</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('showUpload', true)" class="text-xs font-semibold text-indigo-500 hover:text-indigo-400">
                                    Trocar
                                </button>
                            </div>
                        @else
                            <div class="space-y-3">
                                <input type="file" wire:model="certificado" class="w-full text-sm text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:text-white file:font-semibold cursor-pointer">
                                @if($hasCert && ($showUpload || $certificado))
                                    <button type="button" wire:click="$set('showUpload', false); $set('certificado', null)" class="text-xs font-semibold text-rose-500 hover:text-rose-400">
                                        Cancelar
                                    </button>
                                @endif
                            </div>
                        @endif

                        <div class="mt-4 flex gap-3">
                            <div class="flex-1 relative">
                                <input type="{{ $showPassword ? 'text' : 'password' }}" wire:model="certificado_senha" placeholder="Senha do certificado" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-lg px-3 py-2 text-sm">
                                <button type="button" wire:click="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                                    <i class="fas {{ $showPassword ? 'fa-eye-slash' : 'fa-eye' }} text-xs"></i>
                                </button>
                            </div>
                            <button type="button" wire:click="testarCertificado" class="px-4 py-2 bg-dark-800 hover:bg-dark-700 text-indigo-400 text-xs font-semibold rounded-lg border border-indigo-500/20">
                                <i class="fas fa-vial mr-1"></i> Testar
                            </button>
                        </div>

                        @if($certValidationResult)
                        <div class="mt-4 p-3 rounded-lg {{ $certValidationResult['status'] === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20' : 'bg-rose-500/10 border border-rose-500/20' }}">
                            <p class="text-xs font-semibold {{ $certValidationResult['status'] === 'success' ? 'text-emerald-400' : 'text-rose-400' }}">
                                <i class="fas {{ $certValidationResult['status'] === 'success' ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $certValidationResult['message'] }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Logo -->
                <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-200 dark:border-dark-700 rounded-xl bg-slate-50 dark:bg-dark-950">
                    <label class="relative w-28 h-28 rounded-2xl bg-white dark:bg-dark-800 border border-slate-200 dark:border-dark-700 flex items-center justify-center mb-3 overflow-hidden shadow-inner cursor-pointer hover:border-indigo-500 transition-colors">
                        @if($logo)
                            <img src="{{ $logo->temporaryUrl() }}" class="w-full h-full object-cover">
                        @elseif($empresaId && isset($empresas) && $empresas->find($empresaId)?->logo_path)
                            <img src="{{ Storage::url($empresas->find($empresaId)->logo_path) }}" class="w-full h-full object-cover">
                        @else 
                            <i class="fas fa-image text-3xl text-slate-400"></i> 
                        @endif
                        <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/jpg,image/webp" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    </label>
                    <p class="text-xs font-semibold text-slate-500">Logo da Empresa</p>
                    <p class="text-[10px] text-slate-400 mt-1">Tamanho: 200x200px (mín.)</p>
                    <p class="text-[10px] text-slate-400">Extensões: PNG, JPG, JPEG, WEBP</p>
                </div>
            </div>
            @endif

            <!-- Configurações SEFAZ -->
            <div class="mt-6 p-5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <h4 class="text-sm font-bold text-blue-700 dark:text-blue-400 flex items-center gap-2 mb-4">
                    <i class="fas fa-server"></i>
                    Configurações SEFAZ (NF-e Recebidas)
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Intervalo de Consulta</label>
                        <select wire:model="sefaz_intervalo_horas" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-blue-500">
                            <option value="1">1 hora</option>
                            <option value="2">2 horas</option>
                            <option value="4">4 horas</option>
                            <option value="6">6 horas</option>
                            <option value="12">12 horas</option>
                            <option value="24">24 horas</option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Tempo entre consultas automáticas</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Ambiente</label>
                        <select wire:model="tpAmb" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-blue-500">
                            <option value="1">Produção</option>
                            <option value="2">Homologação</option>
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Ambiente da SEFAZ</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Status</label>
                        <div class="flex items-center gap-3 mt-3">
                            <button type="button" wire:click="$toggle('sefaz_ativo')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $sefaz_ativo ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-600' }}">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $sefaz_ativo ? 'translate-x-5' : '' }}"></div>
                            </button>
                            <span class="text-sm font-medium {{ $sefaz_ativo ? 'text-emerald-600' : 'text-slate-500' }}">
                                {{ $sefaz_ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Ativar consultas automáticas</p>
                    </div>
                </div>
            </div>

            <!-- Configurações Tributárias -->
            <div class="mt-6 p-5 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl">
                <h4 class="text-sm font-bold text-purple-700 dark:text-purple-400 flex items-center gap-2 mb-4">
                    <i class="fas fa-calculator"></i>
                    Configurações Tributárias
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Regime Tributário</label>
                        <select wire:model="regime_tributario" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500">
                            <option value="simples_nacional">Simples Nacional</option>
                            <option value="lucro_presumido">Lucro Presumido</option>
                            <option value="lucro_real">Lucro Real</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">% ICMS</label>
                        <input wire:model="aliquota_icms" type="number" step="0.01" min="0" max="100" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">% PIS</label>
                        <input wire:model="aliquota_pis" type="number" step="0.01" min="0" max="100" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">% COFINS</label>
                        <input wire:model="aliquota_cofins" type="number" step="0.01" min="0" max="100" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">% CSLL</label>
                        <input wire:model="aliquota_csll" type="number" step="0.01" min="0" max="100" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">% IRPJ</label>
                        <input wire:model="aliquota_irpj" type="number" step="0.01" min="0" max="100" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">% ISS</label>
                        <input wire:model="aliquota_iss" type="number" step="0.01" min="0" max="100" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">% Lucro Presumido</label>
                        <input wire:model="percentual_lucro_presumido" type="number" step="0.01" min="0" max="100" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500" placeholder="32">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">% Simples Nacional</label>
                        <input wire:model="aliquota_simples" type="number" step="0.01" min="0" max="100" class="w-full bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-purple-500" placeholder="Deixe vazio para automático">
                        <p class="text-[10px] text-slate-400 mt-1">Alíquota fixa. Se vazio, usa o cálculo automático.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="$toggle('calcula_imposto_auto')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $calcula_imposto_auto ? 'bg-purple-600' : 'bg-slate-300 dark:bg-slate-600' }}">
                        <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $calcula_imposto_auto ? 'translate-x-5' : '' }}"></div>
                    </button>
                    <span class="text-sm font-medium {{ $calcula_imposto_auto ? 'text-purple-600' : 'text-slate-500' }}">
                        {{ $calcula_imposto_auto ? 'Calcular imposto automaticamente nos pedidos' : 'Não calcular imposto automaticamente' }}
                    </span>
                </div>
            </div>

            <!-- Tab: DANFE -->
            @if($activeTab == 'danfe')
            <div class="space-y-6">
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                    <div>
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Habilitar DANFE Simplificada</span>
                        <p class="text-xs text-slate-500">Ativar geração de DANFE simplificada para esta empresa</p>
                    </div>
                    <button type="button" wire:click="$toggle('danfe_enabled')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $danfe_enabled ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                        <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $danfe_enabled ? 'translate-x-5' : '' }}"></div>
                    </button>
                </div>

                @if($danfe_enabled)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                        <div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Mostrar Logo</span>
                            <p class="text-xs text-slate-500">Exibir logo da empresa no DANFE</p>
                        </div>
                        <button type="button" wire:click="$toggle('danfe_show_logo')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $danfe_show_logo ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $danfe_show_logo ? 'translate-x-5' : '' }}"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                        <div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Mostrar Itens</span>
                            <p class="text-xs text-slate-500">Exibir lista de itens na nota</p>
                        </div>
                        <button type="button" wire:click="$toggle('danfe_show_itens')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $danfe_show_itens ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $danfe_show_itens ? 'translate-x-5' : '' }}"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                        <div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Valor dos Itens</span>
                            <p class="text-xs text-slate-500">Exibir valor unitário e total dos itens</p>
                        </div>
                        <button type="button" wire:click="$toggle('danfe_show_valor_itens')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $danfe_show_valor_itens ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $danfe_show_valor_itens ? 'translate-x-5' : '' }}"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                        <div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Valor Total</span>
                            <p class="text-xs text-slate-500">Exibir totais da nota fiscal</p>
                        </div>
                        <button type="button" wire:click="$toggle('danfe_show_valor_total')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $danfe_show_valor_total ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $danfe_show_valor_total ? 'translate-x-5' : '' }}"></div>
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl">
                        <div>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">QR Code</span>
                            <p class="text-xs text-slate-500">Exibir QR Code da NFC-e</p>
                        </div>
                        <button type="button" wire:click="$toggle('danfe_show_qrcode')" class="w-11 h-6 rounded-full relative transition-all duration-300 {{ $danfe_show_qrcode ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-dark-700' }}">
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white transition-all transform {{ $danfe_show_qrcode ? 'translate-x-5' : '' }}"></div>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Rodapé Personalizado</label>
                    <textarea 
                        wire:model="danfe_rodape" 
                        rows="2" 
                        class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium"
                        placeholder="Texto que aparecerá no rodapé do DANFE..."
                    ></textarea>
                </div>
                @endif
            </div>
            @endif

            <!-- Ações do Formulário -->
            <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-slate-200 dark:border-dark-700">
                <button type="button" wire:click="$set('isCreating', false); $set('isEditing', false)" class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-dark-800 text-slate-600 dark:text-slate-400 font-semibold text-sm hover:bg-slate-300 dark:hover:bg-dark-700 transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
                    <i class="fas fa-save text-xs"></i>
                    {{ $isCreating ? 'Cadastrar' : 'Salvar' }}
                </button>
            </div>
        </form>
    </div>
    @else
    <!-- Lista de Empresas em Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($empresas as $empresa)
        <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-xl p-5 hover:border-indigo-500/30 transition-all">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-dark-800 flex items-center justify-center text-indigo-500 font-bold text-lg overflow-hidden">
                    @if($empresa->logo_path) 
                        <img src="{{ Storage::url($empresa->logo_path) }}" class="w-full h-full object-cover"> 
                    @else 
                        {{ substr($empresa->nome, 0, 1) }} 
                    @endif
                </div>
                <div class="flex gap-1">
                    <button wire:click="edit({{ $empresa->id }})" class="p-2 rounded-lg text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 transition-all" title="Editar">
                        <i class="fas fa-pen text-xs"></i>
                    </button>
                    <button onclick="confirm('Excluir empresa?') || event.stopImmediatePropagation()" wire:click="delete({{ $empresa->id }})" class="p-2 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-all" title="Excluir">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </div>
            
            <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1 truncate">{{ $empresa->nome }}</h3>
            <p class="text-xs text-slate-500 mb-4">{{ $empresa->apelido ?? 'Sem apelido' }}</p>
            
            <div class="space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 font-medium">CNPJ</span>
                    <span class="font-mono text-slate-700 dark:text-slate-300">{{ $empresa->cnpj ?? '-' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500 font-medium">Certificado</span>
                    @if($empresa->certificado_a1_path)
                        <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded font-semibold">Ativo</span>
                    @else
                        <span class="px-2 py-0.5 bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded font-semibold">Pendente</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-dark-800 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-building text-2xl text-slate-400"></i>
            </div>
            <p class="text-slate-500 font-medium">Nenhuma empresa cadastrada</p>
            <button wire:click="create" class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-lg">
                <i class="fas fa-plus mr-2"></i>Cadastrar Primeira Empresa
            </button>
        </div>
        @endforelse
    </div>
    @endif
</div>
