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

use PHPUnit\Framework\TestCase;

/**
 * Response tester helper, integrated with PHPUnit TestCase.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ResponseTester
{
    /**
     * @var Fw
     */
    protected $fw;

    /**
     * @var TestCase
     */
    protected $testCase;

    /**
     * @var DomDocument
     */
    protected $document;

    /**
     * @var DomXpath
     */
    protected $xpath;

    /**
     * @var DomNode
     */
    protected $form;

    /**
     * @var bool
     */
    protected $latest = false;

    /**
     * Class constructor.
     *
     * @param Fw            $fw
     * @param TestCase|null $testCase
     */
    public function __construct(Fw $fw, TestCase $testCase = null)
    {
        $this->fw = $fw;
        $this->testCase = $testCase;
    }

    /**
     * Returns latest test result.
     *
     * @return bool
     */
    public function latest(): bool
    {
        return $this->latest;
    }

    /**
     * Reset this tester environment.
     *
     * @return ResponseTester
     */
    public function reset(): ResponseTester
    {
        $this->document = null;
        $this->xpath = null;
        $this->form = null;

        return $this;
    }

    /**
     * Make request, it is a proxy to Fw::mock.
     *
     * @param string      $method
     * @param string      $path
     * @param array|null  $args
     * @param bool        $ajax
     * @param bool        $cli
     * @param array|null  $server
     * @param string|null $body
     * @param bool        $quiet
     *
     * @return ResponseTester
     */
    public function request(string $method, string $path, array $args = null, bool $ajax = false, bool $cli = false, array $server = null, string $body = null, bool $quiet = true): ResponseTester
    {
        $request = $method.' '.$path;

        if ($ajax) {
            $request .= ' ajax';
        } elseif ($cli) {
            $request .= ' cli';
        }

        $this->fw['QUIET'] = $quiet;
        $this->fw->mock($request, $args, $server, $body);

        return $this;
    }

    /**
     * Alias to request.
     *
     * @param string $path
     * @param string $method
     * @param mixed  ...$args
     *
     * @return ResponseTester
     */
    public function visit(string $path, string $method = 'GET', ...$args): ResponseTester
    {
        return $this->request($method, $path, ...$args);
    }

    /**
     * Post request.
     *
     * @param string     $path
     * @param array|null $data
     * @param mixed      ...$args
     *
     * @return ResponseTester
     */
    public function post(string $path, array $data = null, ...$args): ResponseTester
    {
        return $this->visit($path, 'POST', $data, ...$args);
    }

    /**
     * Select form that has label.
     *
     * @param string $btnLabel
     *
     * @return ResponseTester
     */
    public function form(string $btnLabel): ResponseTester
    {
        $this->form = $this->findForm($btnLabel);

        if (!$this->form) {
            throw new \LogicException(sprintf('Form with button "%s" is not found.', $btnLabel));
        }

        return $this;
    }

    /**
     * Perform submit based on previously selected form.
     *
     * @param array|null $data
     *
     * @return ResponseTester
     */
    public function submit(array $data = null): ResponseTester
    {
        if (!$this->form) {
            throw new \LogicException('No form selected.');
        }

        $path = $this->fw['PATH'];
        $method = $this->fw['VERB'];

        if (($node = $this->form->attributes->getNamedItem('action')) && $node->value) {
            $path = $node->value;
        }

        if (($node = $this->form->attributes->getNamedItem('method')) && $node->value) {
            $method = $node->value;
        }

        return $this->visit($path, $method, $data, $this->fw['AJAX'], $this->fw['CLI'], $this->fw['SERVER'], $this->fw['BODY'], $this->fw['QUIET']);
    }

    /**
     * Expect response code is equals.
     *
     * @param int         $code
     * @param string|null $message
     *
     * @return ResponseTester
     */
    public function isCode(int $code, string $message = null): ResponseTester
    {
        return $this->expect($code === $this->fw['CODE'], 'equals', $code, $this->fw['CODE'], $message ?? sprintf('Response code is not equals to "%d".', $code));
    }

    /**
     * Expect response code is not equals.
     *
     * @param int         $code
     * @param string|null $message
     *
     * @return ResponseTester
     */
    public function isNotCode(int $code, string $message = null): ResponseTester
    {
        return $this->expect($code !== $this->fw['CODE'], 'notequals', $code, $this->fw['CODE'], $message ?? sprintf('Response code is equals to "%d".', $code));
    }

    /**
     * Expect request is successful.
     *
     * @return ResponseTester
     */
    public function isSuccess(): ResponseTester
    {
        return $this->isCode(200, 'Request is not successful.');
    }

    /**
     * Expect request is not successful.
     *
     * @return ResponseTester
     */
    public function isNotSuccess(): ResponseTester
    {
        return $this->isNotCode(200, 'Request is successful.');
    }

    /**
     * Expect request is found.
     *
     * @return ResponseTester
     */
    public function isFound(): ResponseTester
    {
        return $this->isNotCode(404, 'Request is not found.');
    }

    /**
     * Expect request is not found.
     *
     * @return ResponseTester
     */
    public function isNotFound(): ResponseTester
    {
        return $this->isCode(404, 'Request is found.');
    }

    /**
     * Expect request is forbidden.
     *
     * @return ResponseTester
     */
    public function isForbidden(): ResponseTester
    {
        return $this->isCode(403, 'Request is forbidden.');
    }

    /**
     * Expect request is not forbidden.
     *
     * @return ResponseTester
     */
    public function isNotForbidden(): ResponseTester
    {
        return $this->isNotCode(403, 'Request is not forbidden.');
    }

    /**
     * Expect server response is error.
     *
     * @return ResponseTester
     */
    public function isServerError(): ResponseTester
    {
        return $this->isCode(500, 'Server response is not error.');
    }

    /**
     * Expect server response is not error.
     *
     * @return ResponseTester
     */
    public function isNotServerError(): ResponseTester
    {
        return $this->isNotCode(500, 'Server response is error.');
    }

    /**
     * Expect response content is empty.
     *
     * @return ResponseTester
     */
    public function isEmpty(): ResponseTester
    {
        return $this->expect(empty($this->fw['OUTPUT']), 'empty', $this->fw['OUTPUT'], 'Response is not empty.');
    }

    /**
     * Expect response content is not empty.
     *
     * @return ResponseTester
     */
    public function isNotEmpty(): ResponseTester
    {
        return $this->expect(!empty($this->fw['OUTPUT']), 'notempty', $this->fw['OUTPUT'], 'Response is empty.');
    }

    /**
     * Expect response content is equals.
     *
     * @param string $text
     *
     * @return ResponseTester
     */
    public function isEquals(string $text): ResponseTester
    {
        $output = $this->fw['OUTPUT'];
        $equals = $text === $output;

        return $this->expect($equals, 'equals', $text, $output, 'Response is not equals to expected text.');
    }

    /**
     * Expect response content is not equals.
     *
     * @param string $text
     *
     * @return ResponseTester
     */
    public function isNotEquals(string $text): ResponseTester
    {
        $output = $this->fw['OUTPUT'];
        $notequals = $text !== $output;

        return $this->expect($notequals, 'notequals', $text, $output, 'Response is equals to expected text.');
    }

    /**
     * Expect response content contains text.
     *
     * @param string $text
     *
     * @return ResponseTester
     */
    public function contains(string $text): ResponseTester
    {
        $output = $this->fw['OUTPUT'];
        $contains = $output && (false !== strpos($output, $text));

        return $this->expect($contains, 'contains', $text, $output, 'Response not contains expected text.');
    }

    /**
     * Expect response content not contains text.
     *
     * @param string $text
     *
     * @return ResponseTester
     */
    public function notContains(string $text): ResponseTester
    {
        $output = $this->fw['OUTPUT'];
        $notcontains = !$output || (false === strpos($output, $text));

        return $this->expect($notcontains, 'notcontains', $text, $output, 'Response contains expected text.');
    }

    /**
     * Expect response content match pattern.
     *
     * @param string $pattern
     *
     * @return ResponseTester
     */
    public function regexp(string $pattern): ResponseTester
    {
        $output = $this->fw['OUTPUT'];
        $match = $output && preg_match($pattern, $output);

        return $this->expect($match, 'regexp', $pattern, $output, 'Response not match expected pattern.');
    }

    /**
     * Expect response content not match pattern.
     *
     * @param string $pattern
     *
     * @return ResponseTester
     */
    public function notRegexp(string $pattern): ResponseTester
    {
        $output = $this->fw['OUTPUT'];
        $notmatch = !$output || !preg_match($pattern, $output);

        return $this->expect($notmatch, 'notregexp', $pattern, $output, 'Response match expected pattern.');
    }

    /**
     * Expect response content has element match xpath.
     *
     * @param string $xpath
     *
     * @return ResponseTester
     */
    public function elementExists(string $xpath): ResponseTester
    {
        $element = $this->findXpathFirst($xpath);
        $true = null !== $element;

        return $this->expect($true, 'true', $true, sprintf('No element match xpath: "%s".', $xpath));
    }

    /**
     * Expect response content has not element match xpath.
     *
     * @param string $xpath
     *
     * @return ResponseTester
     */
    public function elementNotExists(string $xpath): ResponseTester
    {
        $element = $this->findXpathFirst($xpath);
        $true = null === $element;

        return $this->expect($true, 'true', $true, sprintf('An element match xpath: "%s".', $xpath));
    }

    /**
     * Expect response content element equals to text.
     *
     * @param string $xpath
     * @param string $text
     *
     * @return ResponseTester
     */
    public function elementEquals(string $xpath, string $text): ResponseTester
    {
        $element = $this->findXpathFirst($xpath);
        $content = $element->textContent ?? null;
        $equals = $content === $text;

        return $this->expect($equals, 'equals', $text, $content, sprintf('Element "%s" is not equals to expected text.', $xpath));
    }

    /**
     * Expect response content element not equals to text.
     *
     * @param string $xpath
     * @param string $text
     *
     * @return ResponseTester
     */
    public function elementNotEquals(string $xpath, string $text): ResponseTester
    {
        $element = $this->findXpathFirst($xpath);
        $content = $element->textContent ?? null;
        $notequals = $content !== $text;

        return $this->expect($notequals, 'notequals', $text, $content, sprintf('Element "%s" is equals to expected text.', $xpath));
    }

    /**
     * Expect response content element contains text.
     *
     * @param string $xpath
     * @param string $text
     *
     * @return ResponseTester
     */
    public function elementContains(string $xpath, string $text): ResponseTester
    {
        $element = $this->findXpathFirst($xpath);
        $content = $element->textContent ?? null;
        $contains = $content && (false !== strpos($content, $text));

        return $this->expect($contains, 'contains', $text, $content, sprintf('Element "%s" not contains expected text.', $xpath));
    }

    /**
     * Expect response content element not contains text.
     *
     * @param string $xpath
     * @param string $text
     *
     * @return ResponseTester
     */
    public function elementNotContains(string $xpath, string $text): ResponseTester
    {
        $element = $this->findXpathFirst($xpath);
        $content = $element->textContent ?? null;
        $notcontains = !$content || (false === strpos($content, $text));

        return $this->expect($notcontains, 'notcontains', $text, $content, sprintf('Element "%s" contains expected text.', $xpath));
    }

    /**
     * Expect response content element match pattern.
     *
     * @param string $xpath
     * @param string $pattern
     *
     * @return ResponseTester
     */
    public function elementRegexp(string $xpath, string $pattern): ResponseTester
    {
        $element = $this->findXpathFirst($xpath);
        $content = $element->textContent ?? null;
        $match = $content && preg_match($pattern, $content);

        return $this->expect($match, 'regexp', $pattern, $content, sprintf('Element "%s" not match expected pattern.', $xpath));
    }

    /**
     * Expect response content element not match pattern.
     *
     * @param string $xpath
     * @param string $pattern
     *
     * @return ResponseTester
     */
    public function elementNotRegexp(string $xpath, string $pattern): ResponseTester
    {
        $element = $this->findXpathFirst($xpath);
        $content = $element->textContent ?? null;
        $notmatch = !$content || !preg_match($pattern, $content);

        return $this->expect($notmatch, 'notregexp', $pattern, $content, sprintf('Element "%s" not match expected pattern.', $xpath));
    }

    /**
     * Expect assertion is successfull.
     *
     * @param bool   $success
     * @param string $assertion
     * @param mixed  ...$args
     *
     * @return ResponseTester
     */
    public function expect(bool $success, string $assertion, ...$args): ResponseTester
    {
        $this->latest = $success;

        // @codeCoverageIgnoreStart
        if ($this->testCase) {
            $assert = 'assert'.$assertion;

            $this->testCase->$assert(...$args);
        }
        // @codeCoverageIgnoreEnd

        return $this;
    }

    /**
     * Resolve DomDocument.
     */
    protected function resolveDocument(): void
    {
        if (!$this->document && $this->fw['OUTPUT'] && $document = \DomDocument::loadHtml($this->fw['OUTPUT'])) {
            $this->document = $document;
        }
    }

    /**
     * Resolve DomXPath.
     */
    protected function resolveXpath(): void
    {
        $this->resolveDocument();

        if (!$this->document) {
            throw new \LogicException('No response to parse.');
        }

        if (!$this->xpath) {
            $this->xpath = new \DomXpath($this->document);
        }
    }

    /**
     * Returns DomNodeList match xpath.
     *
     * @param string $xpath
     *
     * @return DomNodeList|null
     */
    protected function findXpath(string $xpath): ?\DomNodeList
    {
        $this->resolveXpath();

        return $this->xpath->query($xpath) ?: null;
    }

    /**
     * Ensure xpath returns exactly a DomNode or null.
     *
     * @param string $path
     *
     * @return DomNode|null
     */
    protected function findXpathFirst(string $path): ?\DomNode
    {
        $nodes = $this->findXpath($path);

        return $nodes ? $nodes->item(0) : null;
    }

    /**
     * Find form by button label.
     *
     * @param string $buttonLabel
     *
     * @return DomNode|null
     */
    protected function findForm(string $buttonLabel): ?\DomNode
    {
        return $this->findXpathFirst("*//form[button='$buttonLabel']");
    }
}
