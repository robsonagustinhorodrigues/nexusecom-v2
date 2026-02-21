<?php

namespace App\Livewire\Admin;

use App\Models\Empresa;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class Empresas extends Component
{
    use WithFileUploads;

    public $empresas;

    public $empresaId;

    public $nome;

    public function connectMeli()
    {
        return redirect()->route('meli.redirect');
    }

    public $razao_social;

    public $apelido;

    public $cnpj;

    public $email_contabil;

    public $certificado_senha;

    public $logo;

    public $certificado;

    public $auto_ciencia = false;

    public $isEditing = false;

    public $isCreating = false;

    public $activeTab = 'basic';

    public $showPassword = false;

    public $certValidationResult = null;

    public $showUpload = false;

    public $danfe_enabled = true;

    public $danfe_show_logo = true;

    public $danfe_show_itens = true;

    public $danfe_show_valor_itens = true;

    public $danfe_show_valor_total = true;

    public $danfe_show_qrcode = true;

    public $danfe_rodape = '';

    public $tipo_atividade = 'anexo_i';

    // Configurações Fiscais
    public $regime_tributario = 'simples_nacional';

    public $aliquota_icms = 0;

    public $aliquota_pis = 0;

    public $aliquota_cofins = 0;

    public $aliquota_csll = 0;

    public $aliquota_irpj = 0;

    public $aliquota_iss = 0;

    public $percentual_lucro_presumido = 32;

    public $aliquota_simples = null;

    public $calcula_imposto_auto = true;

    public $sefaz_intervalo_horas = 6;

    public $tpAmb = 1;

    public $sefaz_ativo = true;

    public function mount()
    {
        $this->refreshData();
    }

    public function refreshData()
    {
        $this->empresas = Empresa::orderBy('nome')->get();
    }

    public function create()
    {
        $this->reset(['empresaId', 'nome', 'razao_social', 'apelido', 'cnpj', 'email_contabil', 'certificado_senha', 'logo', 'certificado', 'auto_ciencia', 'showPassword', 'certValidationResult', 'showUpload']);
        $this->reset(['danfe_enabled', 'danfe_show_logo', 'danfe_show_itens', 'danfe_show_valor_itens', 'danfe_show_valor_total', 'danfe_show_qrcode', 'danfe_rodape', 'tipo_atividade']);
        $this->danfe_enabled = true;
        $this->danfe_show_logo = true;
        $this->danfe_show_itens = true;
        $this->danfe_show_valor_itens = true;
        $this->danfe_show_valor_total = true;
        $this->danfe_show_qrcode = true;
        $this->tipo_atividade = 'anexo_i';
        $this->isEditing = false;
        $this->isCreating = true;
        $this->activeTab = 'basic';
    }

    public function edit($id)
    {
        $empresa = Empresa::findOrFail($id);

        $this->reset(['logo', 'certificado', 'certificado_senha', 'showPassword', 'certValidationResult', 'showUpload']);

        $this->empresaId = $empresa->id;
        $this->nome = $empresa->nome;
        $this->razao_social = $empresa->razao_social;
        $this->apelido = $empresa->apelido;
        $this->cnpj = $empresa->cnpj;
        $this->email_contabil = $empresa->email_contabil;
        $this->auto_ciencia = (bool) $empresa->auto_ciencia;

        $this->danfe_enabled = $empresa->danfe_enabled ?? true;
        $this->danfe_show_logo = $empresa->danfe_show_logo ?? true;
        $this->danfe_show_itens = $empresa->danfe_show_itens ?? true;
        $this->danfe_show_valor_itens = $empresa->danfe_show_valor_itens ?? true;
        $this->danfe_show_valor_total = $empresa->danfe_show_valor_total ?? true;
        $this->danfe_show_qrcode = $empresa->danfe_show_qrcode ?? true;
        $this->danfe_rodape = $empresa->danfe_rodape ?? '';
        $this->tipo_atividade = $empresa->tipo_atividade ?? 'anexo_i';

        $this->regime_tributario = $empresa->regime_tributario ?? 'simples_nacional';
        $this->aliquota_icms = $empresa->aliquota_icms ?? 0;
        $this->aliquota_pis = $empresa->aliquota_pis ?? 0;
        $this->aliquota_cofins = $empresa->aliquota_cofins ?? 0;
        $this->aliquota_csll = $empresa->aliquota_csll ?? 0;
        $this->aliquota_irpj = $empresa->aliquota_irpj ?? 0;
        $this->aliquota_iss = $empresa->aliquota_iss ?? 0;
        $this->percentual_lucro_presumido = $empresa->percentual_lucro_presumido ?? 32;
        $this->aliquota_simples = $empresa->aliquota_simples;
        $this->calcula_imposto_auto = $empresa->calcula_imposto_auto ?? true;

        $this->sefaz_intervalo_horas = $empresa->sefaz_intervalo_horas ?? 6;
        $this->tpAmb = $empresa->tpAmb ?? 1;
        $this->sefaz_ativo = $empresa->sefaz_ativo ?? true;

        $this->certificado_senha = '';
        $this->showPassword = false;
        $this->certValidationResult = null;
        $this->showUpload = false;
        $this->certificado = null;

        $this->isCreating = false;
        $this->isEditing = true;
        $this->activeTab = 'basic';
    }

    public function save()
    {
        $this->validate([
            'nome' => 'required|min:3',
            'razao_social' => 'required',
            'cnpj' => ['required', function ($attribute, $value, $fail) {
                $clean = preg_replace('/[^0-9]/', '', $value);
                if (strlen($clean) == 11) {
                    if (! $this->validateCPF($clean)) {
                        $fail('CPF inválido.');
                    }
                } elseif (strlen($clean) == 14) {
                    if (! $this->validateCNPJ($clean)) {
                        $fail('CNPJ inválido.');
                    }
                } else {
                    $fail('O documento deve ser um CPF (11 dígitos) ou CNPJ (14 dígitos).');
                }
            }],
        ]);

        $data = [
            'nome' => $this->nome,
            'razao_social' => $this->razao_social,
            'apelido' => $this->apelido,
            'cnpj' => $this->cnpj,
            'email_contabil' => $this->email_contabil,
            'auto_ciencia' => $this->auto_ciencia,
            'slug' => Str::slug($this->nome),
            'danfe_enabled' => $this->danfe_enabled,
            'danfe_show_logo' => $this->danfe_show_logo,
            'danfe_show_itens' => $this->danfe_show_itens,
            'danfe_show_valor_itens' => $this->danfe_show_valor_itens,
            'danfe_show_valor_total' => $this->danfe_show_valor_total,
            'danfe_show_qrcode' => $this->danfe_show_qrcode,
            'danfe_rodape' => $this->danfe_rodape,
            'tipo_atividade' => $this->tipo_atividade,
            'regime_tributario' => $this->regime_tributario,
            'aliquota_icms' => $this->aliquota_icms,
            'aliquota_pis' => $this->aliquota_pis,
            'aliquota_cofins' => $this->aliquota_cofins,
            'aliquota_csll' => $this->aliquota_csll,
            'aliquota_irpj' => $this->aliquota_irpj,
            'aliquota_iss' => $this->aliquota_iss,
            'percentual_lucro_presumido' => $this->percentual_lucro_presumido,
            'aliquota_simples' => $this->aliquota_simples,
            'calcula_imposto_auto' => $this->calcula_imposto_auto,
            'sefaz_intervalo_horas' => $this->sefaz_intervalo_horas,
            'tpAmb' => $this->tpAmb,
            'sefaz_ativo' => $this->sefaz_ativo,
        ];

        if ($this->certificado_senha) {
            $data['certificado_senha'] = $this->certificado_senha;
        }
        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('logos', 'public');
        }
        if ($this->certificado) {
            $data['certificado_a1_path'] = $this->certificado->store('certificados', 'private');
        }

        if ($this->isEditing) {
            Empresa::findOrFail($this->empresaId)->update($data);
            session()->flash('message', 'Empresa atualizada com sucesso! ⚡');
        } else {
            Empresa::create($data);
            session()->flash('message', 'Empresa cadastrada com sucesso! ⚡');
        }

        $this->isEditing = false;
        $this->isCreating = false;
        $this->reset(['certificado_senha', 'showPassword', 'certValidationResult', 'showUpload', 'certificado']);
        $this->refreshData();
    }

    public function testarCertificado()
    {
        $this->certValidationResult = null;

        $this->validate([
            'certificado_senha' => 'required',
        ]);

        $certPath = null;
        if ($this->certificado) {
            $certPath = $this->certificado->getRealPath();
        } elseif ($this->empresaId) {
            $empresa = Empresa::find($this->empresaId);
            if ($empresa && $empresa->certificado_a1_path) {
                if (Storage::disk('private')->exists($empresa->certificado_a1_path)) {
                    $certPath = Storage::disk('private')->path($empresa->certificado_a1_path);
                }
            }
        }

        if (! $certPath) {
            $this->certValidationResult = [
                'status' => 'error',
                'message' => 'Certificado não encontrado para teste. ❌',
            ];

            return;
        }

        $certData = file_get_contents($certPath);
        $certs = [];

        // Limpa erros anteriores do OpenSSL
        while (openssl_error_string());

        if (openssl_pkcs12_read($certData, $certs, $this->certificado_senha)) {
            $this->parseAndSelectCert($certs['cert']);
        } else {
            $errors = [];
            $hasLegacyError = false;
            while ($msg = openssl_error_string()) {
                if (str_contains($msg, '0308010C')) {
                    $hasLegacyError = true;
                }
                $errors[] = $msg;
            }

            // Plano B: Tenta via CLI com a flag -legacy
            if ($hasLegacyError) {
                $pwdArg = escapeshellarg($this->certificado_senha);
                $pathArg = escapeshellarg($certPath);
                $cmd = "openssl pkcs12 -in {$pathArg} -nodes -legacy -password pass:{$pwdArg} -passin pass:{$pwdArg} 2>&1";

                $output = [];
                $resultCode = 0;
                exec($cmd, $output, $resultCode);

                $fullOutput = implode("\n", $output);

                if ($resultCode === 0 && str_contains($fullOutput, '-----BEGIN CERTIFICATE-----')) {
                    // Extrai o primeiro certificado do output PEM
                    if (preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $fullOutput, $matches)) {
                        $this->parseAndSelectCert($matches[0], true);

                        return;
                    }
                }

                $errorMsg = '<b>Erro de Compatibilidade (Legacy)</b><br>Seu certificado usa uma criptografia antiga. Tentamos o plano B via terminal mas também não funcionou.<br><b>Dica:</b> Re-exporte o certificado PFX com criptografia moderna (AES256).';
            } else {
                $errorMsg = 'Falha na leitura: '.(! empty($errors) ? implode(' | ', $errors) : 'Senha incorreta ou formato inválido.');
            }

            $this->certValidationResult = [
                'status' => 'error',
                'message' => $errorMsg.' ❌',
            ];
        }
    }

    private function parseAndSelectCert($certContent, $isLegacy = false)
    {
        $certObj = openssl_x509_parse($certContent);
        $expirationDate = \Carbon\Carbon::createFromTimestamp($certObj['validTo_time_t']);
        $isExpired = $expirationDate->isPast();

        $extractedDoc = $this->extractDocumentFromCertificate($certObj);
        $inputDoc = preg_replace('/[^0-9]/', '', $this->cnpj);

        $this->certValidationResult = [
            'status' => $isExpired ? 'error' : 'success',
            'expiration' => $expirationDate->format('d/m/Y H:i'),
            'isExpired' => $isExpired,
            'document' => $extractedDoc,
            'match' => ($extractedDoc && $extractedDoc === $inputDoc),
            'isLegacy' => $isLegacy,
            'message' => $isExpired ? 'Certificado expirado! ❌' : 'Certificado validado com sucesso! '.($isLegacy ? '(Modo Legado) ' : '').'✅',
        ];
    }

    private function extractDocumentFromCertificate($certObj)
    {
        // 1. Tenta extrair do Common Name (CN) - Padrão muito comum no ICP-Brasil: "NOME:14DÍGITOS"
        $commonName = $certObj['subject']['CN'] ?? '';
        if (preg_match('/:(\d{11,14})$/', $commonName, $matches)) {
            return $matches[1];
        }

        // 2. Tenta extrair das extensões Subject Alternative Name (SAN)
        $extensions = $certObj['extensions'] ?? [];
        $subjectAltName = $extensions['subjectAltName'] ?? '';

        // OIDs ICP-Brasil:
        // 2.16.76.1.3.3 (CNPJ)
        // 2.16.76.1.3.1 (CPF)
        if (preg_match('/2\.16\.76\.1\.3\.3.*?(\d{14})/', $subjectAltName, $matches)) {
            return $matches[1];
        }
        if (preg_match('/2\.16\.76\.1\.3\.1.*?(\d{11})/', $subjectAltName, $matches)) {
            return $matches[1];
        }

        // 3. Fallback: Procura qualquer sequência numérica de 11 ou 14 dígitos no subject
        // Muitos certificados salvam em campos de OID customizados que o PHP agrupa no array principal do subject
        foreach ($certObj['subject'] as $key => $value) {
            if (is_string($value)) {
                $clean = preg_replace('/[^0-9]/', '', $value);
                if (strlen($clean) == 14 || strlen($clean) == 11) {
                    return $clean;
                }
            }
        }

        return null;
    }

    public function togglePassword()
    {
        $this->showPassword = ! $this->showPassword;
    }

    public function delete($id)
    {
        Empresa::findOrFail($id)->delete();
        $this->refreshData();
        session()->flash('message', 'Empresa removida.');
    }

    private function validateCPF($cpf)
    {
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    private function validateCNPJ($cnpj)
    {
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        $tamanhos = [12, 13];
        foreach ($tamanhos as $tamanho) {
            $soma = 0;
            $pos = $tamanho - 7;
            for ($i = $tamanho; $i >= 1; $i--) {
                $soma += $cnpj[$tamanho - $i] * $pos--;
                if ($pos < 2) {
                    $pos = 9;
                }
            }
            $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
            if ($cnpj[$tamanho] != $resultado) {
                return false;
            }
        }

        return true;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.empresas');
    }
}
