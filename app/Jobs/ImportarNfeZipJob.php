<?php

namespace App\Jobs;

use App\Models\Notificacao;
use App\Models\Tarefa;
use App\Services\FiscalService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportarNfeZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    protected int $empresaId;

    protected int $userId;

    protected string $tipo;

    protected $files;

    protected int $tarefaId;

    public function __construct(int $empresaId, int $userId, string $tipo, array $files)
    {
        $this->empresaId = $empresaId;
        $this->userId = $userId;
        $this->tipo = $tipo;
        $this->files = $files;

        $tarefa = Tarefa::criar($this->tipo, count($this->files), $this->empresaId, $this->userId);
        $this->tarefaId = $tarefa->id;
    }

    public function handle(FiscalService $service): void
    {
        $tarefa = Tarefa::find($this->tarefaId);
        if (! $tarefa) {
            return;
        }

        $tarefa->update(['status' => 'processando']);

        $sucesso = 0;
        $falha = 0;
        $erros = [];
        $logContent = "LOG DE IMPORTAÇÃO DE NF-e\n";
        $logContent .= 'Data: '.Carbon::now()->format('d/m/Y H:i:s')."\n";
        $logContent .= 'Total de arquivos: '.count($this->files)."\n";
        $logContent .= str_repeat('-', 50)."\n\n";

        $total = count($this->files);

        foreach ($this->files as $index => $item) {
            $nomeArquivo = $item['name'] ?? 'arquivo_desconhecido';

            try {
                $resultado = false;

                if ($item['type'] === 'xml') {
                    $resultado = $service->importXml($item['content'], $this->empresaId, $nomeArquivo);
                } elseif ($item['type'] === '7z_content') {
                    $resultado = $service->import7zSingle($item['path'], $this->empresaId, $item['index'], $nomeArquivo);
                } elseif ($item['type'] === 'zip_content') {
                    $resultado = $service->importZipSingle($item['path'], $this->empresaId, $item['index'], $nomeArquivo);
                }

                if ($resultado) {
                    $sucesso++;
                    $logContent .= "[OK] {$nomeArquivo}\n";
                } else {
                    $falha++;
                    $erroMsg = 'Falha na importação';
                    $erros[] = "{$nomeArquivo}: {$erroMsg}";
                    $logContent .= "[ERRO] {$nomeArquivo} - {$erroMsg}\n";
                }
            } catch (\Exception $e) {
                $falha++;
                $erroMsg = $e->getMessage();
                $erros[] = "{$nomeArquivo}: {$erroMsg}";
                $logContent .= "[ERRO] {$nomeArquivo} - {$erroMsg}\n";
            }

            $tarefa->atualizarProgresso($index + 1, $sucesso, $falha);
        }

        $logContent .= "\n".str_repeat('-', 50)."\n";
        $logContent .= "RESUMO\n";
        $logContent .= "Sucesso: {$sucesso}\n";
        $logContent .= "Falhas: {$falha}\n";
        $logContent .= 'Data término: '.Carbon::now()->format('d/m/Y H:i:s')."\n";

        $logFileName = 'importacao_nfe_'.$this->tarefaId.'_'.time().'.log';
        $logPath = 'logs/importacao/'.$logFileName;

        try {
            if (! Storage::exists('logs/importacao')) {
                Storage::makeDirectory('logs/importacao');
            }
            Storage::put($logPath, $logContent);
        } catch (\Exception $e) {
            Log::error('Erro ao salvar log de importação: '.$e->getMessage());
        }

        try {
            $tarefa->delete();
        } catch (\Exception $e) {
            Log::error('Erro ao excluir tarefa: '.$e->getMessage());
        }

        try {
            $this->criarNotificacaoComLog($sucesso, $falha, $erros, $logPath, $logFileName);
        } catch (\Exception $e) {
            Log::error('Erro ao criar notificação: '.$e->getMessage());
        }
    }

    protected function criarNotificacaoComLog(int $sucesso, int $falha, array $erros, string $logPath, string $logFileName): void
    {
        $tipo = $falha > 0 ? 'warning' : 'success';
        $titulo = 'Importação NF-e Concluída';

        if ($falha > 0) {
            $mensagem = "Processados: {$sucesso} com sucesso, {$falha} com falha. Clique para ver os detalhes.";
            $detalhes = "\n\nErros:\n".implode("\n", array_slice($erros, 0, 10));
            if (count($erros) > 10) {
                $detalhes .= "\n... e mais ".(count($erros) - 10).' erros. Verifique o log.';
            }
            $mensagem .= $detalhes;
        } else {
            $mensagem = "Importação concluída com sucesso! {$sucesso} NF(s) processada(s).";
        }

        Notificacao::criar(
            $tipo,
            $titulo,
            $mensagem,
            route('fiscal.monitor'),
            [
                'tarefa_id' => null,
                'log_path' => $logPath,
                'log_filename' => $logFileName,
                'sucesso' => $sucesso,
                'falha' => $falha,
            ],
            $this->userId
        );
    }

    public function failed(\Throwable $exception): void
    {
        $tarefa = Tarefa::find($this->tarefaId);
        if ($tarefa) {
            $tarefa->delete();
        }

        Notificacao::criar(
            'error',
            'Erro na Importação NF-e',
            'Ocorreu um erro ao processar os arquivos: '.$exception->getMessage(),
            '/fiscal/monitor',
            ['tarefa_id' => null],
            $this->userId
        );
    }
}
