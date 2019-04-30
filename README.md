# Stick-PHP

Stick to PHP with ```Fw``` class as its hearts. You need to stick to it :)

Some of its code taken directly from Symfony and Fatfree framework's source code.

We do this because we love Symfony and Fatfree so much.
But Symfony development is too fast for us. API changes make us frustate sometimes.
And Fatfree has feature missing that we almost needed in web development.

## Installation

  ```composer require eghojansu/stick@dev-master```

## Usage

Example usage.

```php

require __DIR__.'vendor/autoload.php';

Fal\Stick\Fw::createFromGlobals()
    ->route('GET /', function($fw, $params) {
        return 'foo';
    })
    ->run();

```

## Features

- Class loader.
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

## Acknowledge

This framework still lacks of documentation but we still working on it.

Add information that our test unit is not complete, because some methods we don't know how to test :(.

## Known Bug

- Web socket run bug when quit with Ctrl-C.

  Bug on websocket/server::run (stream_select: unable to select...).