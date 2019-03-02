# Stick-PHP

A symfony-like php framework.
Some of its component taken directly from Symfony and Fatfree framework's source code.

We do this because we love Symfony and Fatfree so much.
But Symfony development is too fast for us. API changes make us frustate sometimes.
And Fatfree has feature missing that we almost needed in web development.

## Installation

  ```composer require eghojansu/stick@dev-master```

## Usage

Example usage.

```php
use Fal\Stick\Web\Kernel;
use Fal\Stick\Web\Response;

require __DIR__.'vendor/autoload.php';

$kernel = new Kernel();
$kernel->getContainer()->get('router')
    ->route('GET home /', function() {
        // Yes!
        // Each controller should returns instance of Response,
        // and a controller can be any callable expression
        // including string expression like below:
        // - 'MyController->method' <= converted into => array(/* instance of MyController */, 'method')
        // - 'MyController::method' <= converted into => array('MyController', 'method')
        return Response::create('Welcome home, Vanilla lover!');
    })
;
$kernel->run();

```

## Features

- Dependency injection.
- Event listener and dispatcher.
- Router.
- Logger.
- Cache.
- Validation.
- Security.
- Database connection and mapper.
- Translation.
- PHP template engine.
- Form helper.
- CRUD, Cli, Html, Image helper.