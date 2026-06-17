# Politica de seguridad

## Versiones soportadas

| Version | Soporte            |
| ------- | ------------------ |
| `main`  | :white_check_mark: |
| < 1.0   | :x:                |

Este proyecto aun no tiene un corte de release formal. Mientras tanto, solo la rama `main` recibe parches de seguridad.

## Reportar una vulnerabilidad

**No abras un issue publico** para reportar vulnerabilidades. GitHub expone las issues a todo el mundo, y un detalle tecnico prematuramente publico facilita la explotacion antes de que haya un parche disponible.

Envia el reporte por uno de estos canales:

- **Email**: `tuxevil@gmail.com` (cifrado opcional con PGP si lo solicitas).
- **GitHub Security Advisories**: desde la pestaña *Security* del repositorio, opcion *Report a vulnerability*. Este canal es privado y solo visible para los maintainers.

Incluye en el reporte:

1. Descripcion clara de la vulnerabilidad y su impacto potencial.
2. Pasos para reproducir (PoC preferido, con requests HTTP anonimizados).
3. Entorno afectado (version, commit, configuracion relevante).
4. Tu evaluacion de severidad (CVSS si la tienes).
5. Si lo deseas, tu nombre y credito publico en el aviso de seguridad.

## Que esperar

- **Acuse de recibo**: en menos de 72 horas.
- **Triage inicial**: en menos de 7 dias, con evaluacion de severidad y plan de mitigacion.
- **Coordinacion**: si el reporte es valido, acordamos una fecha de divulgacion responsable. Por defecto, 90 dias desde el reporte o hasta que haya parche disponible, lo que ocurra primero.
- **Credito**: si lo aceptas, seras mencionado en el aviso de seguridad y en el `CHANGELOG`.

## Alcance

Lo siguiente esta dentro del alcance:

- Inyeccion (SQL, comando, template, header).
- Bypass de autenticacion o autorizacion en el middleware `internal.auth`.
- Fuga de informacion sensible en respuestas de error (`details.body`, stack traces, tokens).
- SSRF, RCE, deserializacion insegura.
- Dependencias con CVEs criticos o altos.
- Configuracion de Docker/Nginx con defaults inseguros.

Lo siguiente **no** esta en alcance (pero se agradece el aviso):

- Issues que requieren acceso fisico al servidor.
- Ataques de ingenieria social o phishing.
- Ausencia de rate limiting en endpoints publicos que intencionalmente lo sean (`/up`, `/api/docs`).
- Hallazgos en dependencias sin exploit publico conocido y sin ruta de explotacion practica.

## Divulgacion responsable

Aceptamos reportes bajo divulgacion responsable. No tomamos acciones legales contra investigadores que:

- Hagan el intento de buena fe de evitar destruccion de datos o interrupcion del servicio.
- Nos den tiempo razonable para responder antes de hacer publico el detalle.
- No exf-iltren datos de usuarios al demostrar la vulnerabilidad.

## Reconocimientos

Este proyecto sigue las practicas de [GitHub Security Advisories](https://docs.github.com/es/code-security/security-advisories) y las guias de [Coordinated Disclosure](https://cheatsheetseries.owasp.org/cheatsheets/Vulnerability_Disclosure_Cheat_Sheet.html) de OWASP.
