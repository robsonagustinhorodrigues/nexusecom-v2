<?php

use App\Models\Grupo;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Armazem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\EstoqueMovimentacao;

// 1. Criar um grupo padrão inicial
$grupo = Grupo::firstOrCreate(
    ['slug' => 'grupo-inicial'],
    ['nome' => 'Grupo Inicial']
);

echo "Usando Grupo: " . $grupo->nome . " (ID: " . $grupo->id . ")\n";

// 2. Vincular todos os usuários sem grupo
$usersCount = User::whereNull('grupo_id')->update(['grupo_id' => $grupo->id]);
echo "Usuários vinculados: $usersCount\n";

// 3. Vincular todas as empresas sem grupo
$empresasCount = Empresa::whereNull('grupo_id')->update(['grupo_id' => $grupo->id]);
echo "Empresas vinculadas: $empresasCount\n";

// 4. Vincular todos os armazéns sem grupo
$armazensCount = Armazem::whereNull('grupo_id')->update(['grupo_id' => $grupo->id]);
echo "Armazéns vinculados: $armazensCount\n";

// 5. Vincular todos os produtos sem grupo
$productsCount = Product::whereNull('grupo_id')->update(['grupo_id' => $grupo->id]);
echo "Produtos vinculados: $productsCount\n";

// 6. Vincular todos os SKUs sem grupo
$skusCount = ProductSku::whereNull('grupo_id')->update(['grupo_id' => $grupo->id]);
echo "SKUs vinculados: $skusCount\n";

// 7. Vincular todas as movimentações de estoque sem grupo
$movsCount = EstoqueMovimentacao::whereNull('grupo_id')->update(['grupo_id' => $grupo->id]);
echo "Movimentações vinculadas: $movsCount\n";

echo "Migração de dados concluída com sucesso! ✅\n";
