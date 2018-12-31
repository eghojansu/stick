<?php

use Fal\Stick\Core;
use Fal\Stick\Cli;

return array(
    'env_file' => __DIR__.'/commands_env.php',
    'commands' => array(
        'custom' => function(Core $fw, Cli $cli) {
            $cli->writeln('Custom command executed with env: %s!', $fw['command_env']);
        },
        'custom2' => array(
            'run' => function(Cli $cli, array $options, array $args) {
                $cli->writeln('Custom command2 executed with arg-foo=%s and option-config=%s!', $args['foo'], var_export($options['config'] ?? null, true));
            },
            'desc' => null,
            'args' => array(
                array('foo', 'bar', 'Foo'),
            ),
            'options' => array(
                array('config', null, null, 'Config'),
            ),
        ),
    ),
);
