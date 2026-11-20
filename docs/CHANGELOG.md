# CHANGELOG

## Acerca de SemVer

Usamos [Versionado Semántico 2.0.0](SEMVER.md) por lo que puedes usar esta librería sin temor a romper tu aplicación.

## Cambios no liberados en una versión

Pueden aparecer cambios no liberados que se integran a la rama principal, pero no ameritan una nueva liberación de
versión, aunque sí su incorporación en la rama principal de trabajo, generalmente se tratan de cambios en el desarrollo.

## Listado de cambios

### Cambios sin liberar

- Se acepta `guzzlehttp/guzzle` 8 (`^7.10 || ^8.3`); las pruebas pasan con Guzzle 8.5.
- El rechazo del inicio de sesión se clasifica con el mensaje que el SAT pone en `var error = '...'`.
  Antes cualquier respuesta con la palabra «captcha» (la página de inicio de sesión siempre la tiene) se tomaba
  como captcha incorrecto, así que una contraseña equivocada podía reintentarse y gastar intentos del contribuyente.
- Un inicio de sesión no confirmado y sin mensaje lanza `LoginException`, no `InvalidCaptchaException`.
- `Scraper::login()` reintenta sólo el captcha incorrecto, con un captcha nuevo, hasta `maxCaptchaAttempts`
  (3 por omisión). Nunca reintenta una contraseña rechazada.
- `Scraper::download()` siempre cierra la sesión del SAT (`finally`), también cuando falla.
- La contraseña lleva `#[SensitiveParameter]` para no aparecer en trazas.

### Versión 0.1.0 2026-01-21

Primera versión pública disponible.
