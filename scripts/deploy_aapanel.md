# Guia Completo de Instalação e Deploy no aaPanel (Debian 13)

Este guia orienta a implantação oficial do **Sistema de Eleição dos Padrões COMARA** em servidor Linux **Debian 13** utilizando o painel de controle **aaPanel** com **PHP 8.3** e **MySQL 8.0+ / MariaDB 10.11+**.

---

## 1. Pré-Requisitos no Servidor Debian 13

No terminal do servidor Debian 13, garanta a atualização dos pacotes:

```bash
apt update && apt upgrade -y
apt install -y git curl wget unzip libldap-common
```

---

## 2. Configuração do Ambiente no aaPanel

Acesse o painel web do **aaPanel** (`http://ip-do-servidor:8888`):

### 2.1. Instalação das Versões Recomendadas
No menu **App Store**:
1. Instale o **Nginx** (versão 1.24+ ou mais recente).
2. Instale o **PHP 8.3**.
3. Instale o **MySQL 8.0** ou **MariaDB 10.11**.

### 2.2. Extensões Obrigatórias do PHP 8.3
No menu **App Store -> PHP 8.3 -> Setting -> Install extensions**, instale:
- `ldap` (Integração com Active Directory INTRAER)
- `fileinfo` (Detecção de tipos MIME de fotografias)
- `curl` (Integração com API SIGPES CCARJ)
- `gd` (Processamento de fotos)
- `zip` e `zlib` (Geração de backups compactados)

---

## 3. Criação do Banco de Dados no aaPanel

1. Acesse o menu **Databases -> Add Database**.
2. **Database Name**: `comara_votacao`
3. **DB Type**: `MySQL`
4. **Character Set**: `utf8mb4`
5. **Collation**: `utf8mb4_unicode_ci`
6. Anote o usuário e a senha gerados.

---

## 4. Publicação dos Arquivos da Aplicação

1. Crie o diretório do site no menu **Website -> Add Site**:
   - **Domain Name**: `votacao.comara.intraer` (ou o IP/hostname desejado)
   - **Root Directory**: `/www/wwwroot/votacao.comara.intraer`
   - **PHP Version**: `PHP-83`

2. Copie ou clone os arquivos do projeto para `/www/wwwroot/votacao.comara.intraer`.

3. **Configuração Fundamental do Diretório Raiz (Document Root)**:
   - No aaPanel, clique sobre o site criado -> **Site directory**.
   - Altere o campo **Running directory** de `/` para:
     ```
     /public
     ```
   - Clique em **Save**. Isso garante a segurança total do sistema, impedindo o acesso público direto aos diretórios `config`, `database`, `src`, `views`, `backups` e `cache`.

4. **Ajuste de Permissões**:
   Execute no terminal do servidor:
   ```bash
   cd /www/wwwroot/votacao.comara.intraer
   chown -R www:www .
   chmod -R 755 .
   chmod -R 775 cache public/uploads/fotos backups
   chmod +x scripts/*.sh
   ```

---

## 5. Configuração das Variáveis e Banco de Dados

Edite o arquivo `/www/wwwroot/votacao.comara.intraer/config/config.php` (ou defina variáveis de ambiente no Nginx fastcgi):

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'comara_votacao');
define('DB_USER', 'comara_user');
define('DB_PASS', 'sua_senha_do_aapanel');

// API SIGPES da FAB:
// Para Homologação: http://api.servicos.homolog.ccarj.intraer/sigpesApi
// Para Produção: http://api.servicos.ccarj.intraer/sigpesApi
define('SIGPES_API_BASE', 'http://api.servicos.homolog.ccarj.intraer/sigpesApi');
```

---

## 6. Carga Inicial do Banco de Dados e Efetivo

No terminal do servidor, execute o script de migração oficial:

```bash
cd /www/wwwroot/votacao.comara.intraer
php database/import_efetivo.php
```

A saída confirmará:
- Criação das 12 tabelas com integridade referencial e urna cega.
- Inserção das 5 Divisões e 7 Assessorias da COMARA (ROCA 21-55).
- Criação do Pleito de referência 2026.
- Carga de militares e civis do efetivo COMARA segmentados nas 4 categorias.
- Criação das contas padrão de demonstração e contingência.

---

## 7. Configuração dos Backups Automatizados no aaPanel (Cron Tab)

No menu **Cron** do aaPanel, crie duas tarefas do tipo **Shell Script**:

### Tarefa 1: Backup Diário Incremental (23:00)
- **Name**: `COMARA - Backup Diario Incremental`
- **Cycle**: `Daily` às `23:00`
- **Script content**:
  ```bash
  /bin/bash /www/wwwroot/votacao.comara.intraer/scripts/backup_diario.sh
  ```

### Tarefa 2: Backup Semanal Completo (Domingo 02:00 - Retenção 5 Anos)
- **Name**: `COMARA - Backup Semanal Completo 5 Anos`
- **Cycle**: `Nth Day of Week` -> `Sunday` às `02:00`
- **Script content**:
  ```bash
  /bin/bash /www/wwwroot/votacao.comara.intraer/scripts/backup_semanal.sh
  ```

---

## 8. Monitoramento de Disponibilidade 24h (SLA 99,9%)

No menu **Website -> Site Monitor** do aaPanel, cadastre um monitor HTTP:
- **URL**: `http://votacao.comara.intraer/scripts/healthcheck.php`
- **Frequency**: A cada 5 minutos
- **Expected Return**: HTTP `200` e JSON com `"status": "HEALTHY"`
