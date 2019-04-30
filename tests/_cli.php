<?php

use Fal\Stick\Fw;
use Fal\Stick\Cli\Command;
use Fal\Stick\Cli\Console;

/**
 * This file is an entry point to see framework action in console.
 */

require __DIR__.'/../vendor/autoload.php';

Fw::createFromGlobals()
    ->emulateCliRequest()
    ->mset(array(
        'DEBUG' => 1,
        'TEMP' => dirname(__DIR__).'/var/',
        'TRACE_CLEAR' => dirname(__DIR__).'/',
        'SERVICES' => array(
            'console' => function() {
                return (new Console(array(
                    'prefix' => '',
                )))->add(Command::create('welcome')->setCode(function(Console $console) {
                    $console
                        ->writeln('Welcome')
                        ->writeln()
                        ->writeln('<info>Info: You are seing this line in green</info>')
                        ->writeln('<error>Error: You are seing this line in red block</error>')
                        ->writeln('<comment>Comment: You are seing this line in yellow</comment>')
                        ->writeln('<question>Question: You are seing this line in cyan block</question>')
                    ;
                }))->add(Command::create('dimension')->setCode(function(Console $console) {
                    $console
                        ->writeln('Width: <comment>'.$console->getWidth().'</comment>')
                        ->writeln('Height: <comment>'.$console->getHeight().'</comment>')
                        ->writeln()
                        ->writeln('Try to resize then run this command again!');
                    ;
                }));
            },
        ),
        'EVENTS.fw.boot' => function(Fw $fw) {
            $fw->console->register($fw);
        },
    ))
    ->run();
