# openAPI-ec

Servicio Laravel API-first para exponer una capa OpenAPI unificada sobre proveedores ecuatorianos. Hoy incluye dos subsistemas:

- **Contabilidad** con **Contifico** para contactos, productos, facturas y pagos contables.
- **Payment gateways** con **Payphone** para cobros directos, links, consulta de transacciones y reversos.

La API esta pensada para ser consumida por sistemas internos como `nexOS`, `AMP` y `nano` usando un bearer token propio por sistema. La seleccion del proveedor es **por request** y las credenciales externas **no se persisten**.

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

## Subsistemas

### Contabilidad

Usa `provider=contifico` y credenciales de Contifico por request.

### Payment gateways

Usa `provider=payphone` y bearer token Payphone por request. Este subsistema trabaja con montos en **centavos enteros**.

## Autenticacion interna

Cada sistema interno debe autenticarse con:

```http
Authorization: Bearer <token>
```

Los tokens se configuran en `.env` con:

```env
INTERNAL_BEARER_TOKENS='{"nexOS":"token-nexos","AMP":"token-amp","nano":"token-nano"}'
```

## Credenciales externas

### GET

En operaciones `GET`, el proveedor viaja en query y las credenciales viajan en headers segun el subsistema:

- Contifico:
  - `provider=contifico`
  - `X-Provider-Api-Key: <api-key>`
  - `X-Provider-Pos-Token: <pos-token>` cuando aplique
- Payphone:
  - `provider=payphone`
  - `X-Gateway-Bearer: <payphone-bearer-token>`

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

Para Payphone:

```json
{
  "provider": "payphone",
  "credentials": {
    "bearerToken": "tu-payphone-token"
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
- `POST /api/v1/payment-gateways/sales`
- `POST /api/v1/payment-gateways/links`
- `GET /api/v1/payment-gateways/transactions/{transactionId}`
- `POST /api/v1/payment-gateways/reversals`

## Ejemplos Payphone

### 1. Crear link de pago

```bash
curl -X POST http://localhost:8081/api/v1/payment-gateways/links \
  -H 'Authorization: Bearer token-nexos' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "provider": "payphone",
    "credentials": {
      "bearerToken": "payphone-production-token"
    },
    "data": {
      "reference": "Pago orden #12345",
      "clientTransactionId": "NEXOS-ORDER-12345",
      "amount": 11500,
      "amountWithTax": 10000,
      "amountWithoutTax": 0,
      "tax": 1500,
      "notifyUrl": "https://nexos.example.com/webhooks/payphone"
    }
  }'
```

Respuesta esperada:

```json
{
  "provider": "payphone",
  "operation": "payment-gateways.link.create",
  "externalId": "NEXOS-ORDER-12345",
  "status": "pending",
  "data": {
    "url": "https://payp.hn/x/ejemplo123",
    "clientTransactionId": "NEXOS-ORDER-12345",
    "reference": "Pago orden #12345",
    "amount": 11500,
    "amountWithTax": 10000,
    "amountWithoutTax": 0,
    "tax": 1500,
    "notifyUrl": "https://nexos.example.com/webhooks/payphone"
  },
  "providerResponse": {}
}
```

### 2. Consultar estado de transaccion

```bash
curl "http://localhost:8081/api/v1/payment-gateways/transactions/123456789?provider=payphone" \
  -H 'Authorization: Bearer token-nexos' \
  -H 'Accept: application/json' \
  -H 'X-Gateway-Bearer: payphone-production-token'
```

### 3. Reversar transaccion

```bash
curl -X POST http://localhost:8081/api/v1/payment-gateways/reversals \
  -H 'Authorization: Bearer token-nexos' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "provider": "payphone",
    "credentials": {
      "bearerToken": "payphone-production-token"
    },
    "data": {
      "transactionId": 123456789,
      "clientTransactionId": "NEXOS-ORDER-12345"
    }
  }'
```

### Flujo recomendado para sistemas internos

1. Crear `Sale` o `Link` con un `clientTransactionId` propio del sistema.
2. Guardar `externalId` y `clientTransactionId` para conciliacion interna.
3. Consultar `GET /payment-gateways/transactions/{transactionId}` hasta obtener `success` o `error`.
4. Reversar solo cuando el negocio realmente requiera anular el cobro completo.

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
- `PAYPHONE_BASE_URL`
- `PAYPHONE_TIMEOUT`

## Testing

La suite cubre:

- autenticacion interna bearer
- resolucion de proveedores
- validaciones requeridas
- normalizacion de respuestas
- integraciones con `Http::fake()` para contactos, productos, facturas, pagos contables y Payphone

## Notas

- El contenedor publica en `8081` porque `8080` ya estaba ocupado en este entorno.
- Payphone se implementa como subsistema separado de gateway de pagos, no como proveedor contable.
