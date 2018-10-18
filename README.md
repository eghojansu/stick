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

System variables and its *expected* type.

- AGENT (`string`)

  Client user agent name.

- AJAX (`bool`)

  Ajax request status.

- ALIAS (`string`)

  Current matched route alias.

- ALIASES (`array`)

  Route aliases.

- BASE (`string`)

  Url base path.

- BASEURL (`string`)

  Base url.

- BITMASK (`int`)

  htmlspecialchars flags bitmask.

- BODY (`mixed`)

  Request body.

- CACHE (`string`)

  Cache configuration.

- CASELESS (`bool`)

  Should route matching ignore case?

- CLI (`bool`)

  Cli request status.

- CODE (`int`)

  Http response code.

- COOKIE (`array`)

  Equivalent with `$_COOKIE`.

- CTR (`int`)

  Route map hander counter (*internal usage*).

- DEBUG (`bool`)

  Debug status.

- DICT (`array`)

  Dictionaries.

- DNSBL (`array`)

  Dns blacklist.

- ENCODING (`string`)

  Default charset.

- ENGINE (`mixed`)

  Cache engine definition (*internal usage*).

- ERROR (`bool`)

  Error status.

- EVENTS (`array`)

  Registered event handlers (*internal usage*).

- EXEMPT (`array`)

  Dns whitelist.

- FALLBACK (`string`)

  Language fallback.

- FRONT (`string`)

  This value will be added after url base path (BASE) and before url path.

  Example:
  ```php
  $BASE = '/foo';
  $FRONT = '/index.php';
  $urlPath = '/bar';

  // generated url will be:
  $BASE.$FRONT.$urlPath // /foo/index.php/bar
  ```

- GET (`array`)

  Equivalent with `$_GET`.

- HANDLERS (`array`)

  Route handlers (*internal usage*).

- HOST (`string`)

  Server host.

- ID (`array`)

  Service indexes.

- IP (`string`)

  Client Ip Address.

- JAR (`array`)

  Cookie jar.

- LANGUAGE (`string`)

  Language used. Support multiple language (separated by comma (,));

- LOCALES (`array`)

  Directories contains dictionary files (in php). File should returns array of language key and content.

  Example:

  ```php
  // id-ID.php
  // Indonesian language.
  return array(
    'apple' => 'Apple',
    'i' => array(
      'like' => array(
        'melon' => 'Aku suka melon.'
        'mango' => 'Aku suka mangga.'
      ),
    ),
  );
  ```

- LOG (`string`)

  Log directory. If not empty it will enable logging.

- MIME (`string`)

  Response content type.

- OUTPUT (`string`)

  Response content.

- PACKAGE (`string`)

  Package name.

- PARAMS (`array`)

  Current route parameters.

- PATH (`string`)

  Current request path.

- PATTERN (`string`)

  Current matched pattern.

- PORT (`int`)

  Server port.

- POST (`array`)

  Equivalent with `$_POST`.

- PROTOCOL (`string`)

  Http protocol.

- QUIET (`bool`)

  Response will be hold if these value is true.

- RAW (`bool`)

  Should framework read request body?

- REF (`mixed`)

  Cache engine instance (*internal usage*).

- REQUEST (`array`)

  Request headers.

- RESPONSE (`array`)

  Response headers.

- ROUTES (`array`)

  Routes definitions.

- RULES (`array`)

  Service rules.

- SCHEME (`string`)

  Http scheme.

- SEED (`string`)

  Application seed.

- SENT (`bool`)

  Is response sent?

- SERVER (`array`)

  Equivalent with `$_SERVER`

- SERVICES (`array`)

  Service instances.

- SESSION (`array`)

  Equivalent with `$_SESSION`.

- STATUS (`string`)

  Http status text.

- TEMP (`string`)

  Temp directory.

- THRESHOLD (`string`)

  Log threshold.

- TIME (`float`)

  Microtime since framework construction.

- TZ (`string`)

  Timezone.

- URI (`string`)

  Request URI.

- URL (`string`)

  Request URL.

- VERB (`string`)

  Http method.

- VERSION (`string`)

  Package version.

- XFRAME (`string`)

  Xframe header.

[1]: http://fatfreeframework.com
[2]: http://symfony.com
[3]: http://laravel.com
[4]: http://github.com/eghojansu/stick-bootstrap