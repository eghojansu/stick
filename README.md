# Stick-PHP

Stick to PHP with ```Fw``` class as its Kernel.

The kernel contains:
- Dependency Injection
- Event Dispatcher
- Logger
- Router

## Installation

  ```composer require eghojansu/stick```

## Usage

Example usage.

```php

require __DIR__.'vendor/autoload.php';

Ekok\Stick\Fw::createFromGlobals()
    ->route('GET /', function() {
        // Outputs "Hello world!"
        return 'Hello world!';
    })
    ->run()
;

```

## TODOs

- Handle CLI Request (handle arguments and options)
- Cache
- SQL Database Helper
