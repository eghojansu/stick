<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\classes;

use Fal\Stick\App;

class FixController
{
    public function custom(App $app)
    {
        $app->set('custom', 'foo');
    }

    public function custompair(App $app)
    {
        $app->set('custompair', 'foo');
    }
}
