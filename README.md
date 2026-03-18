# Ergastério Lite

Fundação inicial do **Ergastério Lite**, pensada para um MVP de mercado preditivo em arte com foco em **Laravel + Blade + Tailwind + MySQL/MariaDB** e compatibilidade com **hospedagem compartilhada**.

> **Importante sobre este ambiente de entrega:** o repositório foi estruturado manualmente em convenção Laravel porque o ambiente não conseguiu baixar dependências externas do Composer/GitHub. Em um ambiente com acesso ao Packagist, basta instalar as dependências para executar a aplicação.

## Stack

- PHP 8.2+
- Laravel
- Blade
- Tailwind CSS
- MySQL / MariaDB
- JavaScript leve via Vite
- Sem WebSockets, Redis, filas avançadas ou microsserviços

## Estrutura principal

```text
app/
  Domain/
    Identity/
    Artists/
    Artworks/
    Markets/
    Admin/
  Http/
    Controllers/
    Requests/
  Policies/
  Services/
routes/
resources/views/
database/migrations/
database/seeders/
```

## Funcionalidades incluídas nesta fundação

- Autenticação base com registro, login e logout.
- Perfil de usuário com edição simples.
- Domínio de artistas com CRUD administrativo e listagem pública.
- Domínio de obras com CRUD administrativo, listagem pública e upload simples de imagem.
- Domínio de mercados com suporte inicial a mercados de múltipla escolha e opções associadas.
- Painel administrativo básico com listagem de artistas, obras e mercados.
- Policies para restringir criação/edição de artistas, obras e mercados a admins.
- Seeders para roles (`user`, `artist`, `admin`) e admin padrão.

## Regras de negócio implementadas

- `users.email` é único.
- Senha deve ser armazenada com hash seguro (`hashed` cast no model e `Hash::make` no seeder).
- Todo novo usuário recebe o papel `user`.
- Artista pode ser vinculado a um usuário cadastrado.
- Toda obra pertence a um artista.
- Todo mercado precisa de no mínimo 2 opções.
- Apenas admin cria/edita mercados.
- Usuário comum visualiza artistas, obras e mercados públicos.
- Compra de posição, LMSR, wallet e payout ficam para fases futuras.

## Setup local

### 1. Instalar dependências

```bash
composer install
npm install
```

### 2. Configurar ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` com os dados do seu banco MySQL/MariaDB.

### 3. Variáveis de ambiente mínimas

```env
APP_NAME="Ergasterio Lite"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ergasterio_lite
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
CACHE_STORE=file
```

### 4. Rodar migrations e seeders

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### 5. Rodar em desenvolvimento

```bash
php artisan serve
npm run dev
```

## Credenciais iniciais do admin

- **E-mail:** `admin@ergasterio-lite.local`
- **Senha:** `password123`

> Altere a senha imediatamente em ambiente real.

## Deploy mínimo em hospedagem compartilhada

1. Faça upload do projeto para fora da pasta pública do servidor.
2. Aponte o domínio/subdomínio para a pasta `public/` do projeto.
3. Rode `composer install --no-dev --optimize-autoloader` no ambiente de deploy, ou envie a pasta `vendor/` já gerada se sua hospedagem não permitir Composer.
4. Configure o `.env` com banco e URL final.
5. Execute `php artisan migrate --force`.
6. Execute `php artisan db:seed --force` apenas na primeira instalação.
7. Rode `php artisan storage:link` se o provedor permitir links simbólicos; se não permitir, configure upload diretamente em pasta pública controlada.
8. Garanta permissões de escrita em `storage/` e `bootstrap/cache/`.
9. Mantenha `QUEUE_CONNECTION=sync` e `CACHE_STORE=file` para compatibilidade com hospedagem compartilhada.

## Próximos passos sugeridos

- Instalar oficialmente o esqueleto Laravel e publicar os arquivos do framework.
- Adicionar testes de feature para autenticação, admin e validação de mercados.
- Evoluir o domínio de mercados com resolução, posições, carteira e histórico.
- Refinar o admin com filtros, paginação mais rica e auditoria.
