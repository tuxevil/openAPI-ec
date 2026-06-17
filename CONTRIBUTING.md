# Guia para contribuir

Gracias por tu interes en mejorar `openAPI-ec`. Esta guia cubre el flujo de trabajo, los estandares de codigo y el proceso de revision.

## Codigo de Conducta

Este proyecto adhiere al [Contributor Covenant v2.1](CODE_OF_CONDUCT.md). Al participar, aceptas sus terminos. Reporta incidentes a `tuxevil@gmail.com`.

## Como empezar

1. **Busca un issue existente** o propón uno nuevo describiendo el problema o la mejora.
2. **Comenta en el issue** antes de empezar a trabajar, para evitar duplicar esfuerzo.
3. **Espera la señal** de un maintainer (reaccion 👍 o comentario "go ahead") para issues grandes o que tocan el contrato HTTP.
4. **Haz fork** del repositorio y clona tu copia local.
5. **Configura el entorno** con Docker (ver [README](README.md#instalacion)).

## Flujo de trabajo

```bash
# 1. Sincroniza main y crea una rama desde main
git checkout main
git pull --rebase
git checkout -b feat/mi-cambio

# 2. Haz commits atomicos
git add archivo1 archivo2
git commit -m "feat: descripcion corta del cambio"

# 3. Antes de pushear, valida localmente
docker compose exec app ./vendor/bin/pint --test
docker compose exec app php artisan test
docker compose exec app composer audit

# 4. Pushea y abre un Pull Request
git push origin feat/mi-cambio
gh pr create --fill
```

## Mensajes de commit

Seguimos [Conventional Commits](https://www.conventionalcommits.org/) en espanol, imperativo presente, primera linea de maximo 72 caracteres:

| Prefijo       | Uso                                                |
| ------------- | -------------------------------------------------- |
| `feat:`       | Nueva funcionalidad visible para el usuario        |
| `fix:`        | Correccion de un bug                                |
| `docs:`       | Cambios solo en documentacion                       |
| `refactor:`   | Cambio interno sin alterar comportamiento           |
| `perf:`       | Mejora de rendimiento                               |
| `test:`       | Agregar o corregir tests                            |
| `build:`      | Cambios en build, CI o dependencias                 |
| `chore:`      | Tareas de mantenimiento (gitignore, formato, etc.)  |
| `security:`   | Mitigacion de vulnerabilidad                        |

El cuerpo del commit debe responder **por que**, no **que** (el "que" esta en el diff).

## Estilo de codigo

- PHP 8.4 idiomatico: tipos estrictos, `readonly`, enums cuando apliquen, `match` sobre `switch` cuando sea posible.
- PSR-12 reforzado por [Laravel Pint](https://laravel.com/docs/pint). Antes de cada PR:

  ```bash
  ./vendor/bin/pint
  ```

- No anadas comentarios redundantes. El codigo se explica solo.
- Nombres descriptivos. Evita abreviaturas salvo en loops (`$i`, `$e`).
- Un `use` por linea, orden alfabetico dentro de cada grupo (PHP, luego Laravel, luego App).

## Tests

Cada cambio de comportamiento debe venir con tests. La suite se ejecuta con PHPUnit:

```bash
php artisan test                  # toda la suite
php artisan test --filter=Nombre  # un test especifico
php artisan test --coverage       # con cobertura
```

Lineamientos:

- **Feature tests** (`tests/Feature/`): prueban endpoints, middleware y flujos completos. Usan `Http::fake()` para no pegarle al proveedor real.
- **Unit tests** (`tests/Unit/`): prueban clases aisladas. No tocan Laravel container.
- **Nombres descriptivos**: `test_contifico_4xx_maps_to_422`, no `test_1`.
- **Un assert principal por test** cuando sea posible. Los demas asserts son contexto.
- **No uses `sleep`** ni dependas del tiempo real. Usa `Carbon::setTestNow()` o `Http::fake()` con respuestas deterministas.

## Cambios al contrato HTTP

El API es el producto. Si tocas un endpoint, validador o forma de respuesta:

1. Actualiza `public/docs/openapi.yaml` con la nueva forma (schemas, constraints, ejemplos).
2. Si agregas un codigo de error, actualiza la tabla en [README](README.md#codigos-de-error).
3. Si agregas una variable de entorno, actualiza `.env.example` y la seccion de [Configuracion](README.md#configuracion).
4. Si rompes compatibilidad, marcalo explicitamente en el PR con la etiqueta **breaking change**.

## Estructura del codigo

```
app/
├── Contracts/          # Interfaces (AccountingProvider, ContactsProvider, ...)
├── Exceptions/         # Excepciones tipadas
├── Http/
│   ├── Controllers/    # Endpoints publicos
│   ├── Middleware/     # Auth, throttle
│   ├── Requests/       # FormRequest con reglas
│   └── Resources/      # Transformadores de salida
├── Providers/          # Adapter + Strategy por proveedor
│   ├── Accounting/     # Contifico y futuros proveedores contables
│   └── PaymentGateway/ # Payphone y futuros gateways
├── Support/            # Value objects compartidos
└── ValueObjects/       # DTOs inmutables
```

Reglas:

- **Controladores delgados**: delegan en Providers. La logica de negocio va en `app/Providers/`.
- **Sin estado compartido**: el Provider se construye con sus credenciales en cada request.
- **Errores tipados**: cada caso de fallo del proveedor tiene un `apiCode` semantico (`provider_timeout`, `provider_upstream_error`, etc.) y un `httpStatus` razonable.

## Seguridad

Si descubres una vulnerabilidad, **no abras un issue publico**. Sigue la [politica de seguridad](SECURITY.md): email `tuxevil@gmail.com` o GitHub Security Advisories.

## Preguntas

Si tienes dudas que no cubre esta guia, abre un [Discussion](https://github.com/tuxevil/openAPI-ec/discussions) en lugar de un issue.
