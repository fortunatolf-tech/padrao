#!/bin/bash
# =============================================================================
# BACKUP DIÁRIO INCREMENTAL - SISTEMA DE VOTAÇÃO COMARA
# Executado via Cron no Debian 13 / aaPanel (sugerido: diariamente às 23:00)
# =============================================================================

set -e

# Configurações de Diretórios e Banco (Auto-detecta o diretório do projeto)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
BACKUP_DIR="${APP_DIR}/backups/daily"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")

# Carrega variáveis de ambiente do .env se existir
if [ -f "${APP_DIR}/.env" ]; then
    # Exporta variáveis ignorando comentários e linhas vazias
    eval $(grep -v '^#' "${APP_DIR}/.env" | grep '=' | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' | sed 's/^/export /')
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-comara_votacao}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

MYSQL_PWD_FLAG=""
if [ -n "${DB_PASS}" ]; then
    MYSQL_PWD_FLAG="-p${DB_PASS}"
fi

mkdir -p "${BACKUP_DIR}"

echo "[$(date)] Iniciando Backup Diário da COMARA..."

# 1. Dump do Banco de Dados compactado com gzip
mysqldump -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" ${MYSQL_PWD_FLAG} \
    --single-transaction \
    --quick \
    --default-character-set=utf8mb4 \
    "${DB_NAME}" | gzip > "${BACKUP_DIR}/db_comara_daily_${TIMESTAMP}.sql.gz"

# 2. Sincronização incremental das fotografias manuais
mkdir -p "${APP_DIR}/public/uploads/fotos"
tar -czf "${BACKUP_DIR}/fotos_comara_daily_${TIMESTAMP}.tar.gz" -C "${APP_DIR}/public" uploads/fotos

# 3. Rotação: Manter últimos 30 dias de backups diários
find "${BACKUP_DIR}" -type f -name "*_daily_*.gz" -mtime +30 -delete

echo "[$(date)] Backup Diário concluído com sucesso em: ${BACKUP_DIR}"
