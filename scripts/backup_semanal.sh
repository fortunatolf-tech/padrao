#!/bin/bash
# =============================================================================
# BACKUP SEMANAL INTEGRAL - SISTEMA DE VOTAÇÃO COMARA
# Executado via Cron no Debian 13 / aaPanel (sugerido: Domingos às 02:00)
# Retenção mínima obrigatória: 5 anos
# =============================================================================

set -e

# Configurações de Diretórios e Banco (Auto-detecta o diretório do projeto)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
BACKUP_DIR="${APP_DIR}/backups/weekly"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")

# Carrega variáveis de ambiente do .env se existir
if [ -f "${APP_DIR}/.env" ]; then
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

echo "[$(date)] Iniciando Backup Semanal Integral da COMARA..."

# 1. Dump Integral com Rotinas e Triggers
mysqldump -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" ${MYSQL_PWD_FLAG} \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --quick \
    --default-character-set=utf8mb4 \
    "${DB_NAME}" | gzip > "${BACKUP_DIR}/FULL_DB_COMARA_${TIMESTAMP}.sql.gz"

# 2. Cópia completa dos arquivos da aplicação e anexos
tar -czf "${BACKUP_DIR}/FULL_FILES_COMARA_${TIMESTAMP}.tar.gz" \
    --exclude="${APP_DIR}/backups" \
    --exclude="${APP_DIR}/cache" \
    -C "$(dirname "${APP_DIR}")" "$(basename "${APP_DIR}")"

# 3. Retenção de 5 anos (1825 dias)
find "${BACKUP_DIR}" -type f -name "FULL_*_*.gz" -mtime +1825 -delete

echo "[$(date)] Backup Semanal Integral concluído com sucesso em: ${BACKUP_DIR}"
