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

namespace Fal\Stick\TestSuite\Provider\Web;

class ReceiverProvider
{
    public function request()
    {
        return array(
            'default engine' => array(
                array(
                    'body' => 'Test entry point.',
                    'engine' => 'curl',
                ),
                'http://localhost:2010/test-entry',
            ),
            'engine curl' => array(
                array(
                    'body' => 'Test entry point.',
                ),
                'http://localhost:2010/test-entry',
                null,
                'curl',
            ),
            'engine stream' => array(
                array(
                    'body' => 'Test entry point.',
                ),
                'http://localhost:2010/test-entry',
                null,
                'stream',
            ),
            'engine socket' => array(
                array(
                    'body' => 'Test entry point.',
                ),
                'http://localhost:2010/test-entry',
                null,
                'socket',
            ),
            'invalid scheme' => array(
                null,
                'invalid://localhost:2010/test-entry',
            ),
            'pretend as local URL' => array(
                array(
                    'body' => 'Test entry point.',
                ),
                '/test-entry',
                null,
                null,
                array(
                    'HOST' => 'localhost',
                    'PORT' => 2010,
                ),
            ),
            'with string header' => array(
                array(
                    'body' => "Test entry point.\nHeaders:\nCustomheader: Foo",
                ),
                'http://localhost:2010/test-entry?headers=Customheader',
                array(
                    'headers' => 'CUSTOMHEADER: Foo',
                ),
            ),
            'url with http basic' => array(
                array(
                    'body' => "Test entry point.\nHeaders:\nAuthorization: Basic ".base64_encode('foo:bar'),
                ),
                'http://foo:bar@localhost:2010/test-entry?headers=Authorization',
            ),
            'post with data' => array(
                array(
                    'body' => "Test entry point.\nPost:\nfoo: bar",
                ),
                'http://localhost:2010/test-entry',
                array(
                    'method' => 'POST',
                    'content' => 'foo=bar',
                ),
            ),
            'cached' => array(
                array(
                    'body' => 'Test entry point.',
                ),
                'http://localhost:2010/test-entry?cache=1',
                array(
                    'headers' => array(
                        'Docache: 1',
                    ),
                ),
                null,
                array(
                    'CACHE' => 'fallback',
                ),
            ),
            'engine curl with invalid proxy' => array(
                array(
                    'body' => '',
                    'error' => 'Could not resolve proxy: foo',
                ),
                'http://localhost:2010/test-entry',
                array(
                    'proxy' => 'foo',
                ),
                'curl',
            ),
        );
    }
}
