<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture;

use Fal\Stick\App;

class ControllerClass
{
    public function __construct()
    {
        # code...
    }

    public function custom(App $app)
    {
        $app->set('custom', 'foo');
    }

    public function custompair(App $app)
    {
        $app->set('custompair', 'foo');
    }
}
