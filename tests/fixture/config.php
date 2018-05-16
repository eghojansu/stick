<?php

return [
    'foo' => 'bar',
    'bar' => 'baz',
    'routes' => [
        ['GET /', function () { return 'registered from config'; }],
    ],
    'qux' => 'quux',
    'redirects' => [
        ['GET /foo', '/bar'],
    ],
    'maps' => [
        ['GET /baz', 'FakeController'],
    ],
    'arr' => range(1,3),
    'configs' => __DIR__ . '/subconfig.php',
];
