<?php

namespace Fal\Stick\Test\fixture;

class CommonClass
{
    const PREFIX_FOO = 'bar';
    const BAR = 'baz';

    public static function prefixQux($str)
    {
        return 'qux'.$str;
    }

    public function prefixFoo($str)
    {
        return 'foo'.$str;
    }
}
