# Stick-PHP

Stick to PHP with ```Fw``` class as its hearts. You need to stick to it :).

## Installation

  ```composer require eghojansu/stick```

## Usage

Example usage.

```php

require __DIR__.'vendor/autoload.php';

Fal\Stick\Fw::createFromGlobals()
    ->route('GET /', function() {
        // Outputs "Hello world!" in browser
        return 'Hello world!';
    })
    ->run()
;

```

## Acknowledge

This framework still lacks of documentation but we still working on it.

Our test unit is not complete, because some methods we don't know how to test :(.
