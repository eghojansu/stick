<?php

use Fal\Stick\Fw;
use Fal\Stick\Util\Cli;

return array(
    'env_file' => __DIR__.'/commands_env.php',
    'commands' => array(
        'custom' => function(Fw $fw, Cli $cli) {
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