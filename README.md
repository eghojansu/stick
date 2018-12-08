# Stick-PHP

A collection of helper to build your website.


## Installation

  ```composer require eghojansu/stick@dev-master```

## Usage

Example usage.

```php
require __DIR__.'vendor/autoload.php';

Fal\Stick\Fw::createFromGlobals()
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

## Apache .htaccess example

```sh
# You should avoid using this file completely
# if you have access to httpd main server config file.
# Using this file slows down your Apache http server.
# Any directive that you can include in this file is better set in *Directory* block,
# as it will have the *same effect with better performance*.
#
# (A suggestion from apache.)

# Disable directory index?
#
# Options -Indexes

# Override entry file
#
DirectoryIndex index.php

# Enable rewrite engine and route requests to framework
#
RewriteEngine On

# Some servers require you to specify the `RewriteBase` directive
# In such cases, it should be the path (relative to the document root)
# containing this .htaccess file
#
# RewriteBase /

# Prevent access to these directory or files
# It is just an example, use it wise.
#
# RewriteRule ^(app|var|vendor)\/|\.env.php$ - [R=404]

# If you have problem with default configuration,
# uncomment three line below to allow request methods below.
#
# <Limit GET HEAD POST PUT DELETE OPTIONS>
#   Require all granted
# </Limit>

RewriteCond %{REQUEST_FILENAME} !-l
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule .* index.php [L,QSA]
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]