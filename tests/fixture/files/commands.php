<?php

use Fal\Stick\Util\Cli;

return array(
    'commands' => array(
        'custom' => function(Cli $cli) {
            $cli->writeln('Custom command executed!');
        },
        'custom2' => array(function(Cli $cli) {
            $cli->writeln('Custom command2 executed!');
        }),
    ),
);
