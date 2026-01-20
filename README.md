# CSF SAT Scraper

[![Source Code][badge-source]][source]
[![Packagist PHP Version Support][badge-php-version]][php-version]
[![Discord][badge-discord]][discord]
[![Latest Version][badge-release]][release]
[![Software License][badge-license]][license]
[![Build Status][badge-build]][build]
[![Reliability][badge-reliability]][reliability]
[![Maintainability][badge-maintainability]][maintainability]
[![Code Coverage][badge-coverage]][coverage]
[![Violations][badge-violations]][violations]
[![Total Downloads][badge-downloads]][downloads]

Un scraper en PHP para descargar constancias de situación fiscal del SAT México.

### Instalación

```bash
composer install phpcfdi/csf-sat-scraper
```

### Uso Básico

```php
<?php

require 'vendor/autoload.php';

use PhpCfdi\CsfSatScraper\Scraper;
use PhpCfdi\CsfSatScraper\HttpClientFactory;
use PhpCfdi\ImageCaptchaResolver\Resolvers\ConsoleResolver;

$client = HttpClientFactory::create([
    'curl' => [
        CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1',
    ],
    RequestOptions::VERIFY => false,
]);

$captchaSolver = new ConsoleResolver();

$scraper = Scraper::create(
    $client,
    $captchaSolver,
    'TU_RFC',
    'TU_CONTRASEÑA'
);

$pdfContent = $scraper->download();
file_put_contents('constancia.pdf', (string) $pdfContent);
```

## 🧪 Testing

### Ejecutar Tests

```bash
# Todos los tests
composer dev:test

# Con formato legible
vendor/bin/phpunit --testdox

# Test específico
vendor/bin/phpunit tests/Unit/Services/CaptchaServiceTest.php
```

### Cobertura de Código

```bash
composer dev:coverage
open build/coverage/html/index.html
```

## 🛠️ Desarrollo

### Requisitos

- PHP 8.2+
- Composer
- Extensión cURL

### Dependencias Principales

- `guzzlehttp/guzzle` - Cliente HTTP
- `symfony/dom-crawler` - Parsing de HTML
- `phpcfdi/image-captcha-resolver` - Resolución de captchas

### Dependencias de Desarrollo

- `phpunit/phpunit` ^11.0 - Framework de testing

## 🔧 Servicios

### AuthenticationService

Maneja todo el proceso de autenticación:
- Inicialización de login
- Obtención del formulario
- Envío de credenciales
- Verificación de sesión

### CaptchaService

Resuelve el captcha del SAT:
- Extracción de imagen del HTML
- Resolución con el solver configurado

### SSOHandler

Gestiona el flujo SSO/SAML:
- Procesamiento de formularios SAML
- Manejo de iframes
- Redirecciones SSO

### DocumentService

Descarga el documento:
- Envío de formulario final
- Descarga del PDF

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

Revisa la [guía de contribución](CONTRIBUTING.md).

### Ejecutar Tests Antes de PR

```bash
composer dev:test
```

## 📝 Licencia

[MIT License](LICENSE)

## 👤 Autor

Cesar Aguilera - cesargnu29@gmail.com

## 🙏 Agradecimientos

- PhpCfdi por image-captcha-resolver
- Comunidad de PHP por las herramientas

[contributing]: https://github.com/phpcfdi/csf-sat-scraper/blob/main/CONTRIBUTING.md
[changelog]: https://github.com/phpcfdi/csf-sat-scraper/blob/main/docs/CHANGELOG.md
[todo]: https://github.com/phpcfdi/csf-sat-scraper/blob/main/docs/TODO.md

[source]: https://github.com/phpcfdi/csf-sat-scraper
[php-version]: https://packagist.org/packages/phpcfdi/csf-sat-scraper
[discord]: https://discord.gg/aFGYXvX
[release]: https://github.com/phpcfdi/csf-sat-scraper/releases
[license]: https://github.com/phpcfdi/csf-sat-scraper/blob/main/LICENSE
[build]: https://github.com/phpcfdi/csf-sat-scraper/actions/workflows/build.yml?query=branch:main
[reliability]:https://sonarcloud.io/component_measures?id=phpcfdi_csf-sat-scraper&metric=Reliability
[maintainability]: https://sonarcloud.io/component_measures?id=phpcfdi_csf-sat-scraper&metric=Maintainability
[coverage]: https://sonarcloud.io/component_measures?id=phpcfdi_csf-sat-scraper&metric=Coverage
[violations]: https://sonarcloud.io/project/issues?id=phpcfdi_csf-sat-scraper&resolved=false
[downloads]: https://packagist.org/packages/phpcfdi/csf-sat-scraper

[badge-source]: https://img.shields.io/badge/source-phpcfdi/csf--scraper-blue.svg?logo=github
[badge-php-version]: https://img.shields.io/packagist/php-v/phpcfdi/csf-sat-scraper?logo=php
[badge-discord]: https://img.shields.io/discord/459860554090283019?logo=discord
[badge-release]: https://img.shields.io/github/release/phpcfdi/csf-sat-scraper.svg?logo=git
[badge-license]: https://img.shields.io/github/license/phpcfdi/csf-sat-scraper.svg?logo=open-source-initiative
[badge-build]: https://img.shields.io/github/actions/workflow/status/phpcfdi/csf-sat-scraper/build.yml?branch=main&logo=github-actions
[badge-reliability]: https://sonarcloud.io/api/project_badges/measure?project=phpcfdi_csf-sat-scraper&metric=reliability_rating
[badge-maintainability]: https://sonarcloud.io/api/project_badges/measure?project=phpcfdi_csf-sat-scraper&metric=sqale_rating
[badge-coverage]: https://img.shields.io/sonar/coverage/phpcfdi_csf-sat-scraper/main?logo=sonarqubecloud&server=https%3A%2F%2Fsonarcloud.io
[badge-violations]: https://img.shields.io/sonar/violations/phpcfdi_csf-sat-scraper/main?format=long&logo=sonarqubecloud&server=https%3A%2F%2Fsonarcloud.io
[badge-downloads]: https://img.shields.io/packagist/dt/phpcfdi/csf-sat-scraper.svg?logo=packagist
