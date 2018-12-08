<?php

use Fal\Stick\Cli;
use Fal\Stick\Fw;

(require dirname(__DIR__).'/src/bootstrap.php')
    ->emulateCliRequest()
    ->route('GET /', function(Fw $fw, Cli $cli) {
        $cli->writeln('<question>  We are good?  </question>');
        $cli->writeln();
        $cli->writeln('  Package: <info>%s</info>', Fw::PACKAGE);
        $cli->writeln('  Version: <comment>%s</comment>', Fw::VERSION);
    })
    ->run()
;
