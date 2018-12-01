<?php

return array(
    'all' => true,
    'foo' => 'bar',
    'one' => 1,
    'one_point_five' => 1.5,
    'bool_true' => true,
    'bool_false' => false,
    'null_null' => null,
    'arr' => array('foo', 'bar', 'baz' => 'qux'),
    'CONFIGS' => __DIR__.'/embedded.php',
    'ROUTES' => array(
        array('GET|POST /home', 'Fal\\Stick\\Test\\SimpleController->home', 0, 0),
    ),
    'REDIRECTS' => array(
        array('GET /redirect-to-home', '/home'),
    ),
    'RESTS' => array(
        array('/books', 'Fal\\Stick\\Test\\BookController'),
    ),
    'CONTROLLERS' => array(
        array('Fal\\Stick\\Test\\SimpleController', array(
            'GET /controller' => 'home',
        )),
    ),
    'RULES' => array(
        array('constructor', array(
            'class' => 'Fal\\Stick\\Test\\Constructor',
            'args' => array('name' => 'from config'),
        )),
    ),
    'EVENTS' => array(
        array('no_constructor', 'Fal\\Stick\\Test\\NoConstructor->getName'),
    ),
    'SUBSCRIBERS' => array(
        'Fal\\Stick\\Test\\NoConstructorSubscriber',
    ),
);
