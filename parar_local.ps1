# Script para parar o ambiente local
Write-Host "Parando containers do Sistema de Votação..." -ForegroundColor Yellow
docker compose down
Write-Host "Ambiente local finalizado com sucesso." -ForegroundColor Green
