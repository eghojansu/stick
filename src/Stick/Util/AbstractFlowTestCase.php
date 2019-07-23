<?php

/**
 * This file is part of the eghojansu/stick.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Util;

use Fal\Stick\Fw;
use PHPUnit\Framework\TestCase;

/**
 * Functional test helper.
 *
 * Note that Fw instance *SHOULD* be provided in any way.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
abstract class AbstractFlowTestCase extends TestCase
{
    /** @var array */
    const PICK = array(
        'AGENT',
        'AJAX',
        'ALIAS',
        'BODY',
        'CLI',
        'COOKIE',
        'ERROR',
        'GET',
        'OUTPUT',
        'PARAMS',
        'PATH',
        'PATTERN',
        'POST',
        'REQUEST',
        'RESPONSE',
        'SERVER',
        'STATUS',
        'TEXT',
        'URI',
        'VERB',
    );

    /** @var Fw */
    protected $fw;

    /** @var int */
    protected $ptr = -1;

    /** @var array */
    protected $history = array();

    /**
     * Reset history.
     *
     * @return AbstractFlowTestCase
     */
    public function first(): AbstractFlowTestCase
    {
        $this->ptr = 0;

        return $this;
    }

    /**
     * Go back to previous response.
     *
     * @return AbstractFlowTestCase
     */
    public function prev(): AbstractFlowTestCase
    {
        if ($this->ptr > 0) {
            --$this->ptr;
        }

        return $this;
    }

    /**
     * Forward to next response.
     *
     * @return AbstractFlowTestCase
     */
    public function next(): AbstractFlowTestCase
    {
        if ($this->ptr + 1 < count($this->history)) {
            ++$this->ptr;
        }

        return $this;
    }

    /**
     * Forward to last response.
     *
     * @return AbstractFlowTestCase
     */
    public function last(): AbstractFlowTestCase
    {
        $this->ptr = max(0, count($this->history) - 1);

        return $this;
    }

    /**
     * Returns current response content by given key.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->history[$this->ptr][1][$key] ?? $default;
    }

    /**
     * Returns current response content.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->history[$this->ptr][1] ?? array();
    }

    /**
     * Returns current request route.
     *
     * @return string
     */
    public function requestRoute(): string
    {
        return $this->history[$this->ptr][0] ?? '';
    }

    /**
     * Make new request, proxy to framework mock method.
     *
     * @param string      $verb
     * @param string      $url
     * @param array|null  $arguments
     * @param string|null $body
     * @param array|null  $server
     *
     * @return AbstractFlowTestCase
     */
    public function request(
        string $verb,
        string $url,
        array $arguments = null,
        string $body = null,
        array $server = null
    ): AbstractFlowTestCase {
        $route = $verb.' '.$url;

        $this->history[++$this->ptr] = array(
            $route,
            $this->fw->mock($route, $arguments, $server, $body)->mget(self::PICK),
        );

        return $this;
    }

    /**
     * Make *GET* request.
     *
     * @param string      $url
     * @param array|null  $arguments
     * @param string|null $body
     * @param array|null  $server
     *
     * @return AbstractFlowTestCase
     */
    public function visit(
        string $url,
        array $arguments = null,
        string $body = null,
        array $server = null
    ): AbstractFlowTestCase {
        return $this->request('GET', $url, $arguments, $body, $server);
    }

    /**
     * Make *POST* request.
     *
     * @param string      $url
     * @param array|null  $arguments
     * @param string|null $body
     * @param array|null  $server
     *
     * @return AbstractFlowTestCase
     */
    public function post(
        string $url,
        array $arguments = null,
        string $body = null,
        array $server = null
    ): AbstractFlowTestCase {
        return $this->request('POST', $url, $arguments, $body, $server);
    }

    /**
     * Expect response status code is informational.
     *
     * @return AbstractFlowTestCase
     */
    public function expectInformational(): AbstractFlowTestCase
    {
        $status = $this->get('STATUS', 0);

        $this->assertTrue($status >= 100 && $status <= 103, "Response is not informational [$status]");

        return $this;
    }

    /**
     * Expect response status code is successful.
     *
     * @return AbstractFlowTestCase
     */
    public function expectSuccessful(): AbstractFlowTestCase
    {
        $status = $this->get('STATUS', 0);

        $this->assertTrue($status >= 200 && $status <= 206, "Response is not successful [$status]");

        return $this;
    }

    /**
     * Expect response status code is a redirection.
     *
     * @return AbstractFlowTestCase
     */
    public function expectRedirection(): AbstractFlowTestCase
    {
        $status = $this->get('STATUS', 0);

        $this->assertTrue($status >= 300 && $status <= 307, "Response is not a redirection [$status]");

        return $this;
    }

    /**
     * Expect response status code is an error.
     *
     * @return AbstractFlowTestCase
     */
    public function expectRequestError(): AbstractFlowTestCase
    {
        $status = $this->get('STATUS', 0);

        $this->assertTrue($status >= 400 && $status <= 417, "Request is not an error [$status]");

        return $this;
    }

    /**
     * Expect response is server error.
     *
     * @return AbstractFlowTestCase
     */
    public function expectServerError(): AbstractFlowTestCase
    {
        $status = $this->get('STATUS', 0);

        $this->assertTrue($status >= 500 && $status <= 505, "Response is not server error [$status]");

        return $this;
    }

    /**
     * Expect response is not found.
     *
     * @return AbstractFlowTestCase
     */
    public function expectNotFound(): AbstractFlowTestCase
    {
        $status = $this->get('STATUS', 0);

        $this->assertEquals(404, $status, "Response is found [$status]");

        return $this;
    }

    /**
     * Expect response is forbidden.
     *
     * @return AbstractFlowTestCase
     */
    public function expectForbidden(): AbstractFlowTestCase
    {
        $status = $this->get('STATUS', 0);

        $this->assertEquals(403, $status, "Response is not forbidden [$status]");

        return $this;
    }

    /**
     * Expect response is not allowed.
     *
     * @return AbstractFlowTestCase
     */
    public function expectNotAllowed(): AbstractFlowTestCase
    {
        $status = $this->get('STATUS', 0);

        $this->assertEquals(405, $status, "Response is allowed [$status]");

        return $this;
    }

    /**
     * Expect status code is equals to expected value.
     *
     * @param int $code
     *
     * @return AbstractFlowTestCase
     */
    public function expectStatusCode(int $code): AbstractFlowTestCase
    {
        $this->assertEquals($code, $this->get('STATUS'));

        return $this;
    }

    /**
     * Expect status text is equals to expected value.
     *
     * @param string $text
     *
     * @return AbstractFlowTestCase
     */
    public function expectStatus(string $text): AbstractFlowTestCase
    {
        $this->assertEquals($text, $this->get('TEXT'));

        return $this;
    }

    /**
     * Expect status text contains expected value.
     *
     * @param string $text
     *
     * @return AbstractFlowTestCase
     */
    public function expectStatusContains(string $text): AbstractFlowTestCase
    {
        $this->assertStringContainsString($text, $this->get('TEXT'));

        return $this;
    }

    /**
     * Expect output is equals to expected value.
     *
     * @param mixed $output
     *
     * @return AbstractFlowTestCase
     */
    public function expectOutput($output): AbstractFlowTestCase
    {
        $this->assertEquals($output, $this->get('OUTPUT'));

        return $this;
    }

    /**
     * Expect output contains expected value.
     *
     * @param string $output
     *
     * @return AbstractFlowTestCase
     */
    public function expectOutputContains(string $output): AbstractFlowTestCase
    {
        $this->assertStringContainsString($output, $this->get('OUTPUT'));

        return $this;
    }

    /**
     * Expect uri is equals to expected value.
     *
     * @param string $text
     *
     * @return AbstractFlowTestCase
     */
    public function expectUri(string $text): AbstractFlowTestCase
    {
        $this->assertEquals($text, $this->get('URI'));

        return $this;
    }

    /**
     * Expect uri contains expected value.
     *
     * @param string $text
     *
     * @return AbstractFlowTestCase
     */
    public function expectUriContains(string $text): AbstractFlowTestCase
    {
        $this->assertStringContainsString($text, $this->get('URI'));

        return $this;
    }

    /**
     * Expect callable returns true.
     *
     * @param callable $cb
     *
     * @return AbstractFlowTestCase
     */
    public function expectTrue(callable $cb): AbstractFlowTestCase
    {
        $this->assertTrue(
            $cb($this->all(), $this->requestRoute(), $this->fw),
            'Callback execution returning false'
        );

        return $this;
    }

    /**
     * Expect callable returns false.
     *
     * @param callable $cb
     *
     * @return AbstractFlowTestCase
     */
    public function expectFalse(callable $cb): AbstractFlowTestCase
    {
        $this->assertFalse(
            $cb($this->all(), $this->requestRoute(), $this->fw),
            'Callback execution returning true'
        );

        return $this;
    }

    /**
     * Expect key content is equals to expected value.
     *
     * @param string $key
     * @param mixed  $expected
     *
     * @return AbstractFlowTestCase
     */
    public function expectEquals(string $key, $expected): AbstractFlowTestCase
    {
        $this->assertEquals(
            $expected,
            $this->get($key),
            "$key's value is not equals to expected value"
        );

        return $this;
    }

    /**
     * Expect key content contains expected value.
     *
     * @param string $key
     * @param string $expected
     *
     * @return AbstractFlowTestCase
     */
    public function expectContains(string $key, string $expected): AbstractFlowTestCase
    {
        $this->assertStringContainsString(
            $expected,
            $this->get($key),
            "$key's value not contains expected value"
        );

        return $this;
    }
}
