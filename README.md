# porto-unsafe

> Plataforma de transparência pública da Prefeitura de Porto Seguro: coleta, armazena e expõe dados de folha de pagamento e despesas municipais via scraping assíncrono com painel administrativo para visualização, pesquisa e exportação.

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-5-FDBA74?logo=laravel&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-8-DC382D?logo=redis&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-4-38BDF8?logo=tailwindcss&logoColor=white)

---

## Visão Geral

O **porto-unsafe** coleta dados públicos de entidades municipais de Porto Seguro a partir de dois portais de transparência. O processo é assíncrono, baseado em filas Redis, e os dados coletados ficam disponíveis em um painel administrativo com suporte a busca, ordenação e exportação.

A origem do nome refere-se à cidade de **Porto Seguro** e ao caráter de extração não-oficial dos dados (`unsafe`), que são públicos mas acessados via scraping HTML.

---

## Fontes de Dados

### Folha de Pagamento

| Atributo | Valor |
|---|---|
| Portal | Transparência Fator Sistemas |
| URL base | `https://transparencia.fatorsistemas.com.br/dados/carregaFolha.php` |
| Entidades coletadas | `pm_portoseguro`, `educ_portoseguro`, `saude_portoseguro` |
| Regimes coletados | 3 regimes por período (CLT, Estatutário e outros) |
| Período selecionável | A partir de março/2024, com filtro por entidade |

### Despesas Municipais

| Atributo | Valor |
|---|---|
| Portal | Município Online |
| URL base | `https://www.municipioonline.com.br/ba/prefeitura/portoseguro/cidadao/despesa` |
| Tipos de despesa | 6 categorias via `ExpenseType` enum |
| Período selecionável | Mês e ano |

Os dados são públicos e dizem respeito a servidores municipais e movimentações orçamentárias da prefeitura.

---

## Dados Extraídos

### Folha de Pagamento

Cada registro da tabela `payrolls` contém:

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

### Despesas Municipais

Cada registro da tabela `expense_summaries` contém:

| Campo | Tipo | Descrição |
|---|---|---|
| `expense_type` | string (enum) | Categoria da despesa (ex: `empenhos`) |
| `expense_date` | date | Data do empenho/liquidação/pagamento |
| `empenho_number` | string | Número do empenho |
| `element_code` | string | Código do elemento de despesa |
| `element_description` | string | Descrição do elemento |
| `creditor` | string | Nome do credor |
| `creditor_document` | string | CPF/CNPJ do credor |
| `committed` | decimal | Valor empenhado (R$) |
| `annulled` | decimal | Valor anulado (R$) |
| `reinforced` | decimal | Valor reforçado (R$) |
| `liquidated` | decimal | Valor liquidado (R$) |
| `paid` | decimal | Valor pago (R$) |
| `bidding_modality` | string\|null | Modalidade de licitação |
| `process_number` | string\|null | Número do processo |
| `month` | integer | Mês de referência |
| `year` | integer | Ano de referência |

A chave única é composta por `(expense_type, empenho_number, year)`.

### Enum `ExpenseType`

| Case | Valor | Label |
|---|---|---|
| `ResumoOrcamentario` | `resumo_orcamentario` | Resumo Orçamentário |
| `Empenhos` | `empenhos` | Empenhos |
| `Liquidacoes` | `liquidacoes` | Liquidações |
| `Pagamentos` | `pagamentos` | Pagamentos |
| `ExtraOrcamentario` | `extra_orcamentario` | Extra Orçamentário |
| `RepasseFinanceiro` | `repasse_financeiro` | Repasse Financeiro |

### Enum `MunicipalDepartment`

| Case | Valor | Label |
|---|---|---|
| `Administracao` | `pm_portoseguro` | Administração |
| `Educacao` | `educ_portoseguro` | Educação |
| `Saude` | `saude_portoseguro` | Saúde |

---

## Arquitetura

