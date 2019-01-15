# Stick-PHP

Another PHP micro framework.


## Installation

  ```composer require eghojansu/stick@dev-master```

## Usage

Example usage.

```php
require __DIR__.'vendor/autoload.php';

Fal\Stick\Core::createFromGlobals()
    ->route('GET home /', function() {
        return 'Welcome home, Vanilla lover!';
    })
    ->run()
;

```