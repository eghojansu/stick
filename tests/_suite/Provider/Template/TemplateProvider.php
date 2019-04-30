<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\TestSuite\Provider\Template;

use Fal\Stick\TestSuite\MyTestCase;

class TemplateProvider
{
    public function render()
    {
        return array(
            array(
                'notfoobar.',
                MyTestCase::fixture('/template/foo.php'),
                'foo.shtml',
            ),
            array(
                MyTestCase::read('/template/layout.html'),
                MyTestCase::fixture('/template/layout.php'),
                'layout.shtml',
            ),
            array(
                null,
                MyTestCase::fixture('/template/parent_noblock.php'),
                'parent_noblock.shtml',
            ),
            array(
                null,
                MyTestCase::fixture('/template/stop_noblock.php'),
                'stop_noblock.shtml',
            ),
            array(
                MyTestCase::read('/template/complete.html'),
                MyTestCase::fixture('/template/complete.php'),
                'complete.shtml',
                array(
                    'page' => array('title' => 'Foo'),
                    'items' => array('foo' => 'bar'),
                    'you' => array(
                        'see' => array(
                            'me' => array(
                                'as' => array(
                                    'array' => array(
                                        'access' => 'right?',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }
}
