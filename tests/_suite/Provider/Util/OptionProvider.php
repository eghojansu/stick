<?php

namespace Fal\Stick\TestSuite\Provider\Util;

class OptionProvider
{
    public function set()
    {
        return array(
            'normal set' => array(
                'foo',
                'foo',
                array('foo'),
            ),
            'array set' => array(
                array('foo' => 'bar', 'bar' => 'baz'),
                'foo',
                array(array('foo' => 'bar')),
                array('bar' => 'baz'),
            ),
            'with string type' => array(
                'foo',
                'foo',
                array('foo', 'string'),
            ),
            'with integer type' => array(
                1,
                'foo',
                array('foo', 'int'),
            ),
            'with object type' => array(
                $obj = new \DateTime(),
                'foo',
                array('foo', 'DateTime'),
                $obj,
            ),
            'invalid type' => array(
                'Option foo expect integer, given string type.',
                'foo',
                array(0, 'integer'),
                'foo',
                null,
                'UnexpectedValueException',
            ),
            'invalid object type' => array(
                'Option foo expect DateTime, given string type.',
                'foo',
                array(null, 'DateTime'),
                'foo',
                null,
                'UnexpectedValueException',
            ),
            'not an option' => array(
                'Option bar is not available.',
                'bar',
                null,
                null,
                null,
                'LogicException',
            ),
            'not allowed' => array(
                "Option foo only allow these values: 'bar','baz'.",
                'foo',
                array(null),
                'foo',
                'bar,baz',
                'OutOfBoundsException',
            ),
        );
    }
}
