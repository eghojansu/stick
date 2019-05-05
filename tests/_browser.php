<?php

use Fal\Stick\Fw;
use Fal\Stick\Security\Jwt;
use Fal\Stick\Web\Receiver;
use Fal\Stick\Security\Auth;
use Fal\Stick\TestSuite\Classes\SimpleUser;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;

/**
 * This file is an entry point to see framework action in browser.
 *
 * Run this file (adjust directory):
 *   php -S localhost:2010 _browser.php
 */

require __DIR__.'/../vendor/autoload.php';

Fw::createFromGlobals()
    ->mset(array(
        'DEBUG' => 1,
        'TEMP' => dirname(__DIR__).'/var/',
        'SERVICES' => array(
            'auth' => function($fw) {
                $provider = new InMemoryUserProvider();
                $provider->addUser(new SimpleUser('1', 'admin', 'admin', array('admin', 'user')));
                $provider->addUser(new SimpleUser('2', 'user', 'user', array('user')));

                return new Auth($fw, $provider, new PlainPasswordEncoder(), array(
                    'rules' => array(
                        '^/auth/session$' => array(
                            'roles' => 'admin',
                            'login' => '/auth/session/login',
                            'home' => '/auth/session',
                        ),
                        '^/auth/jwt$' => array(
                            'roles' => 'admin',
                            'login' => '/auth/jwt/login',
                            'home' => '/auth/jwt',
                        ),
                    ),
                ));
            },
            'jwt' => function() {
                return new Jwt('foo');
            },
        ),
        'toUser' => function(array $user) {
            return new SimpleUser($user['id'], $user['username'], null, $user['roles'], $user['expired']);
        },
        'content' => function($fw, array $content) {
            $content += array(
                '%title%' => 'Stick Framework',
                '%content%' => 'None',
            );

            return strtr('<!DOCTYPE html>'.
            '<html lang="en">'.
            '<head>'.
                '<meta charset="UTF-8">'.
                '<title>Stick Framework Homepage</title>'.
            '</head>'.
            '<body>'.
                '<a href="'.$fw->path('home').'">Home</a>'.
                '<hr>'.
                '<h1>%title%</h1>'.
                '%content%'.
            '</body>'.
            '</html>', $content);
        },
    ))
    ->route('GET home / sync', function($fw) {
        return $fw->content(array(
            '%content%' => '<ul>'.
                '<li><a href="'.$fw->path('auth/basic').'">Auth Basic</a></li>'.
                '<li><a href="'.$fw->path('auth/session').'">Auth Session</a></li>'.
                '<li><a href="'.$fw->path('auth/jwt').'">Auth JWT</a></li>'.
                '<li><a href="'.$fw->path('request').'">Request</a></li>'.
                '<li><a href="'.$fw->path('ws-client').'">Web Socket Client</a></li>'.
            '</ul>',
        ));
    })
    ->route('GET /auth/basic sync', function($fw) {
        // auto-protected with http basic
        $fw->auth->options->remember_session = false;

        if ($fw->auth->basic()) {
            return false;
        }

        return $fw->content(array(
            '%title%' => 'Auth Basic Test',
            '%content%' => 'Welcome back, '.$fw->auth->getUser()->getUsername().'!<br><br>'.
                '<strong>You cannot logout until browser window closed!</strong><br><br>'.
                'Used Realm: <strong>'.$fw->auth->options['basic_realm'].'</strong>',
        ));
    })
    ->route('GET /auth/session sync', function($fw) {
        // auto-protected with guard
        if ($fw->auth->guard()) {
            return false;
        }

        return $fw->content(array(
            '%title%' => 'Auth Session Test',
            '%content%' => 'Welcome back, '.$fw->auth->getUser()->getUsername().'!<br><br>'.
                '<a href="'.$fw->path('auth/session/logout').'">Logout</a>',
        ));
    })
    ->route('GET|POST /auth/session/logout sync', function($fw) {
        $fw->auth->logout();
        $fw->reroute('home');
    })
    ->route('GET|POST /auth/session/login sync', function($fw) {
        // auto-protected with guard
        if ($fw->auth->guard()) {
            return false;
        }

        if ($fw->auth->login($fw->isMethod('post'))) {
            return $fw->reroute();
        }

        return $fw->content(array(
            '%title%' => 'Auth Session Login',
            '%content%' => 'Error: '.$fw->auth->error().'<br>'.
                '<form method="post">'.
                '<input type="text" name="username" placeholder="Username">'.
                '<input type="password" name="password" placeholder="Password">'.
                '<button type="submit">Login</button>'.
                '</form>',
        ));
    })
    ->route('GET /auth/jwt sync', function($fw) {
        // auto-protected with jwt guard
        $fw->auth->options->remember_session = false;
        $token = $fw['GET.token'];

        // no bearer?
        if (!$token) {
            // request one
            return $fw->reroute('/auth/jwt/login');
        }

        // manual set for authorization (just an illustration)
        $fw->set('REQUEST.Authorization', 'Bearer '.$token);

        // this line guard our script
        $fw->auth->jwt($fw->jwt, $fw->toUser);

        ob_start();
        var_dump($fw['SESSION']);
        $session = ob_get_clean();

        return $fw->content(array(
            '%title%' => 'Auth JWT Test',
            '%content%' => 'Welcome back, '.$fw->auth->getUser()->getUsername().'!<br><br>'.
                'JWT Token: '.$token.'<br><br>'.
                'Session: '.$session,
        ));
    })
    ->route('GET|POST /auth/jwt/login sync', function($fw) {
        // auto-protected with jwt guard
        $fw->auth->options->remember_session = false;

        if ($fw->auth->login($fw->isMethod('post'))) {
            $user = $fw->auth->getUser();
            $token = $fw->jwt->encode(array(
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
                'expired' => $user->isCredentialsExpired(),
            ));

            return $fw->reroute('/auth/jwt?token='.$token);
        }

        return $fw->content(array(
            '%title%' => 'Auth JWT Login',
            '%content%' => 'Error: '.$fw->auth->error().'<br>'.
                '<form method="post">'.
                '<input type="text" name="username" placeholder="Username">'.
                '<input type="password" name="password" placeholder="Password">'.
                '<button type="submit">Login</button>'.
                '</form>',
        ));
    })
    ->route('GET request /request/@mode:default', function($fw, $params) {
        $receiver = new Receiver($fw);
        $mode = 'default' === $params['mode'] ? null : $params['mode'];
        $url = 'https://github.com/eghojansu/stick';

        ob_start();
        $response = $receiver->request($url, null, $mode);
        ini_set('xdebug.var_display_max_children', '-1');
        ini_set('xdebug.var_display_max_data', '-1');
        var_dump($response);
        $content = ob_get_clean();

        $content = '<ul>'.
            '<li><a href="'.$fw->path('request/curl').'">Request CURL</a></li>'.
            '<li><a href="'.$fw->path('request/stream').'">Request Stream</a></li>'.
            '<li><a href="'.$fw->path('request/socket').'">Request Socket</a></li>'.
            '</ul><br><br>Response:<br><br>'.
            '<div style="overflow: auto; border: solid 1px red; margin-bottom: 50px">'.$content.'</div>';

        return $fw->content(array(
            '%title%' => 'Request: '.$params['mode'],
            '%content%' => $content,
        ));
    })
    ->route('GET|POST /test-entry', function($fw) {
        if ($fw['REQUEST.Cachenotmodified']) {
            return $fw->status(304);
        }

        if ($fw['REQUEST.Docache']) {
            $fw->expire(5);
        }

        $eol = "\n";

        echo 'Test entry point.';

        if ($fw['GET.redirected']) {
            echo $eol.'redirected';
        }

        if ($fw->isMethod('post')) {
            echo $eol.'Post:';

            foreach ($fw['POST'] as $key => $value) {
                echo $eol.$key.': '.$value;
            }
        }

        if ($fw['GET.headers']) {
            echo $eol.'Headers:';

            foreach (explode('-', $fw['GET.headers']) as $header) {
                echo $eol.$header.': '.$fw['REQUEST.'.$header];
            }
        }
    })
    ->route('GET /ws-client', function($fw) {
        $content = <<<HTML
<div style="width: 300px; margin: 30px auto; padding: 10px">
<input type="text" id="input" placeholder="Type message then press Enter..." style="width: 80px; margin: 10px auto; width: 100%; height: 50px; font-size: 14px; text-align: center">
<ul id="messages">
</ul>
</div>
<script>
var socket = new window.WebSocket('ws://{$fw['HOST']}:2011');
var input = document.getElementById('input');
var messages = document.getElementById('messages');
var addMessage = function(message) {
    var li = document.createElement('li');
    li.innerHTML = message;

    messages.prepend(li);
};

socket.addEventListener('open', function(event) {
    addMessage('Gate opened!');
});
socket.addEventListener('close', function(event) {
    addMessage('Gate closed!');
});
socket.addEventListener('error', function(event) {
    addMessage('Something wrong!');
});
socket.addEventListener('message', function(event) {
    addMessage(event.data);
});

input.addEventListener('keydown', function(event) {
    if (event.keyCode == 13) {
        event.preventDefault();
        socket.send(input.value);
        input.value = '';
    }
});
</script>
HTML;
        return $fw->content(array(
            '%title%' => 'Ws Client',
            '%content%' => $content,
        ));
    })
    ->run();
