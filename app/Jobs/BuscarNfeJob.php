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

class BuscarNfeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    protected int $empresaId;

    protected int $userId;

    protected int $tarefaId;

    public function __construct(int $empresaId, int $userId)
    {
        $this->empresaId = $empresaId;
        $this->userId = $userId;

        $tarefa = Tarefa::criar('busca_sefaz', 1, $this->empresaId, $this->userId);
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

        $logContent = "LOG DE CONSULTA SEFAZ\n";
        $logContent .= 'Data: '.Carbon::now()->format('d/m/Y H:i:s')."\n";
        $logContent .= "Empresa: {$empresa->nome} (ID: {$empresa->id})\n";
        $logContent .= "CNPJ: {$empresa->cnpj}\n";
        $logContent .= str_repeat('-', 50)."\n\n";

        $sucesso = 0;
        $falha = 0;
        $erros = [];
        $nfeProcessadas = 0;

        try {
            $result = $sefaz->buscarNovasNotas($empresa);

            $nfeProcessadas = $result['count'] ?? 0;
            $sucesso = 1;

            $logContent .= "[OK] Consulta realizada com sucesso\n";
            $logContent .= "NF-es processadas: {$nfeProcessadas}\n";
            $logContent .= 'NSU Final: '.($result['maxNsu'] ?? 'N/A')."\n";

        } catch (\Exception $e) {
            $falha = 1;
            $erroMsg = $e->getMessage();
            $erros[] = $erroMsg;

            $logContent .= "[ERRO] {$erroMsg}\n";

            Log::error('Erro na consulta SEFAZ: '.$e->getMessage());
        }

        $logContent .= "\n".str_repeat('-', 50)."\n";
        $logContent .= "RESUMO\n";
        $logContent .= "Sucesso: {$sucesso}\n";
        $logContent .= "Falhas: {$falha}\n";
        $logContent .= "NF-es Processadas: {$nfeProcessadas}\n";
        $logContent .= 'Data término: '.Carbon::now()->format('d/m/Y H:i:s')."\n";

        $logFileName = 'consulta_sefaz_'.$empresa->id.'_'.time().'.log';
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
            'mensagem' => "NF-es: {$nfeProcessadas}, Empresa: {$empresa->nome}",
            'resultado' => [
                'empresa_id' => $empresa->id,
                'empresa_nome' => $empresa->nome,
                'nfe_processadas' => $nfeProcessadas,
                'erros' => array_slice($erros, 0, 50),
            ],
            'finished_at' => now(),
        ]);

        $this->criarNotificacao($sucesso, $falha, $nfeProcessadas, $empresa, $logPath, $logFileName);
    }

    protected function criarNotificacao(int $sucesso, int $falha, int $nfeProcessadas, Empresa $empresa, string $logPath, string $logFileName): void
    {
        $tipo = $falha > 0 ? 'warning' : 'success';
        $titulo = 'Consulta SEFAZ Concluída';

        if ($falha > 0) {
            $mensagem = "Consulta para {$empresa->nome}: {$nfeProcessadas} NF-es processadas. Houve erro. Verifique os detalhes.";
        } else {
            $mensagem = "Consulta para {$empresa->nome} concluída! {$nfeProcessadas} NF-es processadas.";
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
                'mensagem' => 'Erro na consulta SEFAZ: '.$exception->getMessage(),
                'finished_at' => now(),
            ]);
        }

        $empresa = Empresa::find($this->empresaId);

        Notificacao::criar(
            'error',
            'Erro na Consulta SEFAZ',
            'Ocorreu um erro ao consultar o SEFAZ para '.($empresa->nome ?? 'empresa desconhecida').': '.$exception->getMessage(),
            '/fiscal/monitor',
            ['tarefa_id' => $this->tarefaId],
            $this->userId
        );
    }
}
