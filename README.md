# openAPI-ec

Servicio Laravel API-first para exponer una capa OpenAPI unificada sobre proveedores contables ecuatorianos. La v1 implementa **Contifico** y cubre **contactos**, **productos**, **facturas** y **pagos**.

La API esta pensada para ser consumida por sistemas internos como `nexOS`, `AMP` y `nano` usando un bearer token propio por sistema. La seleccion del proveedor es **por request** y las credenciales del proveedor **no se persisten**.

## Stack

- Laravel 13
- PHP 8.4
- Docker Compose
- Nginx + PHP-FPM
- OpenAPI YAML + Swagger UI

## Arquitectura

El servicio sigue el enfoque `Adapter + Strategy + Factory`:

- `ProviderFactory` resuelve el proveedor solicitado.
- `AccountingProvider` define la interfaz raiz.
- Las capacidades se separan en contratos pequenos:
  - `ContactsProvider`
  - `ProductsProvider`
  - `InvoicesProvider`
  - `PaymentsProvider`
- `ContificoProvider` implementa esas capacidades sin contaminar la logica de controladores.

Esto deja lista la base para agregar otros proveedores ecuatorianos sin tocar la API publica.

## Autenticacion interna

Cada sistema interno debe autenticarse con:

```http
Authorization: Bearer <token>
```

Los tokens se configuran en `.env` con:

```env
INTERNAL_BEARER_TOKENS='{"nexOS":"token-nexos","AMP":"token-amp","nano":"token-nano"}'
```

## Credenciales del proveedor

### GET

En operaciones `GET`, el proveedor viaja en query y las credenciales en headers:

- `provider=contifico`
- `X-Provider-Api-Key: <api-key>`
- `X-Provider-Pos-Token: <pos-token>` cuando aplique

### POST y PUT

En operaciones `POST` y `PUT`, el body debe incluir:

```json
{
  "provider": "contifico",
  "credentials": {
    "apiKey": "tu-api-key",
    "posToken": "tu-pos-token"
  },
  "data": {}
}
```

## Endpoints v1

- `GET /api/v1/contacts`
- `GET /api/v1/contacts/{id}`
- `POST /api/v1/contacts`
- `PUT /api/v1/contacts/{id}`
- `GET /api/v1/products`
- `GET /api/v1/products/{id}`
- `POST /api/v1/products`
- `PUT /api/v1/products/{id}`
- `GET /api/v1/products/{id}/stock`
- `GET /api/v1/invoices`
- `GET /api/v1/invoices/{id}`
- `POST /api/v1/invoices`
- `GET /api/v1/invoices/{id}/status`
- `GET /api/v1/invoices/{invoiceId}/payments`
- `POST /api/v1/invoices/{invoiceId}/payments`

## Respuesta estandar

Las respuestas exitosas siguen esta forma:

```json
{
  "provider": "contifico",
  "operation": "contacts.get",
  "externalId": "123",
  "status": "success",
  "data": {},
  "providerResponse": {}
}
```

Los consumidores internos deben depender de `status` y `data`. `providerResponse` se conserva para trazabilidad y debugging.

## Desarrollo local

Levantar el proyecto:

```bash
docker compose up --build -d
```

Instalar dependencias dentro del contenedor:

```bash
docker compose exec app composer install
```

Ejecutar tests:

```bash
docker compose exec app php artisan test
```

La API queda disponible en:

- `http://localhost:8081`
- `http://localhost:8081/api/docs`
- `http://localhost:8081/api/docs/openapi.yaml`

## Variables importantes

Revisar y ajustar:

- `APP_URL`
- `INTERNAL_BEARER_TOKENS`
- `CONTIFICO_BASE_URL`
- `CONTIFICO_TIMEOUT`

## Testing

La suite cubre:

- autenticacion interna bearer
- resolucion de proveedores
- validaciones requeridas
- normalizacion de respuestas
- integraciones con `Http::fake()` para contactos, productos, facturas y pagos

## Notas

- El contenedor publica en `8081` porque `8080` ya estaba ocupado en este entorno.
- La implementacion actual soporta solo `contifico`, pero la estructura ya esta preparada para sumar nuevos adaptadores.
