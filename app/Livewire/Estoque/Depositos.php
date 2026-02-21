<?php

namespace App\Livewire\Estoque;

use App\Models\Deposito;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Depositos extends Component
{
    public $depositos = [];
    
    public $empresas = [];
    
    public $showModal = false;
    
    public $depositoId = null;
    
    public $nome = '';
    
    public $descricao = '';
    
    public $tipo = 'armazem';
    
    public $ativo = true;
    
    public $compartilhado = false;
    
    public $empresa_dona_id = null;
    
    public $empresa_filtro = '';

    public function mount()
    {
        $this->empresas = Empresa::all();
        $this->empresa_filtro = Auth::user()->current_empresa_id;
        $this->carregarDepositos();
    }

    public function carregarDepositos()
    {
        $query = Deposito::query()
            ->when($this->empresa_filtro, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('empresa_id', $this->empresa_filtro)
                        ->orWhere('compartilhado', true);
                });
            })
            ->orderBy('nome');

        $this->depositos = $query->get();
    }

    public function novoDeposito()
    {
        $this->reset(['depositoId', 'nome', 'descricao', 'tipo', 'ativo', 'compartilhado', 'empresa_dona_id']);
        $this->empresa_dona_id = Auth::user()->current_empresa_id;
        $this->showModal = true;
    }

    public function editarDeposito(Deposito $deposito)
    {
        $this->depositoId = $deposito->id;
        $this->nome = $deposito->nome;
        $this->descricao = $deposito->descricao;
        $this->tipo = $deposito->tipo;
        $this->ativo = $deposito->ativo;
        $this->compartilhado = $deposito->compartilhado;
        $this->empresa_dona_id = $deposito->empresa_dona_id;
        $this->showModal = true;
    }

    public function salvar()
    {
        $this->validate([
            'nome' => 'required|min:2',
            'tipo' => 'required|in:loja,armazem,full,virtual',
            'empresa_dona_id' => 'required_if:compartilhado,false',
        ]);

        $data = [
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'tipo' => $this->tipo,
            'ativo' => $this->ativo,
            'compartilhado' => $this->compartilhado,
            'empresa_dona_id' => $this->compartilhado ? null : $this->empresa_dona_id,
        ];

        if ($this->depositoId) {
            $deposito = Deposito::find($this->depositoId);
            $deposito->update($data);
        } else {
            $data['empresa_id'] = Auth::user()->current_empresa_id;
            Deposito::create($data);
        }

        $this->showModal = false;
        $this->carregarDepositos();
        session()->flash('success', 'Depósito salvo com sucesso!');
    }

    public function deletar(Deposito $deposito)
    {
        $deposito->delete();
        $this->carregarDepositos();
        session()->flash('success', 'Depósito excluído!');
    }

    public function render()
    {
        return view('livewire.estoque.depositos');
    }
}
