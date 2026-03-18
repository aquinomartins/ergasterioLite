# Ergastério Lite

## Visão Geral

O **Ergastério Lite** é a versão inicial e simplificada da plataforma Ergastério, desenvolvida para rodar em ambientes de hospedagem compartilhada (como Superdomínios), utilizando tecnologias acessíveis e de baixo custo.

Seu objetivo é validar, na prática, o conceito central do Ergastério:

> Um mercado preditivo aplicado à arte, onde estética, tecnologia e economia se encontram para gerar inteligência coletiva sobre valor cultural.

---

## Objetivo do Projeto

Esta versão tem como foco:

* colocar o sistema no ar rapidamente
* validar comportamento dos usuários
* testar mercados preditivos em arte
* atrair artistas e comunidade
* criar base real de uso

**Importante:**
O Ergastério Lite é um MVP. Ele não representa a arquitetura final do projeto.

---

## Stack Tecnológica

### Backend

* PHP 8.2+
* Arquitetura própria (sem Laravel)
* PDO para acesso ao banco

### Banco de Dados

* MySQL / MariaDB

### Frontend

* HTML renderizado pelo PHP
* CSS próprio
* JavaScript leve

### Infraestrutura

* Hospedagem compartilhada
* SSL
* Cron jobs

---

## Arquitetura

O sistema segue uma estrutura MVC simplificada:

```text
Request → Router → Controller → Service → Repository → Database → View
```

### Camadas

* **Controller:** recebe requisição
* **Service:** contém regras de negócio
* **Repository:** acesso ao banco
* **Model:** representação de dados
* **View:** renderização HTML

---

## Estrutura de Pastas

```text
/public
/app
  /Core
  /Controllers
  /Services
  /Repositories
  /Models
  /Policies
  /Requests
  /Views
/routes
/database
/storage
/docs
```

---

## Funcionalidades do MVP

### Autenticação

* cadastro
* login
* logout

### Artistas

* cadastro
* listagem
* página pública

### Obras

* cadastro
* upload de imagem
* associação ao artista

### Mercados

* criação de mercado
* múltipla escolha
* prazo de fechamento

### Participação

* abrir posição
* histórico simples

### Probabilidades

* cálculo simples baseado em peso

### Resolução

* definição manual de resultado
* cálculo de payoff

### Ranking

* ranking por desempenho

---

## Modelo de Probabilidade

O sistema utiliza uma lógica simplificada:

```text
probabilidade = peso da opção / soma total dos pesos
```

---

## Segurança

* hash seguro de senhas
* validação de entrada
* proteção CSRF
* prepared statements (PDO)
* controle de acesso por sessão

---

## Instalação

### 1. Clonar projeto

```bash
git clone <repo>
```

### 2. Configurar ambiente

Criar arquivo `.env` baseado em `.env.example`

### 3. Configurar banco

* criar banco MySQL
* importar migrations

### 4. Ajustar permissões

* `/storage`

### 5. Acessar no navegador

---

## Roadmap

### Fase 1

* autenticação
* artistas
* obras

### Fase 2

* mercados
* posições

### Fase 3

* resolução
* ranking

### Fase 4

* melhorias de UX
* comentários

---

## Limitações do Lite

* sem WebSocket
* sem LMSR completo
* sem cripto real
* sem NFT on-chain
* sem alta escalabilidade

---

## Evolução Futura

O Ergastério Lite servirá como base para:

* arquitetura em Node.js + TypeScript
* engine LMSR completa
* WebSockets
* sistema financeiro avançado
* integração com blockchain

---

## Filosofia do Projeto

O Ergastério busca unir:

* arte
* tecnologia
* economia
* comunidade

Criando um sistema onde:

> A estética se torna mensurável, compartilhável e economicamente relevante.

---

## Licença

Definir posteriormente.

---

## Contato

Projeto em desenvolvimento.
