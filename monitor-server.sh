#!/bin/bash

# NexusEcom Auto-Restart Script
# Keep Laravel server running

PROJECT_DIR="/home/robson/Documentos/projetos/nexusecom"
PORT=8000

echo "Starting NexusEcom auto-restart monitor..."

while true; do
    # Check if server is running
    if ! lsof -i :$PORT > /dev/null 2>&1; then
        echo "[$(date)] Server not running, starting..."
        cd "$PROJECT_DIR"
        php artisan serve --host=0.0.0.0 --port=$PORT > /dev/null 2>&1 &
        sleep 2
    fi
    
    # Wait 10 seconds before checking again
    sleep 10
done
