<?php

use Fal\Stick\Fw;
use Fal\Stick\Util\Cli;

require dirname(__DIR__).'/src/Fw.php';

Fw::createFromGlobals()
    ->registerShutdownHandler()
    ->registerAutoload()
    ->emulateCliRequest()
    ->route('GET /', function(Fw $fw, Cli $cli) {
        $cli->writeln('<error>  You should see this error message in red box  </error>');
        $cli->writeln('  if you are in CLI mode which is</error> "<info>%s</info>".', var_export($fw['CLI'], true));
    })
    ->run()
;
