# Stick-PHP

Micro PHP Framework.

Inspired by [Fatfree Framework][1], [Symfony Framework][2] and [Laravel Framework][3].

## Installation

- Via composer

  ```composer require eghojansu/stick```

- Manual

  Download this repository as zip/tar then extract to your project directory.

## Usage

Example usage.

```php
<?php

use Fal\Stick\App;

require 'vendor/autoload.php';

$app = new App;
$app->route('GET home /', function() {
    return 'Welcome home, Vanilla lover!';
});
$app->run();

```

## Features

- Simple routing like Fatfree Framework.
- Cache utils.
- Logger.
- Dependency injection.
- Event listener and dispatcher.
- Sql connection and mapper.
- Validation utils.
- Security utils.
- PHP template engine.
- Translator.


[1]: http://fatfreeframework.com
[2]: http://symfony.com
[3]: http://laravel.com