# Stick-PHP

A collection of helper to build your website.


## Installation

  ```composer require eghojansu/stick@dev-master```

## Usage

Example usage.

```php
require __DIR__.'vendor/autoload.php';

Fal\Stick\Fw::createFromGlobals()
    ->registerShutdownHandler()
    ->route('GET home /', function() {
        return 'Welcome home, Vanilla lover!';
    })
    ->run()
;

```

## Features

- Dependency injection.
- Event listener and dispatcher.
- Simple routing.
- Logger.
- Sql connection and mapper.
- Cache.
- Validation.
- Security.
- PHP template engine.
- Translator.

> Logger, Translator, Dependency Injection, Event listeners, Routing and Cache utils is bundled in main App class.