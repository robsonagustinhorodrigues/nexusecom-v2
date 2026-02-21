#!/bin/bash
# ============================================================
# NexusEcom - Script de Importação de NF-es
# Executar no servidor onde estão os XMLs
# ============================================================

set -e

PROJECT_DIR="/home/robson/Documentos/projetos/nexusecom"
NFES_DIR="$HOME/Downloads/nfes/nfes"

echo "========================================"
echo " NexusEcom - Importação de NF-es"
echo " $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"

# -----------------------------------------------
# MAPEAMENTO CNPJ -> EMPRESA ID
# -----------------------------------------------
# Empresa 1 (MaxLider): 57297069000146
# Empresa 2 (LideraMais): 61778473000109  
# Empresa 3 (LideraMix): 47650333000120

# -----------------------------------------------
# EMPRESA 1 - MaxLider (57297069000146)
# -----------------------------------------------
if [ -d "$NFES_DIR/57297069000146" ]; then
    echo ""
    echo ">>> Importando NF-es da Empresa 1 (MaxLider)..."
    count=$(find "$NFES_DIR/57297069000146" -name "*.xml" 2>/dev/null | wc -l)
    echo "    Encontrados: $count XMLs"
    php $PROJECT_DIR/artisan importar:nfs 4 "$NFES_DIR/57297069000146" --chunk=50
else
    echo ""
    echo ">>> Pasta da Empresa 1 não encontrada"
fi

# -----------------------------------------------
# EMPRESA 2 - LideraMais (61778473000109)
# -----------------------------------------------
if [ -d "$NFES_DIR/61778473000109" ]; then
    echo ""
    echo ">>> Importando NF-es da Empresa 2 (LideraMais)..."
    count=$(find "$NFES_DIR/61778473000109" -name "*.xml" 2>/dev/null | wc -l)
    echo "    Encontrados: $count XMLs"
    php $PROJECT_DIR/artisan importar:nfs 5 "$NFES_DIR/61778473000109" --chunk=50
else
    echo ""
    echo ">>> Pasta da Empresa 2 não encontrada"
fi

# -----------------------------------------------
# EMPRESA 3 - LideraMix (47650333000120)
# -----------------------------------------------
if [ -d "$NFES_DIR/47650333000120" ]; then
    echo ""
    echo ">>> Importando NF-es da Empresa 3 (LideraMix)..."
    count=$(find "$NFES_DIR/47650333000120" -name "*.xml" 2>/dev/null | wc -l)
    echo "    Encontrados: $count XMLs"
    php $PROJECT_DIR/artisan importar:nfs 6 "$NFES_DIR/47650333000120" --chunk=50
else
    echo ""
    echo ">>> Pasta da Empresa 3 não encontrada"
fi

echo ""
echo "========================================"
echo " ✅ Importação concluída!"
echo "========================================"
