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

Fal\Stick\App::createFromGlobals()
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

[1]: http://fatfreeframework.com
[2]: http://symfony.com
[3]: http://laravel.com
[4]: http://github.com/eghojansu/stick-bootstrap