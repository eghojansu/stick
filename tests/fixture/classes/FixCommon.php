<?php

namespace Fal\Stick\Test\fixture\classes;

class FixCommon
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
