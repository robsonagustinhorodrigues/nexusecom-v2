<?php

namespace App\Livewire\Admin;

use App\Models\Notificacao;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Avisos extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function render()
    {
        $notificacoes = Notificacao::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.admin.avisos', compact('notificacoes'));
    }

    public function marcarLida(int $id): void
    {
        Notificacao::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['read' => true]);
    }

    public function marcarTodasLida(): void
    {
        Notificacao::where('user_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);
    }

    public function excluir(int $id): void
    {
        Notificacao::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
    }

    public function limparTodas(): void
    {
        Notificacao::where('user_id', Auth::id())->delete();
    }

    public function getNaoLidasProperty(): int
    {
        return Notificacao::where('user_id', Auth::id())
            ->where('read', false)
            ->count();
    }
}
