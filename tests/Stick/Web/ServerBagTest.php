<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 26, 2019 23:12
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\ServerBag;
use PHPUnit\Framework\TestCase;

class ServerBagTest extends TestCase
{
    /**
     * @dataProvider getHeadersProvider
     */
    public function testGetHeaders($expected, $server)
    {
        $bag = new ServerBag($server);

        $this->assertEquals($expected, $bag->getHeaders());
    }

    public function getHeadersProvider()
    {
        return array(
            array(array(), null),
            array(
                array(
                    'CONTENT_LENGTH' => 0,
                    'X_REQUESTED_WITH' => 'Foo',
                ),
                array(
                    'CONTENT_LENGTH' => 0,
                    'HTTP_X_REQUESTED_WITH' => 'Foo',
                ),
            ),
        );
    }
}
