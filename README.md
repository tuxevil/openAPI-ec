# openapi-ec

Servicio Laravel API-first para exponer una capa OpenAPI unificada sobre proveedores contables ecuatorianos. La v1 implementa Contifico y cubre contactos, productos, facturas y pagos.

## Stack

- Laravel 13
- Docker Compose
- Nginx + PHP-FPM
- Swagger UI en `/api/docs`

## Auth y credenciales

- Los sistemas internos consumen la API con `Authorization: Bearer <token>`.
- Los tokens válidos se configuran en `INTERNAL_BEARER_TOKENS`.
- Para `GET`, el proveedor se envía en query y las credenciales en headers:
  - `provider=contifico`
  - `X-Provider-Api-Key`
  - `X-Provider-Pos-Token` cuando aplique
- Para `POST` y `PUT`, `provider`, `credentials` y `data` viajan en JSON.

## Desarrollo

```bash
docker compose up --build -d
docker compose exec app composer install
docker compose exec app php artisan test
```

La API queda disponible en `http://localhost:8081` y la documentación en `http://localhost:8081/api/docs`.
# openAPI-ec
