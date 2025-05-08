# 💼 API Carteira Financeira


## 🧭 Visão Geral

Esta API simula uma carteira financeira, permitindo que usuários:

- Criem contas e façam login via JWT
- Realizem depósitos e transferências
- Revertam transações
- Consultem extratos e saldo

Desenvolvida com **Laravel**, **JWT Auth**, **Docker via Sail**, **Laravel Telescope** e **Swagger**.


---

## 🧪 Tecnologias

- PHP (Laravel)
- Laravel Sail (Docker)
- POSTGRESQL
- JWT Auth
- Laravel Telescope
- Swagger (OpenAPI)
- PHPUnit

---

## ⚙️ Instalação

### Requisitos

- Git
- Docker & Docker Compose

### Setup do Projeto

```bash
# Clone o repositório
git clone https://github.com/gesse-fernandes/wallet-api
cd wallet-api

# Copie o .env de exemplo
cp .env.example .env

```
# Edite as variáveis no .env:
```bash
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=wallet_api
DB_USERNAME=sail
DB_PASSWORD=password
```

# Instale as dependencias:

```bash
# Instale dependências
composer install

# Instale o Sail
composer require laravel/sail --dev
php artisan sail:install

# Suba os containers
./vendor/bin/sail up -d

# Gere chaves
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan jwt:secret

# Rode as migrations
./vendor/bin/sail artisan migrate

```

### 🔐 Autenticação
# Após login, todas as requisições devem conter:

```bash


Authorization: Bearer {token}

```

### 📑 Endpoints da API
## 🔒 Autenticação
POST /api/auth/register

```bash
 {
  "name": "João da Silva",
  "email": "joao@email.com",
 "cpf_cnpj": "00000000000",
  "password": "Senha123!",
   "password_confirmation": "Senha123!",
  "street": "Rua A",
  "number": "123",
  "neighborhood": "Centro",
  "city": "São Paulo",
  "state": "SP",
  "zipcode": "12345-678"
}

```

Retorno esperado:

```bash

{
  "id": 1,
  "name": "João da Silva",
  "token": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaS9hdXRoL2xvZ2luIiwiaWF0IjoxNzQ2NzMxNTQzLCJleHAiOjE3NDY3MzUxNDMsIm5iZiI6MTc0NjczMTU0MywianRpIjoiOVp2YmFrTTgxZ1RNZ09WRSIsInN1YiI6IjIiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.txaYxvIdg2-p-XRtEoSOSUk0otmWd1OkJssttMg72xk"
}

```


POST /api/auth/login

```bash
{
  "email": "joao@email.com",
  "password": "Senha123!"
}

```
Retorno esperado:
```bash

{
  "id": 1,
  "name": "João da Silva",
  "token": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0L2FwaS9hdXRoL2xvZ2luIiwiaWF0IjoxNzQ2NzMxNTQzLCJleHAiOjE3NDY3MzUxNDMsIm5iZiI6MTc0NjczMTU0MywianRpIjoiOVp2YmFrTTgxZ1RNZ09WRSIsInN1YiI6IjIiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.txaYxvIdg2-p-XRtEoSOSUk0otmWd1OkJssttMg72xk"
}

```

POST /api/auth/logout
```bash
Authorization: Bearer {token}

```

Retorno esperado:

```bash

{
  "message": "Logout realizado com sucesso."
}

```

POST /api/transactions/transfer

```bash

{
  "amount":1000,
  "payee_id":1,
}

```
Retorno esperado:

```bash
{
    "message": "Transferência realizada com sucesso.",
    "transaction": {
        "payer_id": "2",
        "payee_id": "1",
        "type": "transfer",
        "status": "completed",
        "amount": "1000.00",
        "metadata": {
            "ip": "172.22.0.1",
            "user_agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36"
        },
        "updated_at": "2025-05-08T19:22:36.000000Z",
        "created_at": "2025-05-08T19:22:36.000000Z",
        "id": 8
    },
    "balance": "500.00"
}

```

POST /api/transactions/deposit

```bash

{
  "amount":1000,
 
}

```
Retorno esperado:

```bash
{
    "message": "Depósito realizado com sucesso.",
    "transaction": {
        "payer_id": null,
        "payee_id": 2,
        "type": "deposit",
        "status": "completed",
        "amount": "1000.00",
        "metadata": {
            "ip": "172.22.0.1",
            "user_agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36"
        },
        "updated_at": "2025-05-08T19:34:31.000000Z",
        "created_at": "2025-05-08T19:34:31.000000Z",
        "id": 9
    },
    "balance": "1500.00"
}

```

POST /api/transactions/reverse/{id}
```bash
{
  "reason": "Forma de pagamento invalida"
}

```
Retorno esperado:

```bash
{
    "message": "Transação revertida com sucesso.",
    "transaction": [
        "deposit",
        "1500.00"
    ]
}
```

GET /api/transactions/statement

Retorno esperado:
```bash
{
    "message": "Extrato consultado com sucesso.",
    "transactions": [
        {
            "id": 9,
            "payer_id": null,
            "payee_id": 2,
            "amount": "1000.00",
            "type": "deposit",
            "status": "reversed",
            "reversed_transaction_id": 9,
            "metadata": {
                "ip": "172.22.0.1",
                "user_agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36",
                "reason": "Reversão solicitada"
            },
            "created_at": "2025-05-08T19:34:31.000000Z",
            "updated_at": "2025-05-08T19:36:40.000000Z"
        },
        {
            "id": 2,
            "payer_id": 1,
            "payee_id": 2,
            "amount": "100.00",
            "type": "transfer",
            "status": "reversed",
            "reversed_transaction_id": null,
            "metadata": [],
            "created_at": "2025-05-08T15:31:36.000000Z",
            "updated_at": "2025-05-08T16:02:53.000000Z"
        }
    ],
    "balance": "500.00"
}
```

# Para rodar os testes:
```bash

./vendor/bin/sail test


```

# O Laravel Telescope está disponível em:

```bash

http://localhost/telescope



```

# 🧰 Comandos úteis

```bash

# Subir containers
./vendor/bin/sail up -d

# Parar containers
./vendor/bin/sail down

# Executar migrations
./vendor/bin/sail artisan migrate

# Executar testes
./vendor/bin/sail test

```
Permissões

```bash

chmod -R 777 storage bootstrap/cache


```

Caso ocorra Erros no Sail: 


```bash

./vendor/bin/sail down --rmi all -v
./vendor/bin/sail up -d



```
