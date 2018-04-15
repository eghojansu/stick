<?php

use Fal\Stick\App;
use Fal\Stick\Cli;

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// To run this test:
// php -f /path/to/this-file.php

$app = require(__DIR__ . '/../src/autoload.php');

$app->route('GET /', function(App $shouldbeSameApp, Cli $cli) use ($app) {
    $cli->write('Given app instance is same: ');
    if ($shouldbeSameApp === $app) {
        $cli->writeln('true', 'white:green');
    } else {
        $cli->writeln('false', 'white:red');
    }
    $cli->writeln('Simple test is passed', 'green');
});
$app->run();
