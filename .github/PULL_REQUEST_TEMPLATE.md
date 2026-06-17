## Resumen

<!-- Una o dos frases que describan el cambio. -->

## Motivacion y contexto

<!-- Por que es necesario este cambio? Que problema resuelve o que capacidad agrega?
Vincula el issue relacionado con `Closes #123` o `Refs #123` si aplica. -->

## Cambios realizados

<!-- Lista concreta de los cambios principales. -->
-
-
-

## Como probar

<!-- Pasos para que un reviewer pueda verificar el cambio localmente. -->
```bash
docker compose up --build -d
docker compose exec app php artisan test --filter=NombreDelTest
```

## Checklist

- [ ] `./vendor/bin/pint --test` pasa en local
- [ ] `php artisan test` pasa en local
- [ ] `composer audit` no reporta vulnerabilidades nuevas
- [ ] Si el contrato HTTP cambia, `public/docs/openapi.yaml` esta actualizado
- [ ] Si se agrego una variable de entorno, `.env.example` esta actualizado
- [ ] Sin secretos, tokens ni credenciales en los archivos modificados
- [ ] Sin cambios fuera de scope (formateo masivo, refactors no relacionados)
- [ ] Mensajes de commit siguen el formato `tipo: descripcion`

## Impacto

<!-- Marca lo que aplique. -->
- [ ] Cambia el contrato de la API (breaking change)
- [ ] Cambia el contrato de la API (compatible hacia atras)
- [ ] Cambia infraestructura o despliegue
- [ ] Solo afecta documentacion
- [ ] No requiere accion de los consumidores

## Screenshots / Logs

<!-- Si aplica, pega salida relevante, capturas o curl examples. -->

## Notas para el reviewer

<!-- Contexto adicional, dudas, areas donde queres una segunda opinion. -->
