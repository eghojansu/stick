<?php

use Fal\Stick\Fw;
use Fal\Stick\WebSocket\Agent;
use Fal\Stick\WebSocket\Server;

/**
 * This file is an websocket server example.
 *
 * Run this file (adjust directory):
 *   php _ws.php
 */

require __DIR__.'/../vendor/autoload.php';

function trace($line) {
    echo "\r".date('H:i:s').' ['.memory_get_usage(true).'] '.$line.PHP_EOL;
}

chdir(__DIR__);
ini_set('default_socket_timeout',3);

(new Server(
    (new Fw())
        ->on('server.error', function(Server $server) {
            if ($err = socket_last_error()) {
                trace(socket_strerror($err));
                socket_clear_error();
            }

            if ($err=error_get_last()) {
                trace($err['message']);
            }
        })
        ->on('server.start', function(Server $server) {
            trace('WebSocket server started');
        })
        ->on('server.stop', function(Server $server) {
            trace('Shutting down');
        })
        ->on('agent.idle', function(Agent $agent) {
            // agent idle
        })
        ->on('agent.connect', function(Agent $agent) {
            trace('(0x00'.$agent->uri().') '.$agent->id().' connected '.'<'.(count($agent->server->agents())+1).'>');
        })
        ->on('agent.disconnect', function(Agent $agent) {
            trace('(0x08'.$agent->uri().') '.$agent->id().' disconnected');

            if ($err=socket_last_error()) {
                trace(socket_strerror($err));
                socket_clear_error();
            }
        })
        ->on('agent.receive', function(Agent $agent, $op, $data) {
            switch($op) {
                case Agent::MASK_PONG:
                    $text='pong';
                    break;
                case Agent::MASK_TEXT:
                    $data=trim($data);
                case Agent::MASK_BINARY:
                    $text='data';
                    break;
            }

            trace('(0x'.str_pad(dechex($op),2,'0',STR_PAD_LEFT).$agent->uri().') '.$agent->id().' '.$text.' received');

            if ($op==Agent::MASK_TEXT && $data) {
                // send data back to all agents
                $agents = $agent->server->agents();
                foreach ($agents as $a) {
                    $a->send(Agent::MASK_TEXT, $data.count($agents));
                }
            }
        })
        ->on('agent.send', function(Agent $agent, $op, $data) {
            switch($op) {
                case Agent::MASK_PING:
                    $text='ping';
                    break;
                case Agent::MASK_TEXT:
                    $data=trim($data);
                case Agent::MASK_BINARY:
                    $text='data';
                    break;
            }

            trace('(0x'.str_pad(dechex($op),2,'0',STR_PAD_LEFT).$agent->uri().') '.$agent->id().' '.$text.' sent');
        }),
    'tcp://0.0.0.0:2011'
))->run();
