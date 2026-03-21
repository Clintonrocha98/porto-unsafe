# porto-unsafe

> Scraper de folha de pagamento pública da Prefeitura de Porto Seguro com painel administrativo para visualização, pesquisa e exportação dos dados.

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-5-FDBA74?logo=laravel&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-8-DC382D?logo=redis&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-4-38BDF8?logo=tailwindcss&logoColor=white)

---

## Visão Geral

O **porto-unsafe** coleta dados da folha de pagamento pública de entidades municipais através do portal de transparência [transparencia.fatorsistemas.com.br](https://transparencia.fatorsistemas.com.br). O processo é assíncrono, baseado em filas, e os dados coletados ficam disponíveis em um painel administrativo com suporte a busca, ordenação e exportação.

A origem do nome refere-se à cidade de **Porto Seguro** e ao caráter de extração não-oficial dos dados (`unsafe`), que são públicos mas acessados via scraping HTML.

---

## Fonte dos Dados

| Atributo | Valor |
|---|---|
| Portal | Transparência Fator Sistemas |
| URL base | `https://transparencia.fatorsistemas.com.br/dados/carregaFolha.php` |
| Entidade padrão | `pm_portoseguro` (Prefeitura Municipal de Porto Seguro) |
| Regimes coletados | 3 regimes por período (CLT, Estatutário e outros) |
| Período selecionável | Mês e ano, com janela de 5 anos |

Os dados são públicos e dizem respeito a servidores municipais ativos e inativos, incluindo informações salariais e funcionais.

---

## Dados Extraídos

Cada registro de folha de pagamento contém os seguintes campos:

| Campo | Tipo | Descrição |
|---|---|---|
| `entity` | string | Identificador da entidade (ex: `pm_portoseguro`) |
| `registration` | string | Matrícula do servidor |
| `name` | string | Nome completo |
| `role` | string | Cargo/função |
| `admission_date` | date | Data de admissão |
| `resignation_date` | date | Data de exoneração/demissão |
| `employment_regime` | string | Regime de emprego (CLT, Estatutário, etc.) |
| `workplace` | string | Local de trabalho/lotação |
| `workload_hours` | integer | Carga horária semanal (horas) |
| `base_salary` | decimal | Salário base (R$) |
| `allowances` | decimal | Vantagens e adicionais (R$) |
| `deductions` | decimal | Descontos (R$) |
| `taxes` | decimal | Impostos retidos (R$) |
| `net_salary` | decimal | Salário líquido (R$) |
| `month` | integer | Mês de referência |
| `year` | integer | Ano de referência |

A chave única de cada registro é composta por `(entity, registration, role, month, year)`, permitindo upserts seguros para re-execuções.

---

## Arquitetura

```
┌─────────────────────────────────────────────────────────┐
│                 Filament Admin Panel                    │
│ /admin (autenticação obrigatória)                       │
│                                                         │
│  ┌──────────────────────┐  ┌──────────────────────────┐ │
│  │   PayrollResource    │  │   ListPayrolls (Page)    │ │
│  │   (tabela, busca)    │  │  ┌────────────────────┐  │ │
│  │                      │  │  │ Ação: Iniciar      │  │ │
│  │                      │  │  │ Extração           │  │ │
│  │                      │  │  ├────────────────────┤  │ │
│  │                      │  │  │ Ação: Histórico    │  │ │
│  │                      │  │  ├────────────────────┤  │ │
│  │                      │  │  │ Ação: Exportar     │  │ │
│  │                      │  │  └────────────────────┘  │ │
│  └──────────────────────┘  └──────────────────────────┘ │
└──────────────────────────────┬──────────────────────────┘
                               │ Bus::batch()
                               ▼
┌─────────────────────────────────────────────────────────┐
│                 Laravel Queue (Redis)                  │
│                                                         │
│  ProcessPayrollScrapeJob  ──►  SavePayrollRecordJob     │
│  (por regime: 1, 2, 3)        (por registro)            │
└──────────────────────────────┬──────────────────────────┘
                               │ HTTP GET
                               ▼
┌─────────────────────────────────────────────────────────┐
│    transparencia.fatorsistemas.com.br                   │
│    /dados/carregaFolha.php?id=...&mes=...&ano=...       │
└──────────────────────────────┬──────────────────────────┘
                               │ HTML response
                               ▼
┌─────────────────────────────────────────────────────────┐
│ PayrollScraperService (DOMDocument + DOMXPath)          │
│ PayrollParser (normalização de encoding e valores)      │
│ PayrollDTO (objeto de transferência)                    │
└──────────────────────────────┬──────────────────────────┘
                               │ Upsert
                               ▼
┌─────────────────────────────────────────────────────────┐
│        PostgreSQL — tabela payrolls                     │
└─────────────────────────────────────────────────────────┘
```

### Componentes Principais

| Componente | Localização | Responsabilidade |
|---|---|---|
| `PayrollScraperService` | `app/Services/Scraping/` | Requisição HTTP e parse do HTML via DOMXPath |
| `PayrollParser` | `app/Parsers/` | Limpeza e normalização dos valores (encoding, datas, moeda) |
| `PayrollDTO` | `app/DTO/` | Objeto de transferência entre scraper e banco |
| `ProcessPayrollScrapeJob` | `app/Jobs/` | Job de fila que orquestra o scraping por regime |
| `SavePayrollRecordJob` | `app/Jobs/` | Job de fila que persiste cada registro via upsert |
| `PayrollResource` | `app/Filament/Resources/` | Resource Filament com tabela, filtros e ações |
| `PayrollExporter` | `app/Filament/Exports/` | Exportação CSV/XLSX via Filament Actions |
| `PayrollScrapeProgressWidget` | `app/Filament/Resources/Payrolls/Widgets/` | Widget de progresso com polling a cada 2s |

---

## Fluxo do Usuário

```
USER (Admin)                              SYSTEM
  │                                           │
  │  👆 Acessa http://localhost:8000/admin    │
  │ ─────────────────────────────────────►    │
  │                                           │  AdminPanelProvider: auth=Filament
  │    Tela de login                          │  middleware: Authenticate
  │ ◄─────────────────────────────────────────│
  │                                           │
  │  👆 Autentica com e-mail e senha          │
  │ ─────────────────────────────────────►    │
  │                                           │  Login: validation: ✓
  │    "Painel de Folhas de Pagamento"        │
  │ ◄─────────────────────────────────────────│
  │                                           │
  │  👆 Clica em "Iniciar Extração"           │
  │ ─────────────────────────────────────►    │
  │                                           │  ListPayrolls: action=start_scrape
  │    ┌──────────────────────────────┐       │  (desabilitado se já houver batch ativo)
  │    │ Entidade: pm_portoseguro     │       │
  │    │ Mês:      [select 1–12]      │       │
  │    │ Ano:      [select últimos 5] │       │
  │    └──────────────────────────────┘       │
  │ ◄─────────────────────────────────────────│
  │                                           │
  │  👆 Preenche e confirma                   │
  │ ─────────────────────────────────────►    │
  │                                           │  Bus::batch([
  │                                           │    ProcessPayrollScrapeJob(regime=1),
  │                                           │    ProcessPayrollScrapeJob(regime=2),
  │                                           │    ProcessPayrollScrapeJob(regime=3),
  │                                           │  ])->dispatch()
  │    🔔 "Extração iniciada em               │
  │        segundo plano"                     │
  │ ◄─────────────────────────────────────────│
  │                                           │
  │                       [background queue]  │
  │                                           │  ProcessPayrollScrapeJob:
  │                                           │  ⚙️ HTTP GET carregaFolha.php
  │                                           │  ⚙️ DOMXPath → PayrollParser
  │                                           │  ⚙️ yield PayrollDTO[]
  │                                           │  ⚙️ → SavePayrollRecordJob (upsert)
  │                                           │
  │  👆 Clica em "Histórico"                 │
  │ ─────────────────────────────────────►    │
  │                                           │  PayrollScrapeProgressWidget
  │    ┌────────────────────────────────┐     │  wire:poll.2s → job_batches
  │    │ Payroll Scrape: 3/2025         │     │  data: {pending, total, failed}
  │    │ ████████████░░░░░░ 65%         │     │
  │    │ Processando: 130 de 200        │     │
  │    └────────────────────────────────┘     │
  │ ◄─────────────────────────────────────────│
  │                                           │
  │    🔔 "Scraping Finalizado               │
  │        mês 3/2025 concluído"             │
  │ ◄─────────────────────────────────────────│  Batch::then() → Notification (DB)
  │                                           │
  │  👆 Busca por nome ou matrícula           │
  │ ─────────────────────────────────────►    │
  │                                           │  PayrollsTable: searchable columns
  │    Tabela com resultados filtrados        │  (entity, registration, name, role, regime)
  │ ◄─────────────────────────────────────────│
  │                                           │
  │  👆 Clica em "Export"                     │
  │ ─────────────────────────────────────►    │
  │                                           │  PayrollExporter: CSV/XLSX
  │    📥 Download do arquivo                 │  🔔 Notificação ao concluir
  │ ◄─────────────────────────────────────────│
```

---

## Stack Tecnológica

| Categoria | Tecnologia | Versão |
|---|---|---|
| Linguagem | PHP | 8.4 |
| Framework | Laravel | 12 |
| Painel Admin | Filament | 5 |
| Frontend reativo | Livewire | 4 |
| Banco de dados | PostgreSQL | 16 |
| Cache / Fila / Sessão | Redis | 8 |
| CSS | Tailwind CSS | 4 |
| Build frontend | Vite | — |
| Testes | Pest | 4 |
| Análise estática | Larastan (PHPStan) | 3 |
| Code style | Laravel Pint | 1 |
| Refatoração | Rector | 2 |
| Containerização | Docker + Docker Compose | — |

---

## Pré-requisitos

### Via Docker (recomendado)
- Docker Engine 24+
- Docker Compose v2+

### Local (sem Docker)
- PHP 8.4 com extensões: `pdo_pgsql`, `redis`, `dom`, `mbstring`, `intl`
- Composer 2+
- Node.js 20+ e npm
- PostgreSQL 16+
- Redis 7+

---

## Como Executar

### Via Docker

```bash
git clone <url-do-repositorio>

cd porto-unsafe

cp .env.example .env

make env-up

```

Acesse o painel em: **http://localhost:8000/admin**

Para derrubar e limpar os containers:

```bash
make env-down
```

---

### Localmente (sem Docker)

```bash
# 1. Clone e acesse o repositório
git clone <url-do-repositorio>
cd porto-unsafe

# 2. Instale as dependências PHP e JS
composer install && npm install

# 3. Configure o ambiente
cp .env.example .env
# Edite .env com suas credenciais de PostgreSQL e Redis

# 4. Gere a chave da aplicação e execute as migrations
php artisan key:generate
php artisan migrate

# 5. Crie um link para storage e o primeiro usuário admin
php artisan storage:link
php artisan make:filament-user

# 6. Inicie todos os serviços em paralelo (servidor, queue, logs, vite)
make dev
```

O `make dev` executa simultaneamente:
- `php artisan serve` — servidor HTTP
- `php artisan queue:listen --tries=1` — worker de filas
- `php artisan pail` — visualizador de logs
- `npm run dev` — Vite em modo watch

Acesse o painel em: **http://localhost:8000/admin**

---

## Variáveis de Ambiente

As principais variáveis estão em `.env.example`. Copie para `.env` e ajuste conforme necessário:

| Variável | Padrão | Descrição |
|---|---|---|
| `APP_URL` | `http://localhost:8000` | URL base da aplicação |
| `APP_LOCALE` | `pt_BR` | Locale da aplicação |
| `DB_CONNECTION` | `pgsql` | Driver do banco de dados |
| `DB_HOST` | `porto-unsafe-db` | Host do PostgreSQL |
| `DB_PORT` | `5432` | Porta do PostgreSQL |
| `DB_DATABASE` | `porto-unsafe` | Nome do banco de dados |
| `DB_USERNAME` | `root` | Usuário do banco |
| `DB_PASSWORD` | `root` | Senha do banco |
| `QUEUE_CONNECTION` | `redis` | Driver da fila (obrigatório: `redis`) |
| `REDIS_HOST` | `porto-unsafe-redis` | Host do Redis |
| `REDIS_PORT` | `6379` | Porta do Redis |
| `CACHE_STORE` | `redis` | Driver de cache |
| `SESSION_DRIVER` | `redis` | Driver de sessão |

> **Atenção:** Para execução local (fora do Docker), altere `DB_HOST` para `127.0.0.1` e `REDIS_HOST` para `127.0.0.1`.

---

## Comandos Úteis (Makefile)

```bash
make help          # lista todos os comandos disponíveis
make dev           # inicia servidor + queue + logs + Vite
make env-up        # sobe os containers Docker
make env-down      # derruba e remove containers, volumes e imagens Docker
make setup         # setup inicial local (install, .env, migrations, build)
make migrate-fresh # recria o banco com seed
make test          # roda todos os testes em paralelo (Pest)
make test-unit     # roda apenas testes unitários
make test-feature  # roda apenas testes de feature
make format        # corrige estilo (Rector + Pint)
make check         # lint sem modificar (Rector dry-run + Pint test + PHPStan)
make phpstan       # análise estática (Larastan)
make pint          # formata código com Laravel Pint
make rector        # aplica refatorações automáticas com Rector
make route-list    # lista todas as rotas registradas
```

---

## Testes

O projeto usa [Pest](https://pestphp.com/) para testes automatizados.

```bash
# Todos os testes
make test

# Apenas unitários
make test-unit

# Apenas de feature
make test-feature

# Filtro por nome
php artisan test --compact --filter=NomeDoTeste

# Via Pest direto
./vendor/bin/pest --parallel --compact
```

---

## Painel Administrativo

Rota: `/admin` (autenticação obrigatória)

### Funcionalidades

| Funcionalidade | Descrição |
|---|---|
| **Listagem** | Tabela paginada com todas as folhas coletadas |
| **Busca** | Pesquisa por entidade, matrícula, nome, cargo e regime |
| **Ordenação** | Colunas de salários, mês e ano são ordenáveis |
| **Iniciar Extração** | Formulário para disparar scraping de um período específico |
| **Histórico** | Slide-over com progresso em tempo real (polling 2s) via Livewire |
| **Exportação** | Download CSV/XLSX com todos os campos formatados em BRL |
| **Notificações** | Alerta ao usuário quando o scraping é concluído (notificação no banco) |

### Colunas Visíveis por Padrão

- Entidade, Matrícula, Nome, Cargo, Regime de Emprego, Salário Líquido, Mês, Ano

### Colunas Toggleáveis (ocultas por padrão)

- Data de Admissão, Salário Base, Vantagens, Descontos, Impostos

---

## Infraestrutura Docker

O `docker-compose.yml` define 4 serviços:

| Serviço | Container | Descrição |
|---|---|---|
| `porto-unsafe-app` | App Laravel | Servidor HTTP na porta 8000 |
| `porto-unsafe-db` | PostgreSQL | Banco de dados na porta 5432 |
| `porto-unsafe-redis` | Redis | Cache, fila e sessão na porta 6379 |
| `porto-unsafe-worker` | Queue Worker | `php artisan queue:work redis --tries=3` |

Todos os serviços compartilham a rede `porto-unsafe-network`. O banco de dados persiste dados no volume `porto-unsafe-db-data`.

---

## Qualidade de Código

| Ferramenta | Propósito | Comando |
|---|---|---|
| **Laravel Pint** | Code style (PSR-12 + Laravel conventions) | `make pint` |
| **Rector** | Refatorações automáticas e modernização PHP | `make rector` |
| **Larastan** | Análise estática (PHPStan nivel máximo) | `make phpstan` |
| **Pest** | Testes automatizados | `make test` |

Para verificar tudo sem modificar arquivos:

```bash
make check
```
