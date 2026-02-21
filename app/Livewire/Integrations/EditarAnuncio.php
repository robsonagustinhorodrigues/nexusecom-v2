<?php

namespace App\Livewire\Integrations;

use App\Models\MarketplaceAnuncio;
use App\Models\ProductSku;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditarAnuncio extends Component
{
    use WithFileUploads;

    public ?MarketplaceAnuncio $anuncio = null;
    public bool $showModal = false;
    
    // Campos editáveis
    public string $titulo = '';
    public string $descricao = '';
    public float $preco = 0;
    public int $estoque = 0;
    public string $sku = '';
    public string $preco_custo = '';
    public bool $status_ativo = true;
    public bool $aceita_mercadopago = true;
    
    // Variações
    public array $variacoes = [];
    
    // Imagens
    public $novaImagem = null;
    public array $imagens = [];
    
    // Loading states
    public bool $saving = false;
    public string $feedback = '';

    #[On('abrir-editar-anuncio')]
    public function abrir($anuncioId)
    {
        $this->anuncio = MarketplaceAnuncio::with(['sku.product', 'integracao'])->findOrFail($anuncioId);
        
        // Carrega campos
        $jsonData = $this->anuncio->json_data ?? [];
        
        $this->titulo = $this->anuncio->titulo ?? '';
        $this->descricao = $jsonData['descricao'] ?? '';
        $this->preco = floatval($this->anuncio->preco ?? 0);
        $this->estoque = intval($this->anuncio->estoque ?? 0);
        $this->sku = $this->anuncio->sku ?? '';
        $this->preco_custo = $jsonData['precoCusto'] ?? ($this->anuncio->sku?->product?->preco_custo ?? '');
        $this->status_ativo = $this->anuncio->status === 'active';
        $this->aceita_mercadopago = $jsonData['accepts_mercadopago'] ?? true;
        
        // Carrega variações
        $this->variacoes = [];
        if (!empty($jsonData['variations'])) {
            foreach ($jsonData['variations'] as $var) {
                $this->variacoes[] = [
                    'id' => $var['id'] ?? null,
                    'sku' => $var['seller_custom_field'] ?? $var['sku'] ?? '',
                    'preco' => floatval($var['price'] ?? $this->preco),
                    'estoque' => intval($var['available_quantity'] ?? 0),
                    'atributos' => $var['attribute_combinations'] ?? [],
                ];
            }
        }
        
        // Carrega imagens
        $this->imagens = [];
        if (!empty($jsonData['pictures'])) {
            foreach ($jsonData['pictures'] as $pic) {
                $this->imagens[] = [
                    'url' => $pic['url'] ?? $pic['secure_url'] ?? $pic['id'] ?? '',
                    'id' => $pic['id'] ?? null,
                ];
            }
        }
        
        $this->showModal = true;
        $this->feedback = '';
    }

    public function salvar()
    {
        $this->saving = true;
        $this->feedback = '';

        try {
            // Atualiza no banco local
            $updateData = [
                'titulo' => $this->titulo,
                'preco' => $this->preco,
                'estoque' => $this->estoque,
                'sku' => $this->sku,
                'status' => $this->status_ativo ? 'active' : 'inactive',
            ];

            // Atualiza JSON local
            $jsonData = $this->anuncio->json_data ?? [];
            $jsonData['title'] = $this->titulo;
            $jsonData['price'] = $this->preco;
            $jsonData['descricao'] = $this->descricao;
            $jsonData['precoCusto'] = floatval($this->preco_custo);
            $jsonData['accepts_mercadopago'] = $this->aceita_mercadopago;

            // Atualiza variações no JSON se existirem
            if (!empty($this->variacoes)) {
                if (!isset($jsonData['variations'])) {
                    $jsonData['variations'] = [];
                }
                foreach ($this->variacoes as $varIndex => $var) {
                    if (isset($jsonData['variations'][$varIndex])) {
                        $jsonData['variations'][$varIndex]['price'] = $var['preco'];
                        $jsonData['variations'][$varIndex]['available_quantity'] = $var['estoque'];
                        if ($var['sku']) {
                            $jsonData['variations'][$varIndex]['seller_custom_field'] = $var['sku'];
                        }
                    }
                }
            }

            $updateData['json_data'] = $jsonData;

            $this->anuncio->update($updateData);

            // Sincroniza com marketplace se for Mercado Livre
            if ($this->anuncio->marketplace === 'mercadolivre' && $this->anuncio->integracao) {
                try {
                    $service = new \App\Services\MeliIntegrationService($this->anuncio->empresa_id);
                    
                    // Atualiza título e preço
                    $service->atualizarAnuncio($this->anuncio->external_id, [
                        'title' => $this->titulo,
                        'price' => $this->preco,
                    ]);
                    
                    $this->feedback = 'Salvo localmente e sincronizado com o Mercado Livre!';
                } catch (\Exception $e) {
                    $this->feedback = 'Salvo localmente. Erro ao sincronizar: ' . $e->getMessage();
                }
            } else {
                $this->feedback = 'Salvo com sucesso!';
            }

            // Dispara evento para atualizar lista
            $this->dispatch('anuncio-atualizado');

        } catch (\Exception $e) {
            $this->feedback = 'Erro: ' . $e->getMessage();
        } finally {
            $this->saving = false;
        }
    }

    public function fechar()
    {
        $this->showModal = false;
        $this->anuncio = null;
    }

    public function render()
    {
        return view('livewire.integrations.editar-anuncio');
    }
}
