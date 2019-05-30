<?php

use Fal\Stick\Fw;
use Fal\Stick\Cli\Command;
use Fal\Stick\Cli\Console;

/**
 * This file is an entry point to see framework action in console.
 */

require __DIR__.'/../vendor/autoload.php';

$config = array(
    'DEBUG' => 1,
    'TEMP' => dirname(__DIR__).'/var/',
    'TRACE_CLEAR' => dirname(__DIR__).'/',
);
$fw = Fw::createFromGlobals($config)->emulateCliRequest();
(new Console($fw))
    ->add(
        Command::create(
            'welcome',
            function (Console $console) {
                $console
                    ->writeln('Welcome')
                    ->writeln()
                    ->writeln('<info>Info: You are seing this line in green</>')
                    ->writeln('<error>Error: You are seing this line in red block</>')
                    ->writeln('<comment>Comment: You are seing this line in yellow</>')
                    ->writeln('<question>Question: You are seing this line in cyan block</>')
                ;
            }
        )
    )->add(
        Command::create(
            'dimension',
            function (Console $console) {
                $console
                    ->writeln('Width: <comment>'.$console->getWidth().'</>')
                    ->writeln('Height: <comment>'.$console->getHeight().'</>')
                    ->writeln()
                    ->writeln('Try to resize then run this command again!');
                ;
            }
        )
    )
    ->run()
;
