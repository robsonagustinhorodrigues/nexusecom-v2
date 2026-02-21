<?php

namespace App\Services;

use App\Models\AnuncioEstoqueVirtual;
use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\MarketplaceAnuncio;
use App\Models\MarketplacePedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EstoqueService
{
    /**
     * Obtém o saldo disponível para um anúncio
     * Leva em conta estoque virtual e configuração do marketplace
     */
    public function getEstoqueDisponivel(MarketplaceAnuncio $anuncio): int
    {
        $estoqueBase = $anuncio->estoque ?? 0;
        
        // Buscar虚拟estoque virtual configurado
        $virtual = AnuncioEstoqueVirtual::where('anuncio_id', $anuncio->id)->first();
        
        if (!$virtual || !$virtual->usar_virtual) {
            return $estoqueBase;
        }
        
        // Se usa virtual, soma quantidade virtual
        return $estoqueBase + $virtual->quantidade_virtual;
    }

    /**
     * Sincroniza estoque do ML com o sistema
     * Consulta API do ML e atualiza o estoque local
     */
    public function syncEstoqueML(int $empresaId): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $anuncios = MarketplaceAnuncio::where('empresa_id', $empresaId)
            ->where('marketplace', 'mercadolivre')
            ->whereNotNull('external_id')
            ->get();

        $atualizados = 0;
        $erros = [];

        foreach ($anuncios as $anuncio) {
            try {
                $estoqueMl = $this->consultarEstoqueML($anuncio, $empresa);
                
                if ($estoqueMl !== null) {
                    // Atualiza estoque local
                    $anuncio->estoque = $estoqueMl;
                    $anuncio->save();
                    $atualizados++;
                }
            } catch (\Exception $e) {
                $erros[] = [
                    'anuncio' => $anuncio->external_id,
                    'erro' => $e->getMessage(),
                ];
            }
        }

        return [
            'total' => $anuncios->count(),
            'atualizados' => $atualizados,
            'erros' => $erros,
        ];
    }

    /**
     * Consulta estoque de um anúncio específico no ML
     */
    public function consultarEstoqueML(MarketplaceAnuncio $anuncio, Empresa $empresa): ?int
    {
        $integracao = $empresa->integracoes()
            ->where('marketplace', 'mercadolivre')
            ->where('ativo', true)
            ->first();

        if (!$integracao) {
            Log::warning("EstoqueService: Integração ML não encontrada para empresa {$empresa->id}");
            return null;
        }

        $token = $integracao->access_token;
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get("https://api.mercadolibre.com/items/{$anuncio->external_id}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['available_quantity'] ?? null;
            }

            Log::warning("EstoqueService: Erro ao buscar estoque ML {$anuncio->external_id}: " . $response->status());
            return null;
        } catch (\Exception $e) {
            Log::error("EstoqueService exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza estoque no ML
     */
    public function atualizarEstoqueML(MarketplaceAnuncio $anuncio, int $quantidade, Empresa $empresa): bool
    {
        $integracao = $empresa->integracoes()
            ->where('marketplace', 'mercadolivre')
            ->where('ativo', true)
            ->first();

        if (!$integracao) {
            return false;
        }

        $token = $integracao->access_token;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->put("https://api.mercadolibre.com/items/{$anuncio->external_id}", [
                'available_quantity' => $quantidade,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("EstoqueService atualizar exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcula estoque para atualizar no ML baseado na configuração
     * Full = usa estoque do depósito Full
     * Place/Flex = usa estoque da loja
     * Se usar virtual = soma virtual
     */
    public function calcularEstoqueParaML(MarketplaceAnuncio $anuncio, ?string $logisticType = null): int
    {
        $virtual = AnuncioEstoqueVirtual::where('anuncio_id', $anuncio->id)->first();
        $usarVirtual = $virtual && $virtual->usar_virtual;
        
        // Estoque base do anúncio
        $estoque = $anuncio->estoque ?? 0;
        
        // Se usa virtual, soma
        if ($usarVirtual) {
            $estoque += $virtual->quantidade_virtual;
        }
        
        return max(0, $estoque);
    }

    /**
     * Sincroniza todos os anúncios com o ML
     * Calcula baseado no tipo de logística configurado
     */
    public function syncTodosAnunciosML(int $empresaId): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $anuncios = MarketplaceAnuncio::where('empresa_id', $empresaId)
            ->where('marketplace', 'mercadolivre')
            ->whereNotNull('external_id')
            ->get();

        $atualizados = 0;
        $erros = [];

        foreach ($anuncios as $anuncio) {
            try {
                // Obter tipo de logística do anúncio
                $logisticType = $anuncio->json_data['shipping']['logistic_type'] ?? null;
                
                // Calcular estoque
                $novoEstoque = $this->calcularEstoqueParaML($anuncio, $logisticType);
                
                // Atualizar no ML
                $sucesso = $this->atualizarEstoqueML($anuncio, $novoEstoque, $empresa);
                
                if ($sucesso) {
                    $atualizados++;
                } else {
                    $erros[] = $anuncio->external_id;
                }
            } catch (\Exception $e) {
                $erros[] = [
                    'anuncio' => $anuncio->external_id,
                    'erro' => $e->getMessage(),
                ];
            }
        }

        return [
            'total' => $anuncios->count(),
            'atualizados' => $atualizados,
            'erros' => $erros,
        ];
    }
}
