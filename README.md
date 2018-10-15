# Stick-PHP

Micro PHP Framework.

Inspired by [Fatfree Framework][1], [Symfony Framework][2] and [Laravel Framework][3].

## Installation

  ```composer require eghojansu/stick```

## Usage

Example usage.

```php
<?php

require 'vendor/autoload.php';

Fal\Stick\Fw::createFromGlobals()
    ->route('GET home /', function() {
        return 'Welcome home, Vanilla lover!';
    })
    ->run()
;

```

Example of framework usage as an CMS: [Stick-Bootstrap][4].

## Features

- Dependency injection.
- Event listener and dispatcher.
- Simple routing like Fatfree Framework.
- Cache utils.
- Logger.
- Console output formatter helper.
- Sql connection and mapper.
- Validation utils.
- Security utils.
- PHP template engine.
- Translator.

> Logger, Translator, Dependency Injection, Event listeners, Routing and Cache utils is bundled in main App class.

## System Variables

System variables all in UPPER CASE.

- AGENT: Client user agent.
- AJAX: Ajax request status.
- ALIAS: Current route alias.
- BASE: Base path.
- BASEURL: Base url.
- BODY: Request body.
- CACHE: Cache definition.
- CACHE_ENGINE: Cache engine.
- CACHE_REF: Cache ref.
- CASELESS: Wether route should match ignore case or not.
- CLI: Cli request status.
- CODE: Http response code.
- COOKIE: Cookies.
- DEBUG: Debug status.
- DICT: Dictionaries.
- DNSBL: Dns block list.
- ENCODING: Charset.
- ENTRY: Entry script name to embbed in url.
- ERROR: Error status.
- EVENTS: Event handlers.
- EXEMPT: Dns white list.
- FALLBACK: Language fallback.
- GET: Equivalent with ```$_GET```.
- HOST: Server host.
- IP: Client ip address.
- JAR: Cookie jar.
- LANGUAGE: Language.
- LOCALES: Locales path.
- LOG: Log path, if value empty then log will be disabled.
- MIME: Response content type.
- OUTPUT: Response content.
- PACKAGE: Package name.
- PARAMS: Current route parameters.
- PATH: Current path.
- PATTERN: Current route pattern.
- PORT: Server port.
- POST: Equivalent with ```$_POST```.
- PROTOCOL: Http protocol.
- QUIET: Wether to send hold response content or flush it.
- RAW: Is request body will be manually processed or not.
- REALM: Current url.
- REQUEST: Request headers.
- RESPONSE: Response headers.
- ROUTE_ALIASES: Route aliases.
- ROUTE_HANDLER_CTR: Route counter.
- ROUTE_HANDLERS: Route handlers.
- ROUTES: Route definitions.
- SCHEME: Http schema.
- SEED: Application seed.
- SENT: Response sent status.
- SERVER: Equivalent with ```$_SERVER```.
- SERVICE_ALIASES: Service aliases.
- SERVICE_RULES: Service rules.
- SERVICES: Service instances.
- SESSION: Equivalent with ```$_SESSION```.
- STATUS: Response status text.
- TEMP: Temporary path.
- THRESHOLD: Log threshold.
- TIME: Time since app construction.
- TRACE: Cut path in trace file.
- TZ: Current timezone.
- URI: Current request URI.
- VERB: Request method.
- VERSION: Package version.
- XFRAME: Xframe header.

[1]: http://fatfreeframework.com
[2]: http://symfony.com
[3]: http://laravel.com
[4]: http://github.com/eghojansu/stick-bootstrap