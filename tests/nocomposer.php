<?php

use Fal\Stick\Cli;
use Fal\Stick\Core;

(require dirname(__DIR__).'/src/bootstrap.php')
    ->emulateCliRequest()
    ->route('GET /', function(Core $fw, Cli $cli) {
        $cli->writeln('<question>  We are good?  </question>');
        $cli->writeln();
        $cli->writeln('  Package: <info>%s</info>', Core::PACKAGE);
        $cli->writeln('  Version: <comment>%s</comment>', Core::VERSION);
    })
    ->run()
;
