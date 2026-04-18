<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\Notificacao;
use App\Models\Tarefa;
use App\Services\SefazEngine;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BuscarNfeRetroativaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 7200;

    protected int $empresaId;

    protected int $userId;

    protected string $dataDe;

    protected string $dataAte;

    protected int $tarefaId;

    public function __construct(int $empresaId, int $userId, string $dataDe, string $dataAte)
    {
        $this->empresaId = $empresaId;
        $this->userId = $userId;
        $this->dataDe = $dataDe;
        $this->dataAte = $dataAte;

        $tarefa = Tarefa::criar('busca_sefaz_retroativa', 1, $this->empresaId, $this->userId);
        $this->tarefaId = $tarefa->id;
    }

    public function handle(SefazEngine $sefaz)
    {
        $tarefa = Tarefa::find($this->tarefaId);
        if (! $tarefa) {
            return;
        }

        $tarefa->update(['status' => 'processando']);

        $empresa = Empresa::find($this->empresaId);

        $logContent = "LOG DE CONSULTA SEFAZ RETROATIVA\n";
        $logContent .= 'Data: '.Carbon::now()->format('d/m/Y H:i:s')."\n";
        $logContent .= "Empresa: {$empresa->nome} (ID: {$empresa->id})\n";
        $logContent .= "CNPJ: {$empresa->cnpj}\n";
        $logContent .= "Período: {$this->dataDe} até {$this->dataAte}\n";
        $logContent .= str_repeat('-', 50)."\n\n";

        $sucesso = 0;
        $falha = 0;
        $erros = [];
        $nfeProcessadas = 0;

        try {
            $dataDe = Carbon::parse($this->dataDe);
            $dataAte = Carbon::parse($this->dataAte);

            $result = $sefaz->buscarPorPeriodo($empresa, $dataDe, $dataAte);

            $nfeProcessadas = $result['count'] ?? 0;
            $sucesso = 1;

            $logContent .= "[OK] Consulta retroativa realizada com sucesso\n";
            $logContent .= "NF-es processadas: {$nfeProcessadas}\n";
            $logContent .= "Período consultado: {$this->dataDe} até {$this->dataAte}\n";

        } catch (\Exception $e) {
            $falha = 1;
            $erroMsg = $e->getMessage();
            $erros[] = $erroMsg;

            $logContent .= "[ERRO] {$erroMsg}\n";

            Log::error('Erro na consulta SEFAZ retroativa: '.$e->getMessage());
        }

        $logContent .= "\n".str_repeat('-', 50)."\n";
        $logContent .= "RESUMO\n";
        $logContent .= "Sucesso: {$sucesso}\n";
        $logContent .= "Falhas: {$falha}\n";
        $logContent .= "NF-es Processadas: {$nfeProcessadas}\n";
        $logContent .= 'Data término: '.Carbon::now()->format('d/m/Y H:i:s')."\n";

        $logFileName = 'consulta_sefaz_retroativa_'.$empresa->id.'_'.time().'.log';
        $logPath = 'logs/sefaz/'.$logFileName;

        if (! Storage::exists('logs/sefaz')) {
            Storage::makeDirectory('logs/sefaz');
        }
        Storage::put($logPath, $logContent);

        $tarefa->update([
            'status' => $falha > 0 ? 'concluido_com_erros' : 'concluido',
            'processado' => 1,
            'sucesso' => $sucesso,
            'falha' => $falha,
            'mensagem' => "NF-es: {$nfeProcessadas}, Período: {$this->dataDe} a {$this->dataAte}, Empresa: {$empresa->nome}",
            'resultado' => [
                'empresa_id' => $empresa->id,
                'empresa_nome' => $empresa->nome,
                'nfe_processadas' => $nfeProcessadas,
                'periodo_de' => $this->dataDe,
                'periodo_ate' => $this->dataAte,
                'erros' => array_slice($erros, 0, 50),
            ],
            'finished_at' => now(),
        ]);

        $this->criarNotificacao($sucesso, $falha, $nfeProcessadas, $empresa, $logPath, $logFileName);
    }

    protected function criarNotificacao(int $sucesso, int $falha, int $nfeProcessadas, Empresa $empresa, string $logPath, string $logFileName): void
    {
        $tipo = $falha > 0 ? 'warning' : 'success';
        $titulo = 'Consulta SEFAZ Retroativa Concluída';

        if ($falha > 0) {
            $mensagem = "Consulta retroativa para {$empresa->nome}: {$nfeProcessadas} NF-es. Houve erro. Verifique os detalhes.";
        } else {
            $mensagem = "Consulta retroativa para {$empresa->nome} concluída! {$nfeProcessadas} NF-es processadas.";
        }

        Notificacao::criar(
            $tipo,
            $titulo,
            $mensagem,
            route('fiscal.monitor'),
            [
                'tarefa_id' => $this->tarefaId,
                'log_path' => $logPath,
                'log_filename' => $logFileName,
                'empresa_id' => $empresa->id,
                'empresa_nome' => $empresa->nome,
                'nfe_processadas' => $nfeProcessadas,
            ],
            $this->userId
        );
    }

    public function failed(\Throwable $exception): void
    {
        $tarefa = Tarefa::find($this->tarefaId);
        if ($tarefa) {
            $tarefa->update([
                'status' => 'falhou',
                'mensagem' => 'Erro na consulta SEFAZ retroativa: '.$exception->getMessage(),
                'finished_at' => now(),
            ]);
        }

        $empresa = Empresa::find($this->empresaId);

        Notificacao::criar(
            'error',
            'Erro na Consulta SEFAZ Retroativa',
            'Ocorreu um erro ao consultar o SEFAZ retroativa para '.($empresa->nome ?? 'empresa desconhecida').': '.$exception->getMessage(),
            '/fiscal/monitor',
            ['tarefa_id' => $this->tarefaId],
            $this->userId
        );
    }
}
