<?php

namespace App\Services;

use App\Models\Deposito;
use App\Models\Empresa;
use App\Models\EstoqueMovimentacao;
use App\Models\MarketplaceAnuncio;
use App\Models\MarketplacePedido;
use App\Models\ProductSku;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EstoqueMovimentacaoService
{
    /**
     * Tipos de movimentação
     */
    const TIPO_ENTRADA = 'entrada';
    const TIPO_SAIDA = 'saida';
    
    /**
     * Tipos de documento/origem
     */
    const DOC_NFE_COMPRA = 'nfe_compra';
    const DOC_NFE_DEVOLUCAO = 'nfe_devolucao';
    const DOC_PEDIDO_VENDA = 'pedido_venda';
    const DOC_AJUSTE = 'ajuste';
    const DOC_TRANSFERENCIA = 'transferencia';
    
    /**
     * Registra entrada de estoque
     */
    public function registrarEntrada(array $dados): EstoqueMovimentacao
    {
        $dados['tipo'] = self::TIPO_ENTRADA;
        $dados['status'] = 'confirmado';
        $dados['user_id'] = Auth::id();
        
        $movimentacao = $this->criarMovimentacao($dados);
        
        // Atualiza saldo do depósito
        $this->atualizarSaldo($movimentacao);
        
        return $movimentacao;
    }
    
    /**
     * Registra saída de estoque (geralmente por pedido)
     */
    public function registrarSaida(array $dados): EstoqueMovimentacao
    {
        $dados['tipo'] = self::TIPO_SAIDA;
        $dados['status'] = 'confirmado';
        $dados['user_id'] = Auth::id();
        
        $movimentacao = $this->criarMovimentacao($dados);
        
        // Atualiza saldo do depósito
        $this->atualizarSaldo($movimentacao);
        
        return $movimentacao;
    }
    
    /**
     * Registra saída automática por pedido
     * Retorna false se pedido já foi enviado (não pode repor)
     */
    public function registrarSaidaPorPedido(MarketplacePedido $pedido, int $skuId, int $quantidade, int $depositoId): ?EstoqueMovimentacao
    {
        // Se pedido já foi enviado, não pode repor automaticamente
        // Tem que ser via NF de devolução
        if (in_array($pedido->status, ['enviado', 'entregue', 'delivered', 'shipped'])) {
            Log::info("EstoqueMovimentacao: Pedido {$pedido->pedido_id} já enviado. Não será feita baixa automática.");
            return null;
        }
        
        // Verificar estoque disponível
        $saldo = $this->getSaldo($skuId, $depositoId);
        
        if ($saldo < $quantidade) {
            Log::warning("EstoqueMovimentacao: Saldo insuficiente para SKU {$skuId} no depósito {$depositoId}. Saldo: {$saldo}, solicitado: {$quantidade}");
            return null;
        }
        
        $dados = [
            'product_sku_id' => $skuId,
            'deposito_id' => $depositoId,
            'quantidade' => $quantidade,
            'tipo' => self::TIPO_SAIDA,
            'documento_tipo' => self::DOC_PEDIDO_VENDA,
            'documento' => $pedido->pedido_id,
            'pedido_id' => $pedido->id,
            'empresa_id' => $pedido->empresa_id,
            'origem' => 'pedido',
            'observacao' => "Baixa por pedido: {$pedido->pedido_id}",
        ];
        
        return $this->registrarSaida($dados);
    }
    
    /**
     * Registra entrada por NFe de compra
     */
    public function registrarEntradaNFeCompra(array $dados): EstoqueMovimentacao
    {
        $dados['documento_tipo'] = self::DOC_NFE_COMPRA;
        
        return $this->registrarEntrada($dados);
    }
    
    /**
     * Registra devolução
     * Se produto_bom = true: volta ao estoque
     * Se produto_bom = false: registra perda (não volta ao estoque)
     */
    public function registrarDevolucao(array $dados): EstoqueMovimentacao
    {
        $dados['documento_tipo'] = self::DOC_NFE_DEVOLUCAO;
        
        // Se produto não está bom, não volta ao estoque - é perda
        if (isset($dados['produto_bom']) && !$dados['produto_bom']) {
            // Registra como perda (não incrementa saldo)
            $dados['tipo'] = 'perda';
            $dados['observacao'] = ($dados['observacao'] ?? ''). ' - Produto dañado/perdido';
        }
        
        return $this->registrarEntrada($dados);
    }
    
    /**
     * Repor estoque por cancelamento de pedido
     * Usado quando o pedido foi cancelado ANTES do envio
     * Se já foi enviado, deve usar NF de devolução
     */
    public function reporEstoquePorCancelamento(MarketplacePedido $pedido, int $skuId, int $quantidade, int $depositoId): ?EstoqueMovimentacao
    {
        // Se pedido já foi enviado, não pode repor automaticamente
        // Tem que ser via NF de devolução
        if (in_array($pedido->status, ['enviado', 'entregue', 'delivered', 'shipped'])) {
            Log::info("EstoqueMovimentacao: Pedido {$pedido->pedido_id} já enviado. Use NF de devolução para repor.");
            return null;
        }
        
        $dados = [
            'product_sku_id' => $skuId,
            'deposito_id' => $depositoId,
            'quantidade' => $quantidade,
            'tipo' => self::TIPO_ENTRADA,
            'documento_tipo' => 'pedido_cancelado',
            'documento' => $pedido->pedido_id,
            'pedido_id' => $pedido->id,
            'empresa_id' => $pedido->empresa_id,
            'origem' => 'pedido_cancelado',
            'observacao' => "Reposição por cancelamento do pedido {$pedido->pedido_id}",
        ];
        
        return $this->registrarEntrada($dados);
    }
    
    /**
     * Criar transferência entre depósitos
     */
    public function transferencia(int $skuId, int $depositoOrigemId, int $depositoDestinoId, int $quantidade, ?string $observacao = null): bool
    {
        // Verificar saldo na origem
        $saldo = $this->getSaldo($skuId, $depositoOrigemId);
        
        if ($saldo < $quantidade) {
            return false;
        }
        
        // Saída do depósito origem
        $this->registrarSaida([
            'product_sku_id' => $skuId,
            'deposito_id' => $depositoOrigemId,
            'quantidade' => $quantidade,
            'documento_tipo' => self::DOC_TRANSFERENCIA,
            'observacao' => $observacao ?? 'Transferência',
        ]);
        
        // Entrada no depósito destino
        $this->registrarEntrada([
            'product_sku_id' => $skuId,
            'deposito_id' => $depositoDestinoId,
            'quantidade' => $quantidade,
            'documento_tipo' => self::DOC_TRANSFERENCIA,
            'observacao' => $observacao ?? 'Transferência',
        ]);
        
        return true;
    }
    
    /**
     * Cria registro de movimentação
     */
    private function criarMovimentacao(array $dados): EstoqueMovimentacao
    {
        return EstoqueMovimentacao::create($dados);
    }
    
    /**
     * Atualiza saldo após movimentação
     */
    private function atualizarSaldo(EstoqueMovimentacao $movimentacao): void
    {
        $saldoAtual = $this->getSaldo(
            $movimentacao->product_sku_id,
            $movimentacao->deposito_id
        );
        
        if ($movimentacao->tipo === 'entrada' || $movimentacao->tipo === 'devolucao') {
            $novoSaldo = $saldoAtual + $movimentacao->quantidade;
        } elseif ($movimentacao->tipo === 'saida') {
            $novoSaldo = $saldoAtual - $movimentacao->quantidade;
        } else {
            // Perda não altera saldo
            return;
        }
        
        \App\Models\EstoqueSaldo::updateOrCreate(
            [
                'product_sku_id' => $movimentacao->product_sku_id,
                'deposito_id' => $movimentacao->deposito_id,
            ],
            [
                'saldo' => max(0, $novoSaldo)
            ]
        );
    }
    
    /**
     * Obtém saldo atual
     */
    public function getSaldo(int $skuId, int $depositoId): int
    {
        $estoque = \App\Models\EstoqueSaldo::where('product_sku_id', $skuId)
            ->where('deposito_id', $depositoId)
            ->first();
        
        return $estoque?->saldo ?? 0;
    }
    
    /**
     * Obtém saldo total do SKU em todos os depósitos
     */
    public function getSaldoTotal(int $skuId): int
    {
        return \App\Models\EstoqueSaldo::where('product_sku_id', $skuId)
            ->sum('saldo');
    }
    
    /**
     * Estorna/reverte uma movimentação
     * Útil para ajustes fiscais
     * Cria uma movimentação oposta para compensar
     * 
     * @param int $movimentacaoId ID da movimentação original
     * @param string $motivo Motivo do estorno
     * @return EstoqueMovimentacao|null
     */
    public function estornar(int $movimentacaoId, ?string $motivo = null): ?EstoqueMovimentacao
    {
        $original = EstoqueMovimentacao::find($movimentacaoId);
        
        if (!$original) {
            Log::warning("EstoqueMovimentacao: Movimentação {$movimentacaoId} não encontrada para estorno");
            return null;
        }
        
        // Se já foi estornada, não permite estornar novamente
        if ($original->movimentacao_estornada_id) {
            Log::warning("EstoqueMovimentacao: Movimentação {$movimentacaoId} já foi estornada");
            return null;
        }
        
        // Não pode estornar perda - só entrada/saída
        if ($original->tipo === 'perda') {
            Log::warning("EstoqueMovimentacao: Não é possível estornar perda");
            return null;
        }
        
        // Determina tipo oposto
        $tipoEstorno = $original->tipo === 'entrada' ? 'saida' : 'entrada';
        
        $dados = [
            'product_sku_id' => $original->product_sku_id,
            'deposito_id' => $original->deposito_id,
            'quantidade' => $original->quantidade,
            'tipo' => $tipoEstorno,
            'documento_tipo' => 'estorno',
            'documento' => $original->documento,
            'valor_unitario' => $original->valor_unitario,
            'observacao' => "Estorno de: {$original->id}. Motivo: " . ($motivo ?? 'Ajuste fiscal'),
            'empresa_id' => $original->empresa_id,
            'origem' => 'estorno',
            'movimentacao_estornada_id' => $original->id,
        ];
        
        // Cria movimentação de estorno
        $estorno = $this->criarMovimentacao($dados);
        
        // Atualiza saldo
        $this->atualizarSaldo($estorno);
        
        // Marca a original como estornada
        $original->movimentacao_estornada_id = $estorno->id;
        $original->save();
        
        Log::info("EstoqueMovimentacao: Movimentação {$movimentacaoId} estornada. Nova: {$estorno->id}");
        
        return $estorno;
    }
}
