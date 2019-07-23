<?php

declare(strict_types=1);

namespace Fal\Stick\TestSuite\Provider\Util;

use Fal\Stick\TestSuite\MyTestCase;

class CommonProvider
{
    public function snakeCase()
    {
        return array(
            array('foo', 'foo'),
            array('snake_case', 'SnakeCase'),
            array('snake_case', 'snakeCase'),
            array('snake_case', 'snake_case'),
            array('snake_case_long_text', 'snakeCaseLongText'),
        );
    }

    public function camelCase()
    {
        return array(
            array('foo', 'foo'),
            array('camelCase', 'camel_case'),
            array('camelCase', 'camel-case'),
            array('camelCase', 'Camel_Case'),
            array('camelCase', 'camelCase'),
            array('camelCaseLongText', 'camel_Case_long_Text'),
        );
    }

    public function pascalCase()
    {
        return array(
            array('Foo', 'foo'),
            array('PascalCase', 'pascal_case'),
            array('PascalCase', 'pascal-case'),
            array('PascalCase', 'pascalCase'),
            array('PascalCase', 'PascalCase'),
            array('PascalCaseLongText', 'pascal_Case_long_Text'),
        );
    }

    public function titleCase()
    {
        return array(
            array('Foo', 'foo'),
            array('Title Case', 'title_case'),
            array('Title Case', 'TitleCase'),
            array('Title Case Long Text', 'title_case_long_text'),
        );
    }

    public function dashCase()
    {
        return array(
            array('Foo', 'foo'),
            array('Dash-Case', 'DASH_CASE'),
            array('Dash-Case', 'dash_case'),
            array('Dash-Case', 'Dash-Case'),
            array('Dash-Case-Long-Text', 'DASH_CASE_Long_Text'),
        );
    }

    public function classname()
    {
        return array(
            array('Foo', 'Foo'),
            array('Bar', 'Foo\\Bar'),
            array('Baz', 'Foo\\Bar\\Baz'),
            array('DateTime', new \DateTime()),
        );
    }

    public function arrColumn()
    {
        return array(
            'no filter' => array(
                array(
                    'foo' => 1,
                    'bar' => null,
                    'baz' => 0,
                ),
                array(
                    'foo' => array('v' => 1),
                    'bar' => array('v' => null),
                    'baz' => array('v' => 0),
                ),
                'v',
            ),
            'with filter' => array(
                array(
                    'foo' => 1,
                ),
                array(
                    'foo' => array('v' => 1),
                    'bar' => array('v' => null),
                    'baz' => array('v' => 0),
                ),
                'v',
                false,
            ),
        );
    }

    public function read()
    {
        return array(
            array('foo', MyTestCase::fixture('/files/foo.txt')),
            array('', MyTestCase::fixture('/files/not_exists.txt')),
        );
    }
}