```
┌──────────────────────────────────────────────────────────────────────┐
│                      Filament Admin Panel                            │
│  /admin (autenticação obrigatória)                                   │
│                                                                      │
│  ┌─────────────────────────────┐  ┌──────────────────────────────┐   │
│  │   PayrollResource           │  │   ExpenseSummaryResource     │   │
│  │   (tabela, busca, exportar) │  │   (abas por ExpenseType)     │   │
│  │   Ação: Iniciar Extração    │  │   Ação: Iniciar Extração     │   │
│  └──────────────┬──────────────┘  └──────────────┬───────────────┘   │
└─────────────────┼────────────────────────────────┼───────────────────┘
                  │ Bus::batch()                    │ Bus::batch()
                  ▼                                 ▼
┌──────────────────────────────────────────────────────────────────────┐
│                      Laravel Queue (Redis)                           │
│                                                                      │
│   ProcessPayrollScrapeJob          ProcessExpenseScrapeJob           │
│   (por regime: 1, 2, 3)            (por ExpenseType)                 │
└──────────────────┬─────────────────────────────┬─────────────────────┘
                   │ HTTP GET                     │ HTTP POST (ASP.NET)
                   ▼                              ▼
┌──────────────────────────────┐   ┌─────────────────────────────────┐
│  transparencia.fatorsistemas │   │  municipioonline.com.br         │
│  .com.br/dados/carregaFolha  │   │  /cidadao/despesa               │
└──────────────┬───────────────┘   └──────────────┬──────────────────┘
               │ HTML                              │ HTML
               ▼                                  ▼
┌──────────────────────────────┐   ┌─────────────────────────────────┐
│  PayrollScraperService       │   │  ExpenseSummaryScraperService   │
│  PayrollParser               │   │  ExpenseSummaryParser           │
│  PayrollDTO                  │   │  ExpenseSummaryDTO              │
│                              │   │                                 │
│  └── HtmlTableParser ───────►│   │  └── HtmlTableParser ──────────►│
└──────────────┬───────────────┘   └──────────────┬──────────────────┘
               │ Upsert                            │ Upsert
               ▼                                  ▼
┌──────────────────────────────┐   ┌─────────────────────────────────┐
│  PostgreSQL — payrolls       │   │  PostgreSQL — expense_summaries │
└──────────────────────────────┘   └─────────────────────────────────┘
```

### Componentes Principais

#### Folha de Pagamento

| Componente | Localização | Responsabilidade |
|---|---|---|
| `PayrollScraperService` | `app/Services/Scraping/` | Requisição HTTP GET e parse do HTML via HtmlTableParser |
| `PayrollParser` | `app/Parsers/` | Limpeza e normalização dos valores (encoding, datas, moeda) |
| `PayrollDTO` | `app/DTO/` | Objeto de transferência entre scraper e banco |
| `ProcessPayrollScrapeJob` | `app/Jobs/` | Job de fila que orquestra o scraping por regime (1, 2, 3) |
| `PayrollResource` | `app/Filament/Resources/` | Resource Filament com tabela, filtros e ações |
| `PayrollExporter` | `app/Filament/Exports/` | Exportação CSV/XLSX via Filament Actions |
| `PayrollScrapeProgressWidget` | `app/Filament/Resources/Payrolls/Widgets/` | Widget de progresso com polling a cada 2s |

#### Despesas Municipais

| Componente | Localização | Responsabilidade |
|---|---|---|
| `ExpenseSummaryScraperService` | `app/Services/Scraping/` | Requisição HTTP POST (ASP.NET WebForms) e parse via HtmlTableParser |
| `ExpenseSummaryParser` | `app/Parsers/` | Normalização de valores monetários e strings |
| `ExpenseSummaryDTO` | `app/DTO/` | Objeto de transferência entre scraper e banco |
| `ProcessExpenseScrapeJob` | `app/Jobs/` | Job de fila que orquestra o scraping por `ExpenseType` |
| `ExpenseSummaryResource` | `app/Filament/Resources/` | Resource Filament com abas por tipo de despesa |

#### Utilitários Compartilhados

| Componente | Localização | Responsabilidade |
|---|---|---|
| `HtmlTableParser` | `app/Parsers/` | Extração de linhas de tabelas HTML via DOMDocument/DOMXPath (injetável, mockável) |
| `MunicipalDepartment` | `app/Enums/` | Enum de departamentos municipais (`Administracao`, `Educacao`, `Saude`) |
| `ExpenseType` | `app/Enums/` | Enum das 6 categorias de despesa com `anchor()` e `formKey()` |

---

## Fluxo do Usuário

### Folha de Pagamento

```
USER (Admin)                              SYSTEM
  │                                           │
  │  👆 Acessa /admin/payrolls                │
  │ ─────────────────────────────────────►    │
  │                                           │  AdminPanelProvider: auth=Filament
  │    Tabela de Folhas de Pagamento          │
  │ ◄─────────────────────────────────────────│
  │                                           │
  │  👆 Clica em "Iniciar Extração"           │
  │ ─────────────────────────────────────►    │
  │                                           │  ListPayrolls: action=start_scrape
  │    ┌──────────────────────────────┐       │  (desabilitado se já houver batch ativo)
  │    │ Entidade: [Administração ▼]  │       │
  │    │ Mês:      [select dinâmico]  │       │  filtra meses já extraídos por entidade/ano
  │    │ Ano:      [select 2024–...] │       │
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
  │                                           │  ⚙️ HtmlTableParser → PayrollParser
  │                                           │  ⚙️ Deduplicação por chave composta
  │                                           │  ⚙️ Payroll::upsert() → payrolls
  │                                           │
  │    🔔 "Scraping Finalizado               │
  │        mês 3/2025 concluído"             │
  │ ◄─────────────────────────────────────────│  Batch::then() → Notification (DB)
  │                                           │
  │  👆 Busca por nome ou matrícula           │
  │ ─────────────────────────────────────►    │
  │                                           │  PayrollsTable: colunas pesquisáveis
  │    Tabela com resultados filtrados        │  (entity, registration, name, role, regime)
  │ ◄─────────────────────────────────────────│
  │                                           │
  │  👆 Clica em "Export"                     │
  │ ─────────────────────────────────────►    │
  │                                           │  PayrollExporter: CSV/XLSX
  │    📥 Download do arquivo                 │  🔔 Notificação ao concluir
  │ ◄─────────────────────────────────────────│
```

