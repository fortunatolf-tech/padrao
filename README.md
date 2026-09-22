# Sistema de Eleição dos Padrões COMARA

**Comissão de Aeroportos da Região Amazônica (COMARA)**  
Sistema Web Seguro para Condução do Processo de Escolha dos Militares e Servidores Civis Padrão.

---

## 📌 Visão Geral do Sistema

O sistema informatiza todo o rito regimental da COMARA para seleção dos destaques anuais nas 4 categorias oficiais:
1. **Graduado Padrão** (Suboficiais e Sargentos)
2. **Praça Padrão** (Cabos e Soldados)
3. **Servidor Público Federal Permanente (SPPF)**
4. **Servidor Público Temporário / Terceirizado (SPTF)**

O processo obedece rigorosamente às 3 fases estatutárias:
- **Fase 1 — Avaliação Qualitativa pelos Oficiais e TACF**: Oficiais avaliam atributos regulamentares e a Comissão de Educação Física lança as notas do Teste de Aptidão e Condicionamento Físico.
- **Fase 2 — Indicação das Chefias**: Chefes das 5 Divisões (DPC, DIVOP, DIENG, DIAF, DLOG) e 3 Assessorias (ASJUR, ACI, ASCOM) indicam obrigatoriamente 6 candidatos por categoria com base nas médias da Fase 1.
- **Fase 3 — Votação Geral Secreta e Direta**: Todo o efetivo habilitado (oficiais, graduados, praças e civis) vota anonimamente através de cédula eletrônica protegida por Criptografia Cega (*Blind Token Signature*). Em caso de empate, o Vice-Presidente da COMARA exerce o voto de minerva.

---

## 🚀 Funcionalidades Principais

- **Autenticação Flexível e Segura**:
  - Login transparente com **SARAM** ou **CPF** (com ou sem pontuação).
  - Autenticação corporativa integrada ao **Active Directory / LDAP da INTRAER** com fallback local seguro para contingência.
  - Troca de senha pelo próprio usuário autenticado (recomendada obrigatoriamente no primeiro acesso); redefinição e reset de senhas restritos com exclusividade ao **Administrador / Setor de TI (STI)** para garantia de segurança e inviolabilidade.
- **Gestão Completa de Efetivo e Administradores**:
  - Base inicial oficial com **443 membros** já cadastrados com postos, quadros, especialidades e lotações.
  - O Administrador pode conceder ou revogar o perfil de **Administrador (`ADMIN`)** para qualquer membro do efetivo com 1 clique.
  - Integração com a API do **SIGPES CCARJ** para download automático das fotografias oficiais em alta resolução.
- **Controle Total do Pleito**:
  - Painel de controle de fases com transição assistida.
  - Botão de **Reinicialização do Pleito** com dupla confirmação e registro em log de auditoria.
  - Apuração em tempo real com gráficos, cálculo automático de empates e emissão de ata regimental pronta para publicação em Boletim Interno.
- **Backups e Auditoria**:
  - Scripts prontos para backups diários incrementais e backups semanais integrais com retenção obrigatória de 5 anos.
  - Trilha de auditoria completa em conformidade com as normas da FAB.

---

## 🛠️ Requisitos Técnicos

- **PHP**: 8.2 ou 8.3 (extensões: `pdo_mysql`, `curl`, `mbstring`, `gd`, `zip`, `ldap`)
- **Banco de Dados**: MySQL 8.0+ ou MariaDB 10.6+
- **Servidor Web**: Apache 2.4+ (com `mod_rewrite`) ou Nginx 1.20+
- **Ambiente de Hospedagem**: aaPanel no Debian 12/13, Servidores Linux dedicados ou Docker.

---

## ⚡ Instalação Rápida Local (Docker)

Se você utiliza Docker no Windows, Linux ou macOS:

1. Clone o repositório:
   ```bash
   git clone https://github.com/fortunatolf-tech/padrao.git
   cd padrao
   ```

2. Suba os contêineres:
   ```bash
   docker compose up -d
   ```

3. Execute o instalador automático do banco de dados:
   ```bash
   docker exec -it votacao_comara_app php /var/www/html/database/instalar.php
   ```

4. Acesse no navegador:
   - URL: `http://localhost:8080`
   - Login inicial: `admin` ou seu SARAM/CPF
   - Senha padrão: `padrao@2026`

---

## 🌐 Deploy em Produção (aaPanel / Debian)

Consulte o manual completo com fotos e comandos passo a passo em:  
👉 **[DEPLOY_TUTORIAL.md](DEPLOY_TUTORIAL.md)**

Resumo dos comandos no servidor Linux / aaPanel:
```bash
cd /www/wwwroot/padrao
cp .env.example .env
# Edite as credenciais do banco no .env:
nano .env
# Instale a base de dados em 1 comando:
php database/instalar.php
```

---

## 🔐 Credenciais Padrão Iniciais

| Usuário / Login | Senha Inicial | Perfil | Descrição |
| :--- | :--- | :--- | :--- |
| `admin` | `padrao@2026` | `ADMIN` | Administrador Geral do Sistema |
| SARAM ou CPF do militar | `padrao@2026` | `ELEITOR` / `CHEFE` | Usuários do efetivo cadastrado |

> **IMPORTANTE**: Após o primeiro acesso com o usuário `admin`, altere a senha na barra superior clicando no seu nome de usuário.

---

## 📂 Estrutura do Projeto

```
padrao/
├── config/             # Configurações do sistema, constantes, conexão BD e LDAP
│   ├── config.php      # Loader do .env e variáveis globais
│   ├── database.php    # Conexão PDO Singleton
│   └── ldap.php        # Autenticação Active Directory e fallback local
├── database/           # Scripts SQL e migrações
│   ├── carga_inicial_443.sql # Dump oficial com 443 membros e estrutura
│   └── instalar.php    # Instalador automático CLI / Web
├── docker/             # Dockerfile e configurações de contêineres
├── public/             # Raiz pública do servidor web
│   ├── css/            # Estilos personalizados FAB / COMARA
│   ├── js/             # Scripts interativos (Fase 1, Fase 2, Urna Fase 3)
│   └── uploads/        # Diretório de fotos dos candidatos
├── scripts/            # Scripts de backup e manutenção (Cron)
│   ├── backup_diario.sh
│   └── backup_semanal.sh
├── src/                # Controladores, Modelos, Middleware e Serviços
│   ├── Auth/           # Gerenciador de Sessão e Criptografia
│   ├── Controllers/    # Lógica de negócio das telas e APIs
│   ├── Middleware/     # Proteção de rotas e perfis
│   ├── Models/         # Interação com o banco de dados
│   └── Services/       # Integração SIGPES CCARJ e regras especiais
└── views/              # Interfaces do usuário em PHP / Bootstrap 5
```

---

## 📜 Licença e Propriedade

Desenvolvido para uso exclusivo da **Comissão de Aeroportos da Região Amazônica (COMARA)** — Comando da Aeronáutica — Ministério da Defesa do Brasil.
