<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Components\Layouts\CompanySelector;
use Tests\TestCase;

class CompanySelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_see_their_companies()
    {
        $user = User::factory()->create();
        $empresa1 = Empresa::create(['nome' => 'Company 1', 'cnpj' => '12.345.678/0001-01']);
        $empresa2 = Empresa::create(['nome' => 'Company 2', 'cnpj' => '12.345.678/0001-02']);
        
        $user->empresas()->attach([$empresa1->id, $empresa2->id]);

        $this->actingAs($user);

        Livewire::test(CompanySelector::class)
            ->assertSee('Company 1')
            ->assertSee('Company 2');
    }

    public function test_user_can_switch_company()
    {
        $user = User::factory()->create();
        $empresa1 = Empresa::create(['nome' => 'Company 1', 'cnpj' => '12.345.678/0001-01']);
        $empresa2 = Empresa::create(['nome' => 'Company 2', 'cnpj' => '12.345.678/0001-02']);
        
        $user->empresas()->attach([$empresa1->id, $empresa2->id]);
        $user->update(['current_empresa_id' => $empresa1->id]);

        $this->actingAs($user);

        Livewire::test(CompanySelector::class)
            ->call('selectCompany', $empresa2->id)
            ->assertRedirect();

        $this->assertEquals($empresa2->id, $user->fresh()->current_empresa_id);
        $this->assertEquals($empresa2->id, session('empresa_id'));
    }
}
