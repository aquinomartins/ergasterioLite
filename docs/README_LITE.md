# Ergastério Lite — Notas da Base Inicial

## Arquitetura adotada

A fundação segue o fluxo `Front Controller -> Router -> Controller -> Service -> Repository -> Model -> View`, com classes pequenas e sem dependência de framework.

## Componentes iniciais

- `public/index.php`: ponto único de entrada.
- `app/Core`: infraestrutura mínima para roteamento, sessão, CSRF, autenticação, view e PDO.
- `app/Services/AuthService.php`: concentra cadastro, login e atualização de perfil.
- `app/Repositories`: isola consultas SQL com prepared statements.
- `app/Requests`: valida e sanitiza formulários.
- `database/migrations`: SQL inicial para `users` e `profiles`.

## Decisões práticas

- HTML renderizado no servidor para maximizar compatibilidade com hospedagem compartilhada.
- Sessões PHP nativas para autenticação.
- CSRF obrigatório em todos os formulários `POST`.
- CSS e JS próprios e mínimos, evitando build step e dependências pesadas.
