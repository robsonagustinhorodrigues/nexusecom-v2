<?php

namespace App\Livewire\Admin;

use App\Models\Tarefa;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Tarefas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $filtroStatus = '';

    public function render()
    {
        $tarefas = Tarefa::where('user_id', Auth::id())
            ->with(['empresa', 'user'])
            ->when($this->filtroStatus, fn ($q) => $q->where('status', $this->filtroStatus))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total' => Tarefa::where('user_id', Auth::id())->count(),
            'processando' => Tarefa::where('user_id', Auth::id())->where('status', 'processando')->count(),
            'concluido' => Tarefa::where('user_id', Auth::id())->where('status', 'concluido')->count(),
            'falhou' => Tarefa::where('user_id', Auth::id())->whereIn('status', ['falhou', 'concluido_com_erros'])->count(),
        ];

        return view('livewire.admin.tarefas', compact('tarefas', 'stats'));
    }

    public function getTarefaProperty(int $id): ?Tarefa
    {
        return Tarefa::where('id', $id)->where('user_id', Auth::id())->first();
    }

    public function limparConcluidas(): void
    {
        Tarefa::where('user_id', Auth::id())
            ->whereIn('status', ['concluido', 'falhou', 'concluido_com_erros', 'cancelado'])
            ->delete();
    }

    public function cancelarTarefa(int $id): void
    {
        $tarefa = Tarefa::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($tarefa && $tarefa->podeCancelar()) {
            $tarefa->cancelar();
            session()->flash('success', 'Tarefa cancelada.');
        } else {
            session()->flash('error', 'Esta tarefa não pode ser cancelada.');
        }
    }
}
