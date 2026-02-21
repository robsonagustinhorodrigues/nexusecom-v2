<?php

use function Livewire\Volt\{state, computed, rules};
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

state([
    'isCreating' => false,
    'isEditing' => false,
    'name' => '',
    'email' => '',
    'password' => '',
    'role_id' => '',
    'selected_empresas' => [],
]);

$users = computed(function () {
    return User::with(['roles', 'empresas'])->get();
});

$roles = computed(function () {
    return Role::all();
});

$empresas = computed(function () {
    return \App\Models\Empresa::all();
});

$create = function () {
    $this->reset(['name', 'email', 'password', 'role_id', 'selected_empresas']);
    $this->isCreating = true;
    $this->isEditing = false;
};

$edit = function ($id) {
    $user = User::with(['roles', 'empresas'])->findOrFail($id);
    $this->name = $user->name;
    $this->email = $user->email;
    $this->password = '';
    $this->role_id = $user->roles->first()?->id;
    $this->selected_empresas = $user->empresas->pluck('id')->toArray();
    $this->isEditing = true;
    $this->isCreating = false;
};

$save = function () {
    $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email,' . ($this->isEditing ? User::where('email', $this->email)->first()?->id : 'NULL'),
        'role_id' => 'required',
        'selected_empresas' => 'required|array|min:1',
    ];
    
    if (!$this->isEditing || $this->password) {
        $rules['password'] = 'required|string|min:8';
    }
    
    $this->validate($rules);

    if ($this->isEditing) {
        $user = User::findOrFail(request('id') ?: 0);
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);
        if ($this->password) {
            $user->update(['password' => bcrypt($this->password)]);
        }
    } else {
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);
    }

    if ($this->role_id) {
        $role = Role::findById($this->role_id);
        $user->syncRoles([$role]);
    }

    $user->empresas()->sync($this->selected_empresas);

    session()->flash('message', 'Usuário salvo com sucesso! ⚡');
    $this->isCreating = false;
    $this->isEditing = false;
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fas fa-users text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Gestão de Equipe</h2>
                <p class="text-sm text-slate-500">Gerencie acessos, grupos e empresas da sua equipe</p>
            </div>
        </div>

        @if(!$isEditing && !$isCreating)
        <button wire:click="create" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
            <i class="fas fa-user-plus text-xs"></i>
            Adicionar Membro
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

    <!-- Formulário -->
    @if($isEditing || $isCreating)
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-200 dark:border-dark-800">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fas {{ $isEditing ? 'fa-user-edit' : 'fa-user-plus' }} text-indigo-500"></i>
                {{ $isEditing ? 'Editar Membro' : 'Novo Membro' }}
            </h3>
        </div>

        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Dados Pessoais -->
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-id-card text-slate-400"></i>
                        Dados de Acesso
                    </h4>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nome Completo</label>
                        <input type="text" wire:model="name" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="Nome completo">
                        @error('name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">E-mail de Login</label>
                        <input type="email" wire:model="email" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="email@exemplo.com">
                        @error('email') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ $isEditing ? 'Nova Senha (Opcional)' : 'Senha de Acesso' }}</label>
                        <input type="password" wire:model="password" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500" placeholder="••••••••">
                    </div>
                </div>

                <!-- Permissões -->
                <div class="space-y-4">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-shield-alt text-slate-400"></i>
                        Permissões e Vínculos
                    </h4>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Grupo de Acesso</label>
                        <select wire:model="role_id" class="w-full bg-slate-50 dark:bg-dark-950 border border-slate-200 dark:border-dark-700 rounded-xl px-4 py-3 text-sm font-medium focus:border-indigo-500 h-[50px]">
                            <option value="">Selecione um grupo...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Empresas que pode Acessar</label>
                        <div class="grid grid-cols-1 gap-2 bg-slate-50 dark:bg-dark-950 p-4 rounded-xl border border-slate-200 dark:border-dark-700 max-h-48 overflow-y-auto">
                            @forelse($empresas as $empresa)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" wire:model="selected_empresas" value="{{ $empresa->id }}" class="rounded bg-dark-800 border-dark-700 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-indigo-400 transition-colors">{{ $empresa->nome }}</span>
                            </label>
                            @empty
                            <p class="text-sm text-slate-500 italic">Nenhuma empresa disponível</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-slate-200 dark:border-dark-800">
                <button type="button" wire:click="$set('isCreating', false); $set('isEditing', false)" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-dark-700 text-slate-600 dark:text-slate-400 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-dark-800 transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
                    <i class="fas fa-save text-xs"></i>
                    {{ $isEditing ? 'Salvar Alterações' : 'Adicionar Membro' }}
                </button>
            </div>
        </form>
    </div>
    @else
    <!-- Tabela -->
    <div class="bg-white dark:bg-dark-900 border border-slate-200 dark:border-dark-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 dark:bg-dark-950 border-b border-slate-200 dark:border-dark-800">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Usuário</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Grupo</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Empresas</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-dark-800">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50 dark:hover:bg-dark-800/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/20 to-indigo-600/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm">
                                    {{ substr($user->name, 0, 2) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @forelse($user->roles as $role)
                            <span class="px-3 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold">
                                {{ $role->name }}
                            </span>
                            @empty
                            <span class="text-xs text-slate-400">Sem função</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->empresas as $empresa)
                                <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-dark-950 text-slate-600 dark:text-slate-400 text-xs font-medium">
                                    {{ $empresa->nome }}
                                </span>
                                @empty
                                <span class="text-xs text-slate-400">Nenhuma</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="edit({{ $user->id }})" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-dark-800 text-slate-400 hover:text-indigo-600 dark:hover:text-white transition-all">
                                <i class="fas fa-edit text-sm"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-users text-4xl text-slate-300 dark:text-slate-600 mb-3"></i>
                                <p class="text-slate-500 font-medium">Nenhum usuário encontrado</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
