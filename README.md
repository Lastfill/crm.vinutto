# 📚 Vinutto CRM - Documentação da API

## 📋 Sumário
- [Visão Geral](#visão-geral)
- [Instalação](#instalação)
- [Autenticação](#autenticação)
- [Endpoints](#endpoints)
- [Exemplos de Uso](#exemplos-de-uso)
- [Códigos de Status](#códigos-de-status)
- [Segurança](#segurança)

---

## 🎯 Visão Geral

API RESTful para gerenciamento de leads e usuários do sistema Vinutto CRM.

**URL Base:** `https://seudominio.com/api.php`

**Formato de Resposta:** JSON

**Autenticação:** Token Bearer (opcional para algumas rotas)

---

## 🚀 Instalação

### 1. Requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Extensões PHP: PDO, PDO_MySQL

### 2. Configuração do Banco de Dados

```bash
# Importar estrutura do banco
mysql -u seu_usuario -p seu_banco < database.sql
```

### 3. Configurar Credenciais

Edite o arquivo `api.php` com suas credenciais:

```php
$host = "localhost";
$db = "seu_banco";
$user = "seu_usuario";
$pass = "sua_senha";
```

### 4. Permissões

```bash
chmod 644 api.php
```

---

## 🔐 Autenticação

### Login

**Endpoint:** `?action=login`  
**Método:** `POST`

**Request:**
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**Response (Sucesso):**
```json
{
  "success": true,
  "token": "a1b2c3d4e5f6...",
  "user": {
    "id": 1,
    "username": "admin",
    "name": "Administrador",
    "role": "Admin",
    "email": "admin@vinutto.com",
    "telefone": ""
  }
}
```

**Response (Erro):**
```json
{
  "success": false,
  "error": "Credenciais inválidas"
}
```

### Usuários Padrão

| Username | Senha Padrão | Role |
|----------|--------------|------|
| admin | admin123 | Admin |
| corretor1 | 123 | Corretor |
| corretor2 | 123 | Corretor |

---

## 📡 Endpoints

### 1. Gerenciamento de Usuários

#### 1.1 Cadastrar Usuário (Admin)

**Endpoint:** `?action=createUser`  
**Método:** `POST`  
**Permissão:** Admin

**Request:**
```json
{
  "username": "corretor3",
  "password": "123",
  "name": "Corretor 3",
  "role": "Corretor",
  "email": "corretor3@vinutto.com",
  "telefone": "(11) 98765-4321",
  "adminRole": "Admin",
  "adminId": 1
}
```

**Response:**
```json
{
  "success": true,
  "userId": 4,
  "message": "Usuário cadastrado com sucesso"
}
```

#### 1.2 Listar Usuários (Admin)

**Endpoint:** `?action=listUsers&role=Admin`  
**Método:** `GET`  
**Permissão:** Admin

**Response:**
```json
{
  "success": true,
  "users": [
    {
      "id": 1,
      "username": "admin",
      "name": "Administrador",
      "role": "Admin",
      "email": "admin@vinutto.com",
      "telefone": null,
      "ativo": 1,
      "ultimo_login": "2024-02-06 10:30:00",
      "created_at": "2024-01-01 00:00:00"
    }
  ]
}
```

#### 1.3 Atualizar Usuário (Admin)

**Endpoint:** `?action=updateUser`  
**Método:** `POST`  
**Permissão:** Admin

**Request:**
```json
{
  "userId": 2,
  "name": "Corretor João Silva",
  "email": "joao@vinutto.com",
  "telefone": "(11) 99999-8888",
  "role": "Corretor",
  "ativo": 1,
  "adminRole": "Admin",
  "adminId": 1
}
```

#### 1.4 Deletar Usuário (Admin)

**Endpoint:** `?action=deleteUser`  
**Método:** `POST`  
**Permissão:** Admin

**Request:**
```json
{
  "userId": 3,
  "adminRole": "Admin",
  "adminId": 1
}
```

#### 1.5 Listar Corretores

**Endpoint:** `?action=listCorretores`  
**Método:** `GET`

**Response:**
```json
{
  "success": true,
  "corretores": [
    {
      "id": 2,
      "username": "corretor1",
      "name": "Corretor 1",
      "email": "corretor1@vinutto.com",
      "telefone": null
    }
  ]
}
```

#### 1.6 Resetar Senha (Admin)

**Endpoint:** `?action=resetPassword`  
**Método:** `POST`  
**Permissão:** Admin

**Request:**
```json
{
  "userId": 2,
  "newPassword": "123",
  "adminRole": "Admin",
  "adminId": 1
}
```

#### 1.7 Alterar Própria Senha

**Endpoint:** `?action=changePassword`  
**Método:** `POST`

**Request:**
```json
{
  "userId": 2,
  "currentPassword": "123",
  "newPassword": "novaSenha123"
}
```

---

### 2. Gerenciamento de Leads

#### 2.1 Listar Leads

**Endpoint:** `?action=listLeads&corretor=2&role=Corretor&status=atendimento&search=João`  
**Método:** `GET`

**Parâmetros opcionais:**
- `corretor` - ID do corretor (obrigatório se não for Admin)
- `role` - Role do usuário (Admin vê todos)
- `status` - Filtrar por status
- `search` - Buscar por nome, email ou telefone

**Response:**
```json
{
  "success": true,
  "leads": [
    {
      "id": 1,
      "nome": "João Silva",
      "tel": "+55 11 98765-4321",
      "email": "joao@email.com",
      "imovel": "Apartamento 2 quartos",
      "imovelLink": "https://...",
      "corretor": 2,
      "corretor_nome": "Corretor 1",
      "status": "atendimento",
      "feedback": "Cliente interessado",
      "historico": "[...]",
      "hist": [
        {
          "data": "06/02/2024 10:30",
          "note": "Primeiro contato",
          "status": "atendimento"
        }
      ],
      "dataCad": "2024-02-06 10:00:00",
      "dataAtt": "2024-02-06 10:30:00"
    }
  ]
}
```

#### 2.2 Salvar Novo Lead

**Endpoint:** `?action=saveLead`  
**Método:** `POST`

**Request:**
```json
{
  "nome": "João Silva",
  "tel": "+55 11 98765-4321",
  "email": "joao@email.com",
  "imovel": "Apartamento 2 quartos - Centro",
  "imovelLink": "https://exemplo.com/imovel/123",
  "corretor": 2,
  "status": "atendimento",
  "feedback": "Lead cadastrado via API",
  "hist": [
    {
      "data": "06/02/2024 10:30",
      "note": "Lead cadastrado no sistema",
      "status": "atendimento"
    }
  ],
  "userId": 2
}
```

**Response:**
```json
{
  "success": true,
  "message": "Lead cadastrado com sucesso",
  "leadId": 15
}
```

#### 2.3 Atualizar Lead Existente

**Endpoint:** `?action=saveLead`  
**Método:** `POST`

**Request:**
```json
{
  "id_update": 15,
  "tel": "+55 11 98765-4321",
  "email": "joao@email.com",
  "imovel": "Apartamento 2 quartos",
  "imovelLink": "https://...",
  "status": "interessado",
  "feedback": "Cliente demonstrou interesse",
  "hist": [
    {
      "data": "06/02/2024 10:30",
      "note": "Lead cadastrado",
      "status": "atendimento"
    },
    {
      "data": "06/02/2024 14:00",
      "note": "Cliente interessado - agendar visita",
      "status": "interessado"
    }
  ],
  "userId": 2
}
```

#### 2.4 Deletar Lead (Admin)

**Endpoint:** `?action=deleteLead`  
**Método:** `POST`  
**Permissão:** Admin

**Request:**
```json
{
  "leadId": 15,
  "role": "Admin",
  "userId": 1
}
```

#### 2.5 Exportar Leads (Admin)

**Endpoint:** `?action=exportLeads&role=Admin`  
**Método:** `GET`  
**Permissão:** Admin

**Response:**
```json
{
  "success": true,
  "leads": [
    {
      "id": 1,
      "nome": "João Silva",
      "tel": "+55 11 98765-4321",
      "email": "joao@email.com",
      "imovel": "Apartamento 2 quartos",
      "imovelLink": "https://...",
      "corretor": 2,
      "corretor_nome": "Corretor 1",
      "status": "fechado",
      "feedback": "Venda finalizada",
      "dataCad": "2024-02-01 10:00:00",
      "dataAtt": "2024-02-05 16:30:00"
    }
  ]
}
```

---

### 3. Estatísticas

#### 3.1 Dashboard/Estatísticas

**Endpoint:** `?action=stats&corretor=2&role=Corretor`  
**Método:** `GET`

**Response:**
```json
{
  "success": true,
  "stats": {
    "total": 45,
    "today": 3,
    "month": 12,
    "byStatus": [
      { "status": "atendimento", "count": 15 },
      { "status": "interessado", "count": 10 },
      { "status": "negociacao", "count": 8 },
      { "status": "fechado", "count": 7 },
      { "status": "perdido", "count": 5 }
    ]
  }
}
```

---

## 💡 Exemplos de Uso

### JavaScript (Fetch API)

```javascript
// Login
async function login() {
  const response = await fetch('https://seudominio.com/api.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      username: 'admin',
      password: 'admin123'
    })
  });
  
  const data = await response.json();
  console.log(data);
}

// Listar leads
async function getLeads() {
  const response = await fetch(
    'https://seudominio.com/api.php?action=listLeads&corretor=2&role=Corretor'
  );
  
  const data = await response.json();
  console.log(data.leads);
}

// Salvar lead
async function saveLead() {
  const response = await fetch('https://seudominio.com/api.php?action=saveLead', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      nome: 'João Silva',
      tel: '+55 11 98765-4321',
      email: 'joao@email.com',
      corretor: 2,
      status: 'atendimento',
      userId: 2
    })
  });
  
  const data = await response.json();
  console.log(data);
}
```

### PHP (cURL)

```php
// Login
$ch = curl_init('https://seudominio.com/api.php?action=login');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'username' => 'admin',
    'password' => 'admin123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$data = json_decode($response, true);
print_r($data);
```

### Python (requests)

```python
import requests

# Login
response = requests.post(
    'https://seudominio.com/api.php?action=login',
    json={
        'username': 'admin',
        'password': 'admin123'
    }
)

data = response.json()
print(data)

# Listar leads
response = requests.get(
    'https://seudominio.com/api.php',
    params={
        'action': 'listLeads',
        'corretor': 2,
        'role': 'Corretor'
    }
)

leads = response.json()
print(leads)
```

---

## 📊 Códigos de Status HTTP

| Código | Significado |
|--------|-------------|
| 200 | Sucesso |
| 400 | Requisição inválida |
| 401 | Não autorizado |
| 403 | Acesso negado |
| 404 | Não encontrado |
| 409 | Conflito (ex: username já existe) |
| 500 | Erro interno do servidor |

---

## 🔒 Segurança

### Boas Práticas Implementadas

✅ **Prepared Statements** - Proteção contra SQL Injection  
✅ **Password Hashing** - Senhas com bcrypt (PASSWORD_DEFAULT)  
✅ **HTTPS Recomendado** - Use sempre SSL/TLS em produção  
✅ **Validação de Entrada** - Todos os inputs são validados  
✅ **Auditoria** - Logs de todas as ações importantes  
✅ **CORS Configurado** - Controle de acesso cross-origin  
✅ **Soft Delete** - Usuários são desativados, não deletados  

### Recomendações Adicionais

1. **Use HTTPS em produção**
2. **Configure rate limiting** para evitar abuso
3. **Implemente JWT** para tokens mais seguros
4. **Monitore os logs** de auditoria regularmente
5. **Faça backup** regular do banco de dados
6. **Atualize senhas** padrão após instalação

---

## 🛠️ Troubleshooting

### Problema: Erro de conexão com banco

**Solução:** Verifique as credenciais em `api.php`:
```php
$host = "localhost";
$db = "seu_banco";
$user = "seu_usuario";
$pass = "sua_senha";
```

### Problema: CORS blocked

**Solução:** Verifique se os headers CORS estão configurados corretamente no início do `api.php`.

### Problema: Caracteres especiais quebrados

**Solução:** Certifique-se que o banco usa `utf8mb4` e que o header da API está correto:
```php
header('Content-Type: application/json; charset=utf-8');
```

---

## 📞 Suporte

Para dúvidas ou problemas, entre em contato através do sistema de suporte.

---

**Versão:** 2.0  
**Última atualização:** 06/02/2024
