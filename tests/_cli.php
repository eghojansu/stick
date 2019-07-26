<?php

use Fal\Stick\Cli\Command;
use Fal\Stick\Cli\Console;
use Fal\Stick\Fw;

/**
 * This file is an entry point to see framework action in console.
 */

require __DIR__.'/../vendor/autoload.php';

chdir(__DIR__);

(new Console(
    Fw::createFromGlobals(array(
        'DEBUG' => 1,
        'TEMP' => dirname(__DIR__).'/var/',
        'TRACE_CLEAR' => dirname(__DIR__).'/',
    )),
    array(
        'default_override' => array(
            'lint' => array(
                'directories' => '../src,../tests',
            ),
            'server:run' => array(
                'address' => '0.0.0.0:2010',
                'docroot' => '.',
                'router' => '_browser.php',
            ),
        ),
    )
))
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
            },
            'Console style parser demo'
        )
    )
    ->add(
        Command::create(
            'dimension',
            function (Console $console) {
                $console
                    ->writeln('Width: <comment>'.$console->getWidth().'</>')
                    ->writeln('Height: <comment>'.$console->getHeight().'</>')
                    ->writeln()
                    ->writeln('Try to resize then run this command again!');
                ;
            },
            'Show console height and width'
        )
    )
    ->run()
;
