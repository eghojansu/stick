<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 29, 2018 15:46
 */

namespace Fal\Stick;

use IvoPetkov\HTML5DOMDocument;
use PHPUnit\Framework\TestCase;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Framework main class test helper, integrated with PHPUnit TestCase.
 *
 * It can be used to do behaviour or functional testing.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Test
{
    /**
     * @var Fw
     */
    protected $fw;

    /**
     * @var TestCase
     */
    protected $test;

    /**
     * @var CssSelectorConverter
     */
    protected $converter;

    /**
     * @var array
     */
    protected $history = array();

    /**
     * @var int
     */
    protected $pointer = -1;

    /**
     * @var bool
     */
    protected $success = false;

    /**
     * Class constructor.
     *
     * @param Fw            $fw
     * @param TestCase|null $test
     */
    public function __construct(Fw $fw, TestCase $test = null)
    {
        $this->fw = $fw;
        $this->test = $test;
    }

    /**
     * Returns fw instance.
     *
     * @return Fw
     */
    public function getFw(): Fw
    {
        return $this->fw;
    }

    /**
     * Returns current response.
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function response(string $get = 'content')
    {
        $response = $this->history[$this->pointer] ?? null;

        if (empty($response)) {
            throw new \LogicException('No response set! Please make request first.');
        }

        return $response[$get] ?? null;
    }

    /**
     * Returns latest test result.
     *
     * @return bool
     */
    public function success(): bool
    {
        return $this->success;
    }

    /**
     * Clear this tester environment.
     *
     * @return Test
     */
    public function clear(): Test
    {
        $this->history = array();
        $this->pointer = -1;

        return $this;
    }

    /**
     * Move to specific pointer.
     *
     * @param int $pointer
     *
     * @return Test
     */
    public function moveTo(int $pointer): Test
    {
        if (!isset($this->history[$pointer])) {
            throw new \LogicException(sprintf('No response at position: %d.', $pointer));
        }

        $this->pointer = $pointer;

        return $this;
    }

    /**
     * Move to first pointer.
     *
     * @return Test
     */
    public function first(): Test
    {
        return $this->moveTo(0);
    }

    /**
     * Move to last pointer.
     *
     * @return Test
     */
    public function last(): Test
    {
        return $this->moveTo(count($this->history) - 1);
    }

    /**
     * Move to previous pointer.
     *
     * @return Test
     */
    public function prev(): Test
    {
        if (--$this->pointer < 0) {
            throw new \LogicException('Cannot move backward!');
        }

        return $this;
    }

    /**
     * Move to next pointer.
     *
     * @return Test
     */
    public function next(): Test
    {
        if (++$this->pointer > count($this->history) - 1) {
            throw new \LogicException('Cannot move forward!');
        }

        return $this;
    }

    /**
     * Returns DomNodeList match xpath.
     *
     * @param string $selector
     * @param bool   $cssSelector
     *
     * @return DomNodeList|null
     */
    public function findElements(string $selector, bool $cssSelector = true): ?\DomNodeList
    {
        if (!$domXpath = $this->response('xpath')) {
            throw new \LogicException('Response is empty!');
        }

        $expression = $selector;

        if ($cssSelector) {
            if (!$this->converter) {
                $this->converter = new CssSelectorConverter();
            }

            $expression = $this->converter->toXPath($selector, '*//');
        }

        return $domXpath->query($expression) ?: null;
    }

    /**
     * Ensure xpath returns exactly one DomNode or null.
     *
     * @param string $selector
     * @param bool   $cssSelector
     *
     * @return DomNode|null
     */
    public function findFirstElement(string $selector, bool $cssSelector = true): ?\DomNode
    {
        $nodes = $this->findElements($selector, $cssSelector);

        return $nodes ? $nodes->item(0) : null;
    }

    /**
     * Make request.
     *
     * @param string     $method
     * @param string     $path
     * @param array|null $data
     * @param array|null $options
     *
     * @return Test
     */
    public function request(string $method, string $path, array $data = null, array $options = null): Test
    {
        $use = ($options ?? array()) + array(
            'ajax' => false,
            'body' => null,
            'cli' => false,
            'server' => null,
            'quiet' => true,
            'html5_options' => null,
        );
        $request = $method.' '.$path;

        if ($use['ajax']) {
            $request .= ' ajax';
        } elseif ($use['cli']) {
            $request .= ' cli';
        }

        $this->fw->set('QUIET', $use['quiet']);
        $this->fw->mock($request, $data, $use['server'], $use['body']);

        $response = $this->fw->allGet('PATH,CODE,STATUS,RESPONSE,OUTPUT,ALIAS,PARAMETERS,document,xpath', true, array(
            'RESPONSE' => 'headers',
            'OUTPUT' => 'content',
            'ALIAS' => 'route',
        ));

        if ($response['content']) {
            $response['document'] = new HTML5DOMDocument();
            $response['document']->loadHtml($response['content']);
            $response['xpath'] = new \DomXPath($response['document']);
        }

        $this->history[++$this->pointer] = $response;

        return $this;
    }

    /**
     * GET request.
     *
     * @param string      $path
     * @param string|null $data
     * @param string|null $options
     *
     * @return Test
     */
    public function visit(string $path, array $data = null, array $options = null): Test
    {
        return $this->request('GET', $path, $data, $options);
    }

    /**
     * POST request.
     *
     * @param string     $path
     * @param array|null $data
     * @param array|null $options
     *
     * @return Test
     */
    public function post(string $path, array $data = null, array $options = null): Test
    {
        return $this->request('POST', $path, $data, $options);
    }

    /**
     * Select form that has button with specified label.
     *
     * @param string $buttonLabel
     * @param bool   $button
     *
     * @return Test
     */
    public function form(string $buttonLabel, bool $button = true): Test
    {
        $xpath = $button ? "*//form[button[text()='$buttonLabel']]" : "*//form[input[@value='$buttonLabel']]";
        $form = $this->findFirstElement($xpath, false);

        if (!$form) {
            throw new \LogicException(sprintf('Form with button labeled "%s" is not found.', $buttonLabel));
        }

        $this->history[$this->pointer]['form'] = $form;

        return $this;
    }

    /**
     * Perform submit based on previously selected form.
     *
     * @param array|null $data
     *
     * @return Test
     */
    public function submit(array $data = null): Test
    {
        if (!$form = $this->response('form')) {
            throw new \LogicException('No form selected.');
        }

        $path = $this->fw->get('PATH');
        $method = $this->fw->get('VERB');
        $options = $this->fw->allGet('AJAX,CLI,SERVER,BODY,QUIET', true);

        if (($node = $form->attributes->getNamedItem('action')) && $node->value) {
            $path = $node->value;
        }

        if (($node = $form->attributes->getNamedItem('method')) && $node->value) {
            $method = $node->value;
        }

        return $this->request($method, $path, $data, $options);
    }

    /**
     * Click link with specified label.
     *
     * @param string $label
     *
     * @return Test
     */
    public function click(string $label): Test
    {
        $element = $this->findFirstElement("*//a[text()='$label']", false);

        if (!($element && ($href = $element->attributes->getNamedItem('href')) && $href->value)) {
            throw new \LogicException(sprintf('Link labeled "%s" not exists.', $label));
        }

        return $this->visit($href->value);
    }

    /**
     * Call callable and return test instance.
     *
     * @param callable $callback
     *
     * @return Test
     */
    public function check(callable $callback): Test
    {
        $callback($this);

        return $this;
    }

    /**
     * Expect response code is equals.
     *
     * @param int         $code
     * @param string|null $message
     *
     * @return Test
     */
    public function isCode(int $code, string $message = null): Test
    {
        return $this->expectEquals($code, $this->response('code'), $message ?? sprintf('Response code is not equals to "%d".', $code));
    }

    /**
     * Expect response code is not equals.
     *
     * @param int         $code
     * @param string|null $message
     *
     * @return Test
     */
    public function isNotCode(int $code, string $message = null): Test
    {
        return $this->expectNotEquals($code, $this->response('code'), $message ?? sprintf('Response code is equals to "%d".', $code));
    }

    /**
     * Expect request is successful.
     *
     * @return Test
     */
    public function isSuccessful(): Test
    {
        return $this->isCode(200, 'Request is not successful.');
    }

    /**
     * Expect request is not successful.
     *
     * @return Test
     */
    public function isNotSuccessful(): Test
    {
        return $this->isNotCode(200, 'Request is successful.');
    }

    /**
     * Expect request is found.
     *
     * @return Test
     */
    public function isFound(): Test
    {
        return $this->isNotCode(404, 'Request is not found.');
    }

    /**
     * Expect request is not found.
     *
     * @return Test
     */
    public function isNotFound(): Test
    {
        return $this->isCode(404, 'Request is found.');
    }

    /**
     * Expect request is forbidden.
     *
     * @return Test
     */
    public function isForbidden(): Test
    {
        return $this->isCode(403, 'Request is forbidden.');
    }

    /**
     * Expect request is not forbidden.
     *
     * @return Test
     */
    public function isNotForbidden(): Test
    {
        return $this->isNotCode(403, 'Request is not forbidden.');
    }

    /**
     * Expect server response is error.
     *
     * @return Test
     */
    public function isServerError(): Test
    {
        return $this->isCode(500, 'Server response is not error.');
    }

    /**
     * Expect server response is not error.
     *
     * @return Test
     */
    public function isNotServerError(): Test
    {
        return $this->isNotCode(500, 'Server response is error.');
    }

    /**
     * Expect current request at specified route.
     *
     * @param string $route
     *
     * @return Test
     */
    public function atRoute(string $route): Test
    {
        return $this->expectEquals($route, $this->response('route'), sprintf('Route is not equals to "%s".', $route));
    }

    /**
     * Expect current request not at specified route.
     *
     * @param string $route
     *
     * @return Test
     */
    public function notAtRoute(string $route): Test
    {
        return $this->expectNotEquals($route, $this->response('route'), sprintf('Route is equals to "%s".', $route));
    }

    /**
     * Expect current request at specified path.
     *
     * @param string $path
     *
     * @return Test
     */
    public function atPath(string $path): Test
    {
        return $this->expectEquals($path, $this->response('path'), sprintf('Path is not equals to "%s".', $path));
    }

    /**
     * Expect current request not at specified path.
     *
     * @param string $path
     *
     * @return Test
     */
    public function notAtPath(string $path): Test
    {
        return $this->expectNotEquals($path, $this->response('path'), sprintf('Path is equals to "%s".', $path));
    }

    /**
     * Expect current request parameters equals.
     *
     * @param array $parameters
     *
     * @return Test
     */
    public function parametersEquals(array $parameters): Test
    {
        return $this->expectEquals($parameters, $this->response('parameters'), 'Parameters is not equals expected.');
    }

    /**
     * Expect current request parameters not equals.
     *
     * @param array $parameters
     *
     * @return Test
     */
    public function parametersNotEquals(array $parameters): Test
    {
        return $this->expectNotEquals($parameters, $this->response('parameters'), 'Parameters equals expected.');
    }

    /**
     * Alias to parametersEquals.
     *
     * @param array $parameters
     *
     * @return Test
     */
    public function withParameters(array $parameters): Test
    {
        return $this->parametersEquals($parameters);
    }

    /**
     * Expect response content is empty.
     *
     * @return Test
     */
    public function isEmpty(): Test
    {
        return $this->expectEmpty($this->response(), 'Response is not empty.');
    }

    /**
     * Expect response content is not empty.
     *
     * @return Test
     */
    public function isNotEmpty(): Test
    {
        return $this->expectNotEmpty($this->response(), 'Response is empty.');
    }

    /**
     * Expect response content is equals.
     *
     * @param string $text
     *
     * @return Test
     */
    public function isEquals(string $text): Test
    {
        return $this->expectEquals($text, $this->response(), 'Response is not equals to expected text.');
    }

    /**
     * Expect response content is not equals.
     *
     * @param string $text
     *
     * @return Test
     */
    public function isNotEquals(string $text): Test
    {
        return $this->expectNotEquals($text, $this->response(), 'Response is equals to expected text.');
    }

    /**
     * Expect response content contains text.
     *
     * @param string $text
     *
     * @return Test
     */
    public function contains(string $text): Test
    {
        return $this->expectContains($text, $this->response(), 'Response not contains expected text.');
    }

    /**
     * Expect response content not contains text.
     *
     * @param string $text
     *
     * @return Test
     */
    public function notContains(string $text): Test
    {
        return $this->expectNotContains($text, $this->response(), 'Response contains expected text.');
    }

    /**
     * Expect response content match pattern.
     *
     * @param string $pattern
     *
     * @return Test
     */
    public function regexp(string $pattern): Test
    {
        return $this->expectRegexp($pattern, $this->response(), 'Response not match expected pattern.');
    }

    /**
     * Expect response content not match pattern.
     *
     * @param string $pattern
     *
     * @return Test
     */
    public function notRegexp(string $pattern): Test
    {
        return $this->expectNotRegexp($pattern, $this->response(), 'Response match expected pattern.');
    }

    /**
     * Expect response content has element match css selector.
     *
     * @param string $selector
     * @param bool   $cssSelector
     *
     * @return Test
     */
    public function elementExists(string $selector, bool $cssSelector = true): Test
    {
        $element = $this->findFirstElement($selector, $cssSelector);

        return $this->expectNotEmpty($element, sprintf('No element match selector: "%s".', $selector));
    }

    /**
     * Expect response content has not element match css selector.
     *
     * @param string $selector
     * @param bool   $cssSelector
     *
     * @return Test
     */
    public function elementNotExists(string $selector, bool $cssSelector = true): Test
    {
        $element = $this->findFirstElement($selector, $cssSelector);

        return $this->expectEmpty($element, sprintf('An element match selector: "%s".', $selector));
    }

    /**
     * Expect response content element equals to text.
     *
     * @param string $selector
     * @param string $text
     * @param bool   $cssSelector
     *
     * @return Test
     */
    public function elementEquals(string $selector, string $text, bool $cssSelector = true): Test
    {
        $element = $this->findFirstElement($selector, $cssSelector);
        $content = $element->textContent ?? null;

        return $this->expectEquals($text, $content, sprintf('Element "%s" is not equals to expected text.', $selector));
    }

    /**
     * Expect response content element not equals to text.
     *
     * @param string $selector
     * @param string $text
     * @param bool   $cssSelector
     *
     * @return Test
     */
    public function elementNotEquals(string $selector, string $text, bool $cssSelector = true): Test
    {
        $element = $this->findFirstElement($selector, $cssSelector);
        $content = $element->textContent ?? null;

        return $this->expectNotEquals($text, $content, sprintf('Element "%s" is equals to expected text.', $selector));
    }

    /**
     * Expect response content element contains text.
     *
     * @param string $selector
     * @param string $text
     * @param bool   $cssSelector
     *
     * @return Test
     */
    public function elementContains(string $selector, string $text, bool $cssSelector = true): Test
    {
        $element = $this->findFirstElement($selector, $cssSelector);
        $content = $element->textContent ?? null;

        return $this->expectContains($text, $content, sprintf('Element "%s" not contains expected text.', $selector));
    }

    /**
     * Expect response content element not contains text.
     *
     * @param string $selector
     * @param string $text
     * @param bool   $cssSelector
     *
     * @return Test
     */
    public function elementNotContains(string $selector, string $text, bool $cssSelector = true): Test
    {
        $element = $this->findFirstElement($selector, $cssSelector);
        $content = $element->textContent ?? null;

        return $this->expectNotContains($text, $content, sprintf('Element "%s" contains expected text.', $selector));
    }

    /**
     * Expect response content element match pattern.
     *
     * @param string $selector
     * @param string $pattern
     * @param bool   $cssSelector
     *
     * @return Test
     */
    public function elementRegexp(string $selector, string $pattern, bool $cssSelector = true): Test
    {
        $element = $this->findFirstElement($selector, $cssSelector);
        $content = $element->textContent ?? null;

        return $this->expectRegexp($pattern, $content, sprintf('Element "%s" not match expected pattern.', $selector));
    }

    /**
     * Expect response content element not match pattern.
     *
     * @param string $selector
     * @param string $pattern
     * @param bool   $cssSelector
     *
     * @return Test
     */
    public function elementNotRegexp(string $selector, string $pattern, bool $cssSelector = true): Test
    {
        $element = $this->findFirstElement($selector, $cssSelector);
        $content = $element->textContent ?? null;

        return $this->expectNotRegexp($pattern, $content, sprintf('Element "%s" match expected pattern.', $selector));
    }

    /**
     * Expect link with specific label exists.
     *
     * @param string $label
     *
     * @return Test
     */
    public function linkExists(string $label): Test
    {
        $element = $this->findFirstElement("*//a[text()='$label']", false);

        return $this->expectNotEmpty($element, sprintf('Link labeled "%s" not exists.', $label));
    }

    /**
     * Expect link with specific label not exists.
     *
     * @param string $label
     *
     * @return Test
     */
    public function linkNotExists(string $label): Test
    {
        $element = $this->findFirstElement("*//a[text()='$label']", false);

        return $this->expectEmpty($element, sprintf('Link labeled "%s" exists.', $label));
    }

    /**
     * Expect hive value is true.
     *
     * @param string $key
     *
     * @return Test
     */
    public function hiveTrue(string $key): Test
    {
        return $this->expectTrue($this->fw->get($key), sprintf('Hive "%s" is not true.', $key));
    }

    /**
     * Expect hive value is not true.
     *
     * @param string $key
     *
     * @return Test
     */
    public function hiveNotTrue(string $key): Test
    {
        return $this->expectNotTrue($this->fw->get($key), sprintf('Hive "%s" is true.', $key));
    }

    /**
     * Expect hive value is false.
     *
     * @param string $key
     *
     * @return Test
     */
    public function hiveFalse(string $key): Test
    {
        return $this->expectFalse($this->fw->get($key), sprintf('Hive "%s" is not false.', $key));
    }

    /**
     * Expect hive value is not false.
     *
     * @param string $key
     *
     * @return Test
     */
    public function hiveNotFalse(string $key): Test
    {
        return $this->expectNotFalse($this->fw->get($key), sprintf('Hive "%s" is false.', $key));
    }

    /**
     * Expect hive value is null.
     *
     * @param string $key
     *
     * @return Test
     */
    public function hiveNull(string $key): Test
    {
        return $this->expectNull($this->fw->get($key), sprintf('Hive "%s" is not null.', $key));
    }

    /**
     * Expect hive value is not null.
     *
     * @param string $key
     *
     * @return Test
     */
    public function hiveNotNull(string $key): Test
    {
        return $this->expectNotNull($this->fw->get($key), sprintf('Hive "%s" is null.', $key));
    }

    /**
     * Expect hive value empty.
     *
     * @param string $key
     *
     * @return Test
     */
    public function hiveEmpty(string $key): Test
    {
        return $this->expectEmpty($this->fw->get($key), sprintf('Hive "%s" not empty.', $key));
    }

    /**
     * Expect hive value is not empty.
     *
     * @param string $key
     *
     * @return Test
     */
    public function hiveNotEmpty(string $key): Test
    {
        return $this->expectNotEmpty($this->fw->get($key), sprintf('Hive "%s" empty.', $key));
    }

    /**
     * Expect hive value is equal.
     *
     * @param string $key
     * @param mixed  $expected
     *
     * @return Test
     */
    public function hiveEquals(string $key, $expected): Test
    {
        return $this->expectEquals($expected, $this->fw->get($key), sprintf('Hive "%s" is not equals with expected value.', $key));
    }

    /**
     * Expect hive value is not equal.
     *
     * @param string $key
     * @param mixed  $expected
     *
     * @return Test
     */
    public function hiveNotEquals(string $key, $expected): Test
    {
        return $this->expectNotEquals($expected, $this->fw->get($key), sprintf('Hive "%s" is equals with expected value.', $key));
    }

    /**
     * Expect hive value contains.
     *
     * @param string $key
     * @param mixed  $expected
     *
     * @return Test
     */
    public function hiveContains(string $key, $expected): Test
    {
        return $this->expectContains($expected, $this->fw->get($key), sprintf('Hive "%s" not contains expected value.', $key));
    }

    /**
     * Expect hive value not contains.
     *
     * @param string $key
     * @param mixed  $expected
     *
     * @return Test
     */
    public function hiveNotContains(string $key, $expected): Test
    {
        return $this->expectNotContains($expected, $this->fw->get($key), sprintf('Hive "%s" contains expected value.', $key));
    }

    /**
     * Expect hive value match pattern.
     *
     * @param string $key
     * @param string $pattern
     *
     * @return Test
     */
    public function hiveRegexp(string $key, string $pattern): Test
    {
        return $this->expectRegexp($pattern, $this->fw->get($key), sprintf('Hive "%s" not match pattern.', $key));
    }

    /**
     * Expect hive value not match pattern.
     *
     * @param string $key
     * @param string $pattern
     *
     * @return Test
     */
    public function hiveNotRegexp(string $key, string $pattern): Test
    {
        return $this->expectNotRegexp($pattern, $this->fw->get($key), sprintf('Hive "%s" match pattern.', $key));
    }

    /**
     * Expect assertion is successfull.
     *
     * @param bool   $success
     * @param string $assertion
     * @param mixed  ...$arguments
     *
     * @return Test
     */
    public function expect(bool $success, string $assertion, ...$arguments): Test
    {
        $this->success = $success;

        // @codeCoverageIgnoreStart
        if ($this->test) {
            $assert = 'assert'.$assertion;

            $this->test->$assert(...$arguments);
        }
        // @codeCoverageIgnoreEnd

        return $this;
    }

    /**
     * Expect value is true.
     *
     * @param mixed       $expected
     * @param string|null $message
     *
     * @return Test
     */
    public function expectTrue($expected, string $message = null): Test
    {
        return $this->expect(true === $expected, 'true', $expected, $message);
    }

    /**
     * Expect value is not true.
     *
     * @param mixed       $expected
     * @param string|null $message
     *
     * @return Test
     */
    public function expectNotTrue($expected, string $message = null): Test
    {
        return $this->expect(true !== $expected, 'nottrue', $expected, $message);
    }

    /**
     * Expect value is false.
     *
     * @param mixed       $expected
     * @param string|null $message
     *
     * @return Test
     */
    public function expectFalse($expected, string $message = null): Test
    {
        return $this->expect(false === $expected, 'false', $expected, $message);
    }

    /**
     * Expect value is not false.
     *
     * @param mixed       $expected
     * @param string|null $message
     *
     * @return Test
     */
    public function expectNotFalse($expected, string $message = null): Test
    {
        return $this->expect(false !== $expected, 'notfalse', $expected, $message);
    }

    /**
     * Expect value is null.
     *
     * @param mixed       $expected
     * @param string|null $message
     *
     * @return Test
     */
    public function expectNull($expected, string $message = null): Test
    {
        return $this->expect(null === $expected, 'null', $expected, $message);
    }

    /**
     * Expect value is not null.
     *
     * @param mixed       $expected
     * @param string|null $message
     *
     * @return Test
     */
    public function expectNotNull($expected, string $message = null): Test
    {
        return $this->expect(null !== $expected, 'notnull', $expected, $message);
    }

    /**
     * Expect value empty.
     *
     * @param mixed       $expected
     * @param string|null $message
     *
     * @return Test
     */
    public function expectEmpty($expected, string $message = null): Test
    {
        return $this->expect(empty($expected), 'empty', $expected, $message);
    }

    /**
     * Expect value not empty.
     *
     * @param mixed       $expected
     * @param string|null $message
     *
     * @return Test
     */
    public function expectNotEmpty($expected, string $message = null): Test
    {
        return $this->expect(!empty($expected), 'notempty', $expected, $message);
    }

    /**
     * Expect value is equals.
     *
     * @param mixed       $expected
     * @param mixed       $actual
     * @param string|null $message
     *
     * @return Test
     */
    public function expectEquals($expected, $actual, string $message = null): Test
    {
        return $this->expect($expected == $actual, 'equals', $expected, $actual, $message);
    }

    /**
     * Expect value is not equals.
     *
     * @param mixed       $expected
     * @param mixed       $actual
     * @param string|null $message
     *
     * @return Test
     */
    public function expectNotEquals($expected, $actual, string $message = null): Test
    {
        return $this->expect($expected != $actual, 'notequals', $expected, $actual, $message);
    }

    /**
     * Expect value is identical.
     *
     * @param mixed       $expected
     * @param mixed       $actual
     * @param string|null $message
     *
     * @return Test
     */
    public function expectSame($expected, $actual, string $message = null): Test
    {
        return $this->expect($expected === $actual, 'same', $expected, $actual, $message);
    }

    /**
     * Expect value is not identical.
     *
     * @param mixed       $expected
     * @param mixed       $actual
     * @param string|null $message
     *
     * @return Test
     */
    public function expectNotSame($expected, $actual, string $message = null): Test
    {
        return $this->expect($expected !== $actual, 'notsame', $expected, $actual, $message);
    }

    /**
     * Expect value contains.
     *
     * @param mixed       $expected
     * @param mixed       $actual
     * @param string|null $message
     *
     * @return Test
     */
    public function expectContains($expected, $actual, string $message = null): Test
    {
        if (is_string($actual)) {
            $contains = $actual && false !== strpos($actual, $expected);
        } else {
            $contains = $actual && in_array($expected, (array) $actual);
        }

        return $this->expect($contains, 'contains', $expected, $actual, $message);
    }

    /**
     * Expect value not contains.
     *
     * @param mixed       $expected
     * @param mixed       $actual
     * @param string|null $message
     *
     * @return Test
     */
    public function expectNotContains($expected, $actual, string $message = null): Test
    {
        if (is_string($actual)) {
            $notcontains = !$actual || false === strpos($actual, $expected);
        } else {
            $notcontains = !$actual || !in_array($expected, (array) $actual);
        }

        return $this->expect($notcontains, 'notcontains', $expected, $actual, $message);
    }

    /**
     * Expect value match pattern.
     *
     * @param mixed       $expected
     * @param mixed       $actual
     * @param string|null $message
     *
     * @return Test
     */
    public function expectRegexp($expected, $actual, string $message = null): Test
    {
        $match = $actual && preg_match($expected, (string) $actual);

        return $this->expect($match, 'regexp', $expected, $actual, $message);
    }

    /**
     * Expect value not match pattern.
     *
     * @param mixed       $expected
     * @param mixed       $actual
     * @param string|null $message
     *
     * @return Test
     */
    public function expectNotRegexp($expected, $actual, string $message = null): Test
    {
        $notmatch = !$actual || !preg_match($expected, (string) $actual);

        return $this->expect($notmatch, 'notregexp', $expected, $actual, $message);
    }
}
