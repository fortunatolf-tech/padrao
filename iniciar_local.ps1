# Script de Inicialização do Ambiente Local Windows 11 (COMARA)
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "   SISTEMA DE ELEIÇÃO DOS PADRÕES COMARA - AMBIENTE LOCAL" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verifica se o Docker Desktop está aberto
Write-Host "[1/4] Verificando Docker Desktop..." -ForegroundColor Yellow
$dockerCheck = docker ps 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Docker Desktop não está em execução. Tentando iniciar o Docker Desktop..." -ForegroundColor Magenta
    $dockerPath = "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    if (Test-Path $dockerPath) {
        Start-Process $dockerPath
        Write-Host "Aguardando inicialização do Docker Desktop (pode levar 30-45 segundos)..." -ForegroundColor Yellow
        $tentativas = 0
        while ($tentativas -lt 25) {
            Start-Sleep -Seconds 3
            docker ps > $null 2>&1
            if ($LASTEXITCODE -eq 0) {
                break
            }
            $tentativas++
            Write-Host "." -NoNewline
        }
        Write-Host ""
    } else {
        Write-Host "ERRO: Docker Desktop não encontrado em $dockerPath." -ForegroundColor Red
        Write-Host "Por favor, abra o Docker Desktop manualmente pelo Menu Iniciar e execute este script novamente." -ForegroundColor Red
        Exit 1
    }
}

# 2. Sobe os containers da aplicação e do banco MySQL
Write-Host "[2/4] Construindo e iniciando containers (PHP 8.3 + MySQL 8.0)..." -ForegroundColor Yellow
docker compose up -d --build

if ($LASTEXITCODE -ne 0) {
    Write-Host "Falha ao iniciar os containers Docker." -ForegroundColor Red
    Exit 1
}

# 3. Aguarda o MySQL estar pronto e roda o import_efetivo.php
Write-Host "[3/4] Aguardando banco de dados inicializar e carregando efetivo da COMARA..." -ForegroundColor Yellow
Start-Sleep -Seconds 6

Write-Host "Executando carga de militares, civis e chefias (import_efetivo.php)..." -ForegroundColor Cyan
docker compose exec app php database/import_efetivo.php

# 4. Finalização e Abertura no Navegador
Write-Host ""
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "   AMBIENTE LOCAL PRONTO PARA TESTES NO WINDOWS 11!" -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "Acesse no navegador: http://localhost:8080/" -ForegroundColor White
Write-Host ""
Write-Host "Credenciais de Teste:" -ForegroundColor Yellow
Write-Host "  - Administrador:     admin             / padrao@2026" -ForegroundColor Cyan
Write-Host "  - Presidente:        presidente        / padrao@2026" -ForegroundColor Cyan
Write-Host "  - Direção Superior:  oficial.superior  / padrao@2026" -ForegroundColor Cyan
Write-Host "  - Educação Física:   ed.fisica         / padrao@2026" -ForegroundColor Cyan
Write-Host "  - Chefe Divisão:     chefe.dpc         / padrao@2026" -ForegroundColor Cyan
Write-Host "  - Chefe Direto:      chefe.direto      / padrao@2026" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Green
Write-Host ""

# Abrir no navegador padrão
Start-Process "http://localhost:8080/"
