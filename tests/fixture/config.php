<?php

use Fal\Stick\Test\fixture\controller\MapGetController;
use Fal\Stick\Test\fixture\services\NoConstructorClass;

return [
    'foo' => 'bar',
    'bar' => 'baz',
    'PREMAP' => 'map',
    'routes' => [
        ['GET /', function () {
            return 'registered from config';
        }],
    ],
    'qux' => 'quux',
    'redirects' => [
        ['GET /foo', '/'],
    ],
    'maps' => [
        ['GET /bar', MapGetController::class],
    ],
    'arr' => range(1, 3),
    'configs' => __DIR__.'/subconfig.php',
    'rules' => [
        ['foo', NoConstructorClass::class],
    ],
    'listeners' => [
        ['foo', function () {
        }],
    ],
    'groups' => [
        [['prefix'=>'/group'], function($app) {
            $app->route('GET /index', function() {
                return 'group index';
            });
        }],
    ],
];
