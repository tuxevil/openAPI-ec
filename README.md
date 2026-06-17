# openAPI-ec

> Capa de integración API-first sobre Laravel que unifica el acceso a proveedores ecuatorianos bajo un único contrato OpenAPI.

[![PHP](https://img.shields.io/badge/PHP-8.4-777bb4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-ff2d20?logo=laravel&logoColor=white)](https://laravel.com/)
[![OpenAPI](https://img.shields.io/badge/OpenAPI-3.1-6ba539?logo=openapiinitiative&logoColor=white)](https://www.openapis.org/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHPUnit](https://img.shields.io/badge/tests-PHPUnit-2c3e50?logo=php&logoColor=white)](https://phpunit.de/)

Servicio HTTP stateless que expone una API REST normalizada para que sistemas internos consuman proveedores ecuatorianos (Contifico, Payphone) sin acoplarse a sus SDKs, autenticaciones o particularidades. La selección del proveedor y las credenciales externas viajan por request y nunca se persisten.

---

## Tabla de contenidos

- [Descripción](#descripción)
- [Stack tecnológico](#stack-tecnológico)
- [Arquitectura](#arquitectura)
- [Características](#características)
- [Requisitos previos](#requisitos-previos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Referencia de la API](#referencia-de-la-api)
  - [Endpoints disponibles](#endpoints-disponibles)
  - [Autenticación interna](#autenticación-interna)
  - [Credenciales de proveedor](#credenciales-de-proveedor)
  - [Formato de respuesta](#formato-de-respuesta)
  - [Códigos de error](#códigos-de-error)
- [Pruebas](#pruebas)
- [Seguridad](#seguridad)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Despliegue](#despliegue)
- [Contribuir](#contribuir)
- [Licencia](#licencia)
- [Contacto](#contacto)

---

## Descripción

`openAPI-ec` resuelve un problema recurrente al integrar varios proveedores locales: cada uno expone endpoints, autenticaciones y formas de respuesta diferentes, y replicar esa lógica en cada sistema consumidor (POS, e-commerce, ERP) genera duplicación, drift y acoplamiento.

Este servicio:

- Recibe una llamada HTTP estándar desde un sistema interno autorizado.
- Resuelve el proveedor solicitado en runtime mediante un patrón *Factory + Strategy*.
- Adapta la petición al esquema del proveedor (autenticación, payload, headers).
- Normaliza la respuesta a un contrato estable: `{ provider, operation, externalId, status, data, providerResponse }`.
- Devuelve errores tipados con un código semántico, separando fallos del cliente, del proveedor y timeouts.

Los sistemas internos (POS, ERPs, e-commerce) solo conocen el contrato de `openAPI-ec`. Cambiar de proveedor, agregar uno nuevo o versionar uno existente no impacta a los consumidores.

### Casos de uso

- **Conciliación contable**: consultar contactos, productos, facturas y pagos contra Contifico.
- **Cobros con tarjeta**: crear ventas y links de pago con Payphone, consultar transacciones y reversar.
- **Bridges SaaS–proveedor**: exponer un único punto de integración para múltiples sistemas cliente.

---

## Stack tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje | PHP | 8.4 |
| Framework | Laravel | 13.x |
| Cliente HTTP | Guzzle / `Illuminate\Http\Client` | ^7 |
| Documentación | OpenAPI + Swagger UI | 3.1 |
| Testing | PHPUnit | 12.x |
| Contenedor | Docker + Docker Compose | 24+ / 2.x |
| Web server | Nginx + PHP-FPM | 1.27 / 8.4-fpm-alpine |
| Linter | Laravel Pint | 1.x |

---

## Arquitectura

El servicio aplica tres patrones clásicos para mantener el código testeable y extensible:

- **Adapter**: cada proveedor externo se encapsula en una clase que implementa los contratos de capacidad (`ContactsProvider`, `ProductsProvider`, `InvoicesProvider`, `PaymentsProvider`, `AccountingProvider`).
- **Strategy**: el `ProviderFactory` resuelve la estrategia de proveedor según el parámetro `provider` del request.
- **Factory**: las clases *Client* (Guzzle) y *Provider* se construyen con sus credenciales por request, sin estado compartido.

```
┌────────────────┐   Bearer token   ┌──────────────────┐
│ Sistema interno│──────────────────▶                  │
│  (nexOS, AMP…) │                  │  Auth middleware │
└────────────────┘                  └────────┬─────────┘
                                              │
                                  ┌───────────▼───────────┐
                                  │  Controller + Request │
                                  │  (FormRequest + rules)│
                                  └───────────┬───────────┘
                                              │
                                  ┌───────────▼───────────┐
                                  │  ProviderFactory      │
                                  │  + AccountingProvider │
                                  └───────────┬───────────┘
                                              │
                          ┌───────────────────┴───────────────────┐
                          │                                       │
                ┌─────────▼─────────┐                 ┌───────────▼──────────┐
                │ ContificoProvider │                 │ PayphoneProvider     │
                │ + ContificoClient │                 │ + PayphoneClient     │
                └─────────┬─────────┘                 └───────────┬──────────┘
                          │                                       │
                          └────────────────┬──────────────────────┘
                                           │
                                ┌──────────▼──────────┐
                                │  Contifico /        │
                                │  Payphone (upstream)│
                                └─────────────────────┘
```

El diagrama se regenera desde `public/docs/openapi.yaml` y se sirve en `/api/docs`.

---

## Características

- **API REST normalizada** sobre OpenAPI 3.1 con Swagger UI incluido.
- **Autenticación por bearer token interno** con comparación de tiempo constante (`hash_equals`) y resolución del sistema cliente.
- **Selección de proveedor por request** (`?provider=contifico` o `?provider=payphone`).
- **Credenciales externas efímeras**: nunca se persisten, viajan en headers o body.
- **Rate limiting por sistema** (60 req/min por sistema interno) con respuesta JSON 429.
- **Normalización contable** (Contifico): tipo de identificación, RUC, cédula, pasaporte, Consumidor Final.
- **Pagos en centavos enteros** (Payphone) para evitar errores de redondeo.
- **Errores tipados** con códigos semánticos: `invalid_internal_token`, `validation_failed`, `provider_timeout`, `provider_upstream_error`, `provider_request_error`, `rate_limited`.
- **Suplantación segura en logs**: el contexto de log incluye `internal_system` para auditoría sin filtrar tokens.
- **Defensa en profundidad**: manejo de `Throwable` en `bootstrap/app.php` evita fugas de stack trace cuando `APP_DEBUG=false`.
- **Suite de pruebas**: 61 tests / 222 aserciones que cubren autenticación, validaciones, normalización, mapeo de errores, rate limiting y el flujo de ID externo.
- **Docker listo**: build reproducible, FPM + Nginx, entrada con corrección de permisos sobre `storage/`.

---

## Requisitos previos

- **Docker** 24+ y **Docker Compose** v2.
- **Make** (opcional, para los atajos).
- **Puertos**: `8081` libre (configurable en `docker-compose.yml`).

No se requiere PHP ni Composer en el host; todo se ejecuta dentro del contenedor.

---

## Instalación

### Con Docker (recomendado)

```bash
# 1. Clonar
git clone https://github.com/tuxevil/openAPI-ec.git
cd openAPI-ec

# 2. Configurar variables de entorno
cp .env.example .env

# 3. Generar APP_KEY
docker compose run --rm app php artisan key:generate

# 4. Levantar el servicio
docker compose up --build -d

# 5. Verificar
curl http://localhost:8081/up
```

La API queda disponible en `http://localhost:8081` y la documentación en `http://localhost:8081/api/docs`.

### Sin Docker (entorno local con PHP 8.4)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --port=8081
```

---

## Configuración

Todas las variables relevantes se documentan en `.env.example`. Las mínimas para producción son:

```env
APP_NAME=openapi-ec
APP_ENV=production
APP_KEY=base64:...                # generado con php artisan key:generate
APP_DEBUG=false
APP_URL=https://api.tu-dominio.com

# Mapa JSON sistema-interno => bearer-token.
# Genera tokens seguros con: openssl rand -hex 32
INTERNAL_BEARER_TOKENS='{"sistema-interno":"cambia-este-token"}'

CONTIFICO_BASE_URL=https://api.contifico.com/sistema
CONTIFICO_TIMEOUT=30
PAYPHONE_BASE_URL=https://pay.payphonetodoesposible.com/api
PAYPHONE_TIMEOUT=30
```

> ⚠️ **Nunca** commitees `.env`. Está cubierto por `.gitignore`.

### Generar tokens seguros

```bash
openssl rand -hex 32
```

Copia el resultado como valor en `INTERNAL_BEARER_TOKENS`. Cada sistema interno debe tener un token único y rotable de forma independiente.

---

## Referencia de la API

### Endpoints disponibles

Todos los endpoints bajo `/api/v1` requieren autenticación bearer y pasan por el rate limiter (60 req/min por sistema).

#### Contabilidad (Contifico)

| Método | Path | Descripción |
|---|---|---|
| `GET` | `/api/v1/contacts` | Listar contactos |
| `GET` | `/api/v1/contacts/{id}` | Obtener contacto |
| `POST` | `/api/v1/contacts` | Crear o actualizar contacto |
| `PUT` | `/api/v1/contacts/{id}` | Actualizar contacto |
| `GET` | `/api/v1/products` | Listar productos |
| `GET` | `/api/v1/products/{id}` | Obtener producto |
| `POST` | `/api/v1/products` | Crear o actualizar producto |
| `PUT` | `/api/v1/products/{id}` | Actualizar producto |
| `GET` | `/api/v1/products/{id}/stock` | Stock de un producto |
| `GET` | `/api/v1/invoices` | Listar facturas |
| `GET` | `/api/v1/invoices/{id}` | Obtener factura |
| `POST` | `/api/v1/invoices` | Crear factura |
| `GET` | `/api/v1/invoices/{id}/status` | Estado de factura |
| `GET` | `/api/v1/invoices/{invoiceId}/payments` | Pagos de una factura |
| `POST` | `/api/v1/invoices/{invoiceId}/payments` | Registrar pago |

#### Pasarela de pagos (Payphone)

| Método | Path | Descripción |
|---|---|---|
| `POST` | `/api/v1/payment-gateways/sales` | Cobro directo con tarjeta |
| `POST` | `/api/v1/payment-gateways/links` | Crear link de pago |
| `GET` | `/api/v1/payment-gateways/transactions/{transactionId}` | Estado de transacción |
| `POST` | `/api/v1/payment-gateways/reversals` | Reversar transacción |

#### Documentación

| Método | Path | Descripción |
|---|---|---|
| `GET` | `/api/docs` | Swagger UI |
| `GET` | `/api/docs/openapi.yaml` | Especificación OpenAPI 3.1 |
| `GET` | `/up` | Health check (no requiere auth) |

La especificación completa y actualizada está disponible en `/api/docs/openapi.yaml` y se puede importar en Postman, Insomnia o un generador de clientes.

### Autenticación interna

Todas las rutas `/api/v1/*` requieren un bearer token configurado en `INTERNAL_BEARER_TOKENS`:

```http
GET /api/v1/contacts?provider=contifico HTTP/1.1
Host: api.tu-dominio.com
Authorization: Bearer tu-token-aqui
Accept: application/json
```

El sistema cliente se identifica a partir del token y se inyecta como atributo `internalSystem` en el request. Aparece como contexto en los logs para auditoría.

### Credenciales de proveedor

Las credenciales externas (Contifico, Payphone) **nunca se persisten**. Viajan en headers para `GET` o en el body para `POST`/`PUT`.

#### Operaciones `GET` (Contifico)

```http
GET /api/v1/contacts?provider=contifico HTTP/1.1
Authorization: Bearer <token-interno>
X-Provider-Api-Key: <api-key-contifico>
X-Provider-Pos-Token: <pos-token>   # solo si aplica
```

#### Operaciones `POST` / `PUT`

```json
{
  "provider": "contifico",
  "credentials": {
    "apiKey": "<api-key-contifico>",
    "posToken": "<pos-token>"
  },
  "data": {
    "identificacion": "1712345678",
    "razon_social": "Consumidor Final",
    "email": "cliente@ejemplo.com",
    "es_extranjero": false
  }
}
```

Para Payphone:

```json
{
  "provider": "payphone",
  "credentials": {
    "bearerToken": "<token-payphone>"
  },
  "data": {
    "reference": "Pago orden #12345",
    "clientTransactionId": "orden-12345",
    "amount": 11500,
    "amountWithTax": 10000,
    "amountWithoutTax": 0,
    "tax": 1500,
    "notifyUrl": "https://tu-dominio.com/webhooks/payphone"
  }
}
```

> Todos los montos en Payphone se expresan en **centavos enteros**.

### Formato de respuesta

Todas las respuestas exitosas comparten esta forma:

```json
{
  "provider": "contifico",
  "operation": "contacts.get",
  "externalId": "123",
  "status": "success",
  "data": { },
  "providerResponse": { }
}
```

Los consumidores deben depender de `status` y `data`. `providerResponse` se conserva para trazabilidad y debugging.

### Códigos de error

| HTTP | `code` | Cuándo se devuelve |
|---|---|---|
| `401` | `invalid_internal_token` | Token ausente o no configurado en `INTERNAL_BEARER_TOKENS` |
| `422` | `validation_failed` | El body o los query params no cumplen las reglas del `FormRequest` |
| `422` | `provider_request_error` | El proveedor rechazó la petición (4xx aguas arriba) |
| `502` | `provider_upstream_error` | El proveedor devolvió 5xx |
| `504` | `provider_timeout` | El proveedor no respondió dentro de `*_TIMEOUT` segundos |
| `429` | `rate_limited` | El sistema interno superó 60 req/min |
| `500` | `internal_error` | Error inesperado; el detalle se omite si `APP_DEBUG=false` |

Shape de error:

```json
{
  "code": "provider_timeout",
  "message": "Contifico request timed out.",
  "details": { "status": null },
  "provider": "contifico"
}
```

`details.body` y `details.exception` se filtran automáticamente de la respuesta cuando `APP_DEBUG=false` para evitar fuga de PII o credenciales que el proveedor pudiera devolver en sus mensajes de error.

---

## Pruebas

La suite cubre:

- Autenticación bearer (token ausente, inválido, sistema no configurado).
- Resolución de proveedores (`ProviderFactory`, `PaymentGatewayFactory`).
- Validaciones de `FormRequest` (Cédula, RUC, Pasaporte, Consumidor Final, RUC inválido, items de factura, productos sin posToken, payphone requerido).
- Normalización Contifico (`isForeign` con whitelisting de `S/SI/TRUE/1/YES`, `identificationType`, externalId null vs fallback).
- Mapeo de errores upstream (4xx, 5xx, timeout, filtro de `details.body` en modo debug).
- Rate limiting (61ª request devuelve 429 con cuerpo JSON; `/docs` no se limita).
- Integraciones con `Http::fake()` para contactos, productos, facturas, pagos contables y Payphone.

```bash
# Ejecutar toda la suite
docker compose exec app php artisan test

# Ejecutar un test específico
docker compose exec app php artisan test --filter=ContificoApiTest

# Con cobertura
docker compose exec app php artisan test --coverage
```

Estado actual: **61 tests / 222 aserciones, 100 % verde**.

Linter:

```bash
docker compose exec app ./vendor/bin/pint
```

---

## Seguridad

- **Tokens internos** con `hash_equals` para comparación de tiempo constante.
- **Rate limiting** por sistema para mitigar abuso y DoS accidental contra proveedores externos.
- **Filtrado de `details.body`** y `details.exception` en respuestas de error cuando `APP_DEBUG=false`.
- **Permisos de `storage/`** corregidos en cada arranque del contenedor por `docker/entrypoint.sh`.
- **CVE monitoring**: `composer audit` se ejecuta en CI. Las dependencias se actualizan de forma proactiva ante avisos críticos.
- **Sin persistencia de credenciales externas**: los tokens de Contifico/Payphone viven solo durante el request.
- **Sin telemetría**: el servicio no envía datos a terceros.

### Reportar vulnerabilidades

Por favor, **no abras un issue público**. Escribe directamente a `tuxevil@gmail.com` con una descripción detallada y un PoC. Se responderá en menos de 72 horas.

---

## Estructura del proyecto

```
openAPI-ec/
├── app/
│   ├── Contracts/                      # Interfaces por capacidad
│   │   ├── AccountingProvider.php
│   │   ├── ContactsProvider.php
│   │   ├── InvoicesProvider.php
│   │   ├── PaymentsProvider.php
│   │   ├── PaymentGatewayProvider.php
│   │   └── ProductsProvider.php
│   ├── Exceptions/                     # Excepciones tipadas
│   │   ├── ProviderException.php
│   │   └── UnsupportedProviderException.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/         # Endpoints públicos
│   │   ├── Middleware/                 # Auth, throttle
│   │   ├── Requests/Api/V1/            # FormRequest + rules
│   │   └── Resources/
│   ├── Providers/                      # Adapter + Strategy
│   │   ├── Accounting/
│   │   │   ├── Contifico/
│   │   │   └── Support/                # Normalizers
│   │   └── PaymentGateway/
│   │       └── Payphone/
│   ├── Support/                        # Value objects
│   └── ValueObjects/
├── bootstrap/                          # Bootstrap + service providers
├── config/                             # Configuración de Laravel
├── database/                           # Migraciones (vacías, sin auth)
├── docker/                             # Dockerfile y entrypoint
│   ├── entrypoint.sh
│   └── nginx/default.conf
├── public/                             # Document root
│   ├── docs/openapi.yaml
│   └── index.php
├── resources/views/                    # swagger.blade.php
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
├── tests/
│   ├── Feature/                        # Tests de integración
│   └── Unit/                           # Tests unitarios
├── .env.example
├── composer.json
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## Despliegue

### Build de producción

```bash
docker build -t openapi-ec:latest .
```

### Consideraciones

- `APP_DEBUG=false` obligatorio en producción.
- `APP_KEY` único por entorno; rotable con `php artisan key:generate`.
- `INTERNAL_BEARER_TOKENS` con tokens generados con `openssl rand -hex 32`, idealmente desde un secret manager.
- Detrás de un reverse proxy (Nginx, Caddy, Traefik) que termine TLS.
- Logs centralizados (Loki, ELK, Datadog) consumiendo `storage/logs/laravel.log`.
- Health check HTTP en `GET /up` para Kubernetes / load balancers.
- Si se monta en un sub-path, ajustar `APP_URL` y `proxy_set_header` en Nginx.

---

## Contribuir

1. Lee la [guía de contribución](CONTRIBUTING.md) y el [código de conducta](CODE_OF_CONDUCT.md).
2. Haz fork del repositorio.
3. Crea una rama desde `main`: `git checkout -b feat/mi-cambio`.
4. Asegúrate de que `pint` y `php artisan test` pasen localmente.
5. Añade tests para cualquier cambio de comportamiento.
6. Actualiza `public/docs/openapi.yaml` si tocas el contrato HTTP.
7. Abre un Pull Request usando la plantilla provista.

¿Dudas, ideas o quieres discutir el diseño? Usa las [Discussions](https://github.com/tuxevil/openAPI-ec/discussions) en lugar de abrir un issue.

---

## Licencia

Distribuido bajo la licencia MIT. Ver [`LICENSE`](LICENSE) para el texto completo.

---

## Contacto

- **Autor**: Sebastian Real
- **Email**: `tuxevil@gmail.com`
- **Repositorio**: `github.com/tuxevil/openAPI-ec`

¿Encontraste un bug o tienes una sugerencia? Abre un [issue](https://github.com/tuxevil/openAPI-ec/issues).
