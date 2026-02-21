<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Empresa;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;

class Usuarios extends Component
{
    public $users, $roles, $empresas;
    public $isEditing = false;
    public $isCreating = false;

    // Campos do formulário
    public $userId, $name, $email, $password, $role_id, $selected_empresas = [];

    public function mount()
    {
        $this->refreshData();
    }

    public function refreshData()
    {
        $grupoId = auth()->user()->grupo_id;
        $this->users = User::where('grupo_id', $grupoId)->with(['roles', 'empresas'])->get();
        $this->roles = Role::all();
        $this->empresas = Empresa::orderBy('nome')->get();
    }

    public function create()
    {
        $this->reset(['userId', 'name', 'email', 'password', 'role_id', 'selected_empresas']);
        $this->isEditing = false;
        $this->isCreating = true;
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = $user->roles->first()?->id;
        $this->selected_empresas = $user->empresas->pluck('id')->toArray();
        
        $this->isCreating = false;
        $this->isEditing = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . ($this->userId ?? 'NULL'),
            'password' => $this->isCreating ? 'required|min:6' : 'nullable|min:6',
        ]);

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $userData['password'] = Hash::make($this->password);
        }

        if ($this->isEditing) {
            $user = User::findOrFail($this->userId);
            $user->update($userData);
            session()->flash('message', 'Usuário atualizado com sucesso!');
        } else {
            $user = User::create($userData);
            session()->flash('message', 'Usuário cadastrado com sucesso!');
        }

        // Sincroniza Role
        if ($this->role_id) {
            $role = Role::find($this->role_id);
            $user->syncRoles([$role->name]);
        }

        // Sincroniza Empresas
        $user->empresas()->sync($this->selected_empresas);

        $this->reset(['isEditing', 'isCreating', 'password']);
        $this->refreshData();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.usuarios');
    }
}
