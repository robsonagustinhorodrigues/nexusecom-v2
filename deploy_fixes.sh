#!/bin/bash
# ============================================================
# NexusEcom - Script de Deploy e Correções
# Executar no servidor remoto (177.170.10.220)
# ============================================================

set -e

PROJECT_DIR="/home/robson/Documentos/projetos/nexusecom"
cd "$PROJECT_DIR"

echo "========================================"
echo " NexusEcom - Deploy de Correções"
echo " $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"

# -----------------------------------------------
# 1. VERIFICAR/RODAR MIGRATIONS PENDENTES
# -----------------------------------------------
echo ""
echo ">>> [1/6] Verificando migrations pendentes..."
php artisan migrate --force
echo "✅ Migrations OK"

# -----------------------------------------------
# 2. VERIFICAR COLUNA tipo NA TABELA products
# -----------------------------------------------
echo ""
echo ">>> [2/6] Verificando schema da tabela products..."
php artisan tinker --execute="
    \$col = DB::select(\"SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name = 'products' AND column_name = 'tipo'\");
    echo 'Coluna tipo: ' . json_encode(\$col) . PHP_EOL;
    if (!empty(\$col) && \$col[0]->character_maximum_length >= 20) {
        echo '✅ Coluna tipo OK (varchar >= 20)' . PHP_EOL;
    } else {
        echo '⚠️  Coluna tipo precisa de ajuste! Rodando ALTER...' . PHP_EOL;
        DB::statement(\"ALTER TABLE products ALTER COLUMN tipo TYPE varchar(20)\");
        echo '✅ Coluna tipo corrigida' . PHP_EOL;
    }
"

# -----------------------------------------------
# 3. LIMPAR CACHE
# -----------------------------------------------
echo ""
echo ">>> [3/6] Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo "✅ Caches limpos"

# -----------------------------------------------
# 4. REPROCESSAR NF-e (POR SEGMENTOS)
# -----------------------------------------------
echo ""
echo ">>> [4/6] Reprocessando NF-e..."

# Empresa 3 (~600 faltando)
echo "  → Empresa 3 (completando processamento)..."
php artisan reprocessar:nfs 3 --chunk=500

# Empresa 2 (1 recebida)
echo "  → Empresa 2..."
php artisan reprocessar:nfs 2 --chunk=500

# Empresa 1 (24217 recebidas - em segmentos de 5000)
echo "  → Empresa 1 (24217 notas, processando em segmentos)..."
for OFFSET in $(seq 0 5000 25000); do
    echo "    Segmento offset=$OFFSET limit=5000..."
    php artisan reprocessar:nfs 1 --chunk=500 --offset=$OFFSET --limit=5000
    echo "    Pausa de 2s..."
    sleep 2
done

echo "✅ Reprocessamento NF-e concluído"

# -----------------------------------------------
# 5. SYNC BLING PRODUTOS
# -----------------------------------------------
echo ""
echo ">>> [5/6] Sincronizando produtos do Bling..."

# Descomente a linha da empresa que tem Bling:
# php artisan bling:sync-produtos 1 --limit=100
# php artisan bling:sync-produtos 2 --limit=100
php artisan bling:sync-produtos 3 --limit=100

echo "⚠️  Bling sync comentado. Descomente a empresa correta e rode novamente."

# -----------------------------------------------
# 6. GIT COMMIT
# -----------------------------------------------
echo ""
echo ">>> [6/6] Git status..."
git status --short
echo ""
echo "Para commitar, rode:"
echo "  git add -A"
echo "  git commit -m 'fix: corrige reprocessamento NF-e, fillable Product, fotos Amazon'"
echo "  git push origin main"

echo ""
echo "========================================"
echo " ✅ Script finalizado!"
echo "========================================"
