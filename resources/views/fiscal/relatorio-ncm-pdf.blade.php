<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Vendas por NCM - {{ $empresa->nome }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
            color: #000;
        }
        .header p {
            margin: 2px 0;
            color: #666;
        }
        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 10px;
        }
        .card {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .card .label {
            font-size: 9px;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 5px;
            display: block;
        }
        .card .value {
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }
        .card.highlight {
            background-color: #f8fafc;
            border-color: #3b82f6;
        }
        .card.highlight .value {
            color: #2563eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 8px;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Vendas por NCM</h1>
        <p><strong>Empresa:</strong> {{ $empresa->nome }} ({{ $empresa->cnpj }})</p>
        <p><strong>Período:</strong> {{ \Carbon\Carbon::parse($request->data_inicial)->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($request->data_final)->format('d/m/Y') }}</p>
        <p>Gerado em: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="summary-cards">
        <div class="card">
            <span class="label">Total de Vendas</span>
            <span class="value">R$ {{ number_format($summary['venda_total'], 2, ',', '.') }}</span>
        </div>
        <div class="card">
            <span class="label">Total de Compras</span>
            <span class="value">R$ {{ number_format($summary['compra_total'], 2, ',', '.') }}</span>
        </div>
        <div class="card">
            <span class="label">Total Canceladas</span>
            <span class="value">R$ {{ number_format($summary['cancelada_total'], 2, ',', '.') }}</span>
        </div>
        <div class="card">
            <span class="label">Total Devolvidas</span>
            <span class="value">R$ {{ number_format($summary['devolvida_total'], 2, ',', '.') }}</span>
        </div>
        <div class="card highlight">
            <span class="label">TOTAL LÍQUIDO</span>
            <span class="value">R$ {{ number_format($summary['liquido_total'], 2, ',', '.') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>NCM</th>
                <th class="text-right">Vendas</th>
                <th class="text-right">Compras</th>
                <th class="text-right">Canceladas</th>
                <th class="text-right">Devolvidas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultados as $row)
            <tr>
                <td>{{ $row->ncm }}</td>
                <td class="text-right">R$ {{ number_format($row->total_venda, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($row->total_compra, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($row->total_cancelada, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($row->total_devolvida, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Relatório gerado pelo sistema NexusEcom em {{ now()->format('d/m/Y H:i:s') }}
        <div class="no-print" style="margin-top: 10px;">
            <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #3b82f6; color: white; border: none; border-radius: 4px;">Imprimir PDF</button>
        </div>
    </div>

    <script>
        // Auto trigger print dialog
        window.onload = function() {
            // Uncomment to auto-print
            // window.print();
        };
    </script>
</body>
</html>
