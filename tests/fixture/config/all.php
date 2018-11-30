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
    'configs' => __DIR__.'/embedded.php',
    'routes' => array(
        array('GET|POST /home', 'Fal\\Stick\\Test\\SimpleController->home', 0, 0),
    ),
    'redirects' => array(
        array('GET /redirect-to-home', '/home'),
    ),
    'rests' => array(
        array('/books', 'Fal\\Stick\\Test\\BookController'),
    ),
    'controllers' => array(
        array('Fal\\Stick\\Test\\SimpleController', array(
            'GET /controller' => 'home',
        )),
    ),
    'rules' => array(
        array('constructor', array(
            'class' => 'Fal\\Stick\\Test\\Constructor',
            'args' => array('name' => 'from config'),
        )),
    ),
    'events' => array(
        array('no_constructor', 'Fal\\Stick\\Test\\NoConstructor->getName'),
    ),
    'subscribers' => array(
        'Fal\\Stick\\Test\\NoConstructorSubscriber',
    ),
);
