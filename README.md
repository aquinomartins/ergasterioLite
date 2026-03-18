# Ergastério Lite

Base inicial de um MVP de mercado preditivo em arte, construída em PHP nativo com arquitetura leve e preparada para hospedagem compartilhada.

## Stack

- PHP 8.2+
- MySQL ou MariaDB
- PDO
- HTML renderizado pelo PHP
- CSS próprio
- JavaScript leve
- Sessões PHP para autenticação

## Arquitetura

```text
Request -> public/index.php -> Router -> Controller -> Service -> Repository -> Model -> View
```

### Estrutura principal

```text
public/
app/
  Core/
  Config/
  Controllers/
  Services/
  Repositories/
  Models/
  Requests/
  Views/
routes/
database/
storage/
docs/
```

## Funcionalidades entregues nesta base

- Home pública do Ergastério Lite
- Registro de usuário com criação automática de perfil
- Login e logout com sessão PHP
- Dashboard autenticado
- Edição de perfil autenticada
- Proteção CSRF em formulários POST
- Validação básica e flash messages
- Migrations iniciais de `users` e `profiles`

## Requisitos

- PHP 8.2 ou superior
- Extensões `pdo` e `pdo_mysql`
- MySQL 8+ ou MariaDB compatível

## Instalação local

### 1. Configurar ambiente

Copie o arquivo de exemplo:

```bash
cp .env.example .env
```

Edite `.env` com as credenciais do seu MySQL/MariaDB.

### 2. Criar banco e tabelas

Crie o banco `ergasterio_lite` e rode as migrations na ordem:

```bash
mysql -u root -p ergasterio_lite < database/migrations/001_create_users.sql
mysql -u root -p ergasterio_lite < database/migrations/002_create_profiles.sql
```

### 3. Subir servidor local

```bash
php -S localhost:8000 router.php
```

### 4. Acessar no navegador

- Home: `http://localhost:8000/`
- Cadastro: `http://localhost:8000/register`
- Login: `http://localhost:8000/login`
- Dashboard: `http://localhost:8000/dashboard`
- Editar perfil: `http://localhost:8000/profile/edit`

## Fluxo de autenticação

1. O usuário se cadastra.
2. O sistema valida os dados.
3. O `AuthService` cria `users` e `profiles` com transação.
4. A senha é salva com `password_hash`.
5. O login usa `password_verify`.
6. O dashboard e a edição de perfil exigem sessão ativa.

## Segurança aplicada

- Prepared statements em todos os acessos a banco.
- `password_hash` e `password_verify` para credenciais.
- CSRF token em todos os formulários `POST`.
- Validação e sanitização básicas nas requests.
- Sessão invalidada no logout.

## Decisões de arquitetura

- **Front Controller** em `public/index.php` para centralizar bootstrap e despacho.
- **Router próprio** para manter compatibilidade com hospedagem simples.
- **Controllers finos**: apenas fluxo HTTP e renderização.
- **Services**: concentram regras de cadastro, login e atualização de perfil.
- **Repositories**: concentram SQL e uso de PDO.
- **Views**: HTML separado da lógica de negócio.

## Próximos passos recomendados

1. Adicionar módulo de artistas com CRUD básico e páginas públicas.
2. Adicionar módulo de obras com upload controlado em `storage/uploads`.
3. Criar camada de autorização por papéis/status.
4. Expandir validações reutilizáveis e tratamento de erros.
5. Incluir logs de aplicação em `storage/logs`.

## Observações

- Os controllers `ArtistController`, `ArtworkController`, `MarketController` e `AdminController` já existem como placeholders para a próxima fase.
- O projeto evita frameworks pesados, ORMs e processos persistentes para manter simplicidade operacional.