### Despesas Municipais

```
USER (Admin)                              SYSTEM
  │                                           │
  │  👆 Acessa /admin/expense-summaries       │
  │ ─────────────────────────────────────►    │
  │                                           │  ExpenseSummaryResource: view=blade
  │    ┌─────────────────────────────┐        │  ListExpenseSummaries::getTableQuery()
  │    │ Resumo │ Empenhos │ Liquid. │        │  filtra por activeType (Livewire)
  │    │ Pagam. │ Extra    │ Repasse │        │
  │    └─────────────────────────────┘        │
  │    Tabela de despesas da aba ativa        │
  │ ◄─────────────────────────────────────────│
  │                                           │
  │  👆 Clica em outra aba                    │
  │ ─────────────────────────────────────►    │
  │                                           │  Livewire: $set('activeType', value)
  │    Tabela recarrega com novo tipo         │  getTableQuery() → WHERE expense_type=...
  │ ◄─────────────────────────────────────────│
  │                                           │
  │  👆 Clica em "Iniciar Extração"           │
  │ ─────────────────────────────────────►    │
  │                                           │  ListExpenseSummaries: action=start_scrape
  │    ┌──────────────────────────────┐       │
  │    │ Tipo: [Empenhos ▼]           │       │
  │    │ Mês:  [select 1–12]          │       │
  │    │ Ano:  [select 2024–...]      │       │
  │    └──────────────────────────────┘       │
  │ ◄─────────────────────────────────────────│
  │                                           │
  │  👆 Confirma                              │
  │ ─────────────────────────────────────►    │
  │                                           │  Bus::batch([
  │                                           │    ProcessExpenseScrapeJob(type, year, month)
  │                                           │  ])->dispatch()
  │    🔔 "Extração iniciada em               │
  │        segundo plano"                     │
  │ ◄─────────────────────────────────────────│
  │                                           │
  │                       [background queue]  │
  │                                           │  ProcessExpenseScrapeJob:
  │                                           │  ⚙️ HTTP POST (ASP.NET) + CookieJar
  │                                           │  ⚙️ HtmlTableParser → ExpenseSummaryParser
  │                                           │  ⚙️ Deduplicação por chave composta
  │                                           │  ⚙️ ExpenseSummary::upsert()
  │                                           │
  │    🔔 "Extração Finalizada"              │
  │ ◄─────────────────────────────────────────│  Batch::then() → Notification (DB)
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

### Folha de Pagamento — `/admin/payrolls`

| Funcionalidade | Descrição |
|---|---|
| **Listagem** | Tabela paginada com todas as folhas coletadas |
| **Busca** | Pesquisa por entidade, matrícula, nome, cargo e regime |
| **Ordenação** | Colunas de salários, mês e ano são ordenáveis |
| **Iniciar Extração** | Formulário para disparar scraping com seletor de entidade, mês (filtra já extraídos) e ano |
| **Exportação** | Download CSV/XLSX com todos os campos formatados em BRL |
| **Notificações** | Alerta no banco quando o scraping é concluído |

**Colunas visíveis por padrão:** Entidade, Matrícula, Nome, Cargo, Regime de Emprego, Salário Líquido, Mês, Ano

**Colunas toggleáveis (ocultas por padrão):** Data de Admissão, Salário Base, Vantagens, Descontos, Impostos

### Despesas Municipais — `/admin/expense-summaries`

| Funcionalidade | Descrição |
|---|---|
| **Abas por categoria** | 6 abas (Resumo Orçamentário, Empenhos, Liquidações, Pagamentos, Extra Orçamentário, Repasse Financeiro) com tabela reativa via Livewire |
| **Listagem** | Tabela paginada filtrada pelo tipo da aba ativa |
| **Iniciar Extração** | Formulário com seletor de tipo, mês e ano |
| **Notificações** | Alerta no banco quando o scraping é concluído |

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
