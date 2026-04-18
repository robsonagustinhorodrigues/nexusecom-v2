<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Grupo (firstOrCreate to avoid duplicates)
        $grupo = Grupo::firstOrCreate(
            ['slug' => 'admin'],
            ['nome' => 'Administradores']
        );
        echo "Grupo: {$grupo->nome} (ID: {$grupo->id})\n";

        // Create Empresas
        $empresasData = [
            ['nome' => 'MaxLider', 'slug' => 'maxlider', 'cnpj' => '57297069000146', 'razao_social' => 'MaxLider Ltda'],
            ['nome' => 'LideraMais', 'slug' => 'lideramais', 'cnpj' => '61778473000109', 'razao_social' => 'LideraMais Ltda'],
            ['nome' => 'LideraMix', 'slug' => 'lideramix', 'cnpj' => '47650333000120', 'razao_social' => 'LideraMix Ltda'],
        ];

        $empresaIds = [];
        foreach ($empresasData as $data) {
            $empresa = Empresa::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'grupo_id' => $grupo->id, // Link to company/group
                    'inscricao_estadual' => '',
                    'endereco' => '',
                    'cidade' => '',
                    'estado' => 'SP',
                    'cep' => '',
                    'telefone' => '',
                    'email' => '',
                    'regime_tributario' => 'simples_nacional',
                    'valor_aliquota_icms' => 0,
                    'valor_aliquota_pis' => 0,
                    'valor_aliquota_cofins' => 0,
                    'valor_aliquota_csll' => 0,
                    'valor_aliquota_irrf' => 0,
                    'certificado_pfx' => null,
                    'certificado_senha' => null,
                    'certificado_vencimento' => null,
                    'ambiente_nfe' => 'producao',
                    'ambiente_nfce' => 'producao',
                    'ambiente_nfs' => 'producao',
                    'ativo' => true,
                ])
            );
            $empresaIds[] = $empresa->id;
            echo "Empresa: {$empresa->nome} (ID: {$empresa->id}, Grupo: {$grupo->nome})\n";
        }

        // Create User (firstOrCreate to avoid duplicates)
        $user = User::firstOrCreate(
            ['email' => 'robson@ortopasso.com.br'],
            [
                'name' => 'Robson',
                'password' => bcrypt('Master32'),
                'grupo_id' => $grupo->id,
                'current_empresa_id' => $empresaIds[0],
            ]
        );

        // Link user to all empresas (sync to avoid duplicates)
        $user->empresas()->sync($empresaIds);

        echo "User: {$user->email}\n";
        echo "Linked to empresas: " . implode(', ', $empresaIds) . "\n";
    }
}
