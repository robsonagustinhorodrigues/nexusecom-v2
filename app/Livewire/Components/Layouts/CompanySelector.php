<?php

namespace App\Livewire\Components\Layouts;

use App\Models\Empresa;
use Livewire\Component;

class CompanySelector extends Component
{
    public $empresas;
    public $currentEmpresa;

    public function mount()
    {
        $this->loadEmpresas();
    }

    public function loadEmpresas()
    {
        if (auth()->check()) {
            $user = auth()->user();
            // Agora as empresas são carregadas do grupo do usuário
            // O Global Scope em Empresa também ajudará a reforçar isso
            if ($user->grupo) {
                $this->empresas = $user->grupo->empresas()->orderBy('nome')->get();
            } else {
                \Illuminate\Support\Facades\Log::warning("User {$user->email} has no group assigned.");
                $this->empresas = collect();
            }
            
            // If no current empresa is set, select the first one
            if (!$user->current_empresa_id && $this->empresas->count() > 0) {
                $user->update(['current_empresa_id' => $this->empresas->first()->id]);
                session(['empresa_id' => $user->current_empresa_id]);
            }

            $this->currentEmpresa = Empresa::find($user->current_empresa_id);
        } else {
            $this->empresas = collect();
        }
    }

    public function selectCompany($id)
    {
        $user = auth()->user();
        
        // Safety check if user is associated with this company
        $canSelect = false;
        if ($user->grupo) {
            $canSelect = $user->grupo->empresas()->where('empresas.id', $id)->exists();
        }

        if ($canSelect) {
            $user->update(['current_empresa_id' => $id]);
            session(['empresa_id' => $id]);
            
            // Dispatch event or just redirect
            $this->dispatch('company-switched');
            return redirect(request()->header('Referer') ?? '/dashboard');
        }
    }

    public function render()
    {
        return view('livewire.components.layouts.company-selector');
    }
}
