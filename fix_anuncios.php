<?php
$content = file_get_contents('app/Livewire/Integrations/Anuncios.php');

$method = '
    public function getSelectedIntegration()
    {
        $integracao = \App\Models\Integracao::where("empresa_id", $this->empresaId)
            ->where("marketplace", $this->selectedMarketplace)
            ->where("ativo", true)
            ->first();
        return $integracao;
    }
';

$content = str_replace('public function render()', $method . "\n" . 'public function render()', $content);
file_put_contents('app/Livewire/Integrations/Anuncios.php', $content);
echo "Done\n";
