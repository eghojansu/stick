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
 * Framework main class test helper, integrated with PHPUnit TestCase.
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
     * @return Test
     */
    public function reset(): Test
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
     * @return Test
     */
    public function request(string $method, string $path, array $args = null, bool $ajax = false, bool $cli = false, array $server = null, string $body = null, bool $quiet = true): Test
    {
        $request = $method.' '.$path;

        if ($ajax) {
            $request .= ' ajax';
        } elseif ($cli) {
            $request .= ' cli';
        }

        $this->fw->set('QUIET', $quiet);
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
     * @return Test
     */
    public function visit(string $path, string $method = 'GET', ...$args): Test
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
     * @return Test
     */
    public function post(string $path, array $data = null, ...$args): Test
    {
        return $this->visit($path, 'POST', $data, ...$args);
    }

    /**
     * Select form that has label.
     *
     * @param string $btnLabel
     *
     * @return Test
     */
    public function form(string $btnLabel): Test
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
     * @return Test
     */
    public function submit(array $data = null): Test
    {
        if (!$this->form) {
            throw new \LogicException('No form selected.');
        }

        $arguments = $this->fw->allGet('PATH,VERB,DATA,AJAX,CLI,SERVER,BODY,QUIET');
        $arguments['DATA'] = $data;

        if (($node = $this->form->attributes->getNamedItem('action')) && $node->value) {
            $arguments['PATH'] = $node->value;
        }

        if (($node = $this->form->attributes->getNamedItem('method')) && $node->value) {
            $arguments['VERB'] = $node->value;
        }

        return $this->visit(...array_values($arguments));
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
        $actualCode = $this->fw->get('CODE');

        return $this->expect($code === $actualCode, 'equals', $code, $actualCode, $message ?? sprintf('Response code is not equals to "%d".', $code));
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
        $actualCode = $this->fw->get('CODE');

        return $this->expect($code !== $actualCode, 'notequals', $code, $actualCode, $message ?? sprintf('Response code is equals to "%d".', $code));
    }

    /**
     * Expect request is successful.
     *
     * @return Test
     */
    public function isSuccess(): Test
    {
        return $this->isCode(200, 'Request is not successful.');
    }

    /**
     * Expect request is not successful.
     *
     * @return Test
     */
    public function isNotSuccess(): Test
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
     * Expect response content is empty.
     *
     * @return Test
     */
    public function isEmpty(): Test
    {
        $actualOutput = $this->fw->get('OUTPUT');

        return $this->expect(empty($actualOutput), 'empty', $actualOutput, 'Response is not empty.');
    }

    /**
     * Expect response content is not empty.
     *
     * @return Test
     */
    public function isNotEmpty(): Test
    {
        $actualOutput = $this->fw->get('OUTPUT');

        return $this->expect(!empty($actualOutput), 'notempty', $actualOutput, 'Response is empty.');
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
        $actualOutput = $this->fw->get('OUTPUT');
        $equals = $text === $actualOutput;

        return $this->expect($equals, 'equals', $text, $actualOutput, 'Response is not equals to expected text.');
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
        $actualOutput = $this->fw->get('OUTPUT');
        $notequals = $text !== $actualOutput;

        return $this->expect($notequals, 'notequals', $text, $actualOutput, 'Response is equals to expected text.');
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
        $actualOutput = $this->fw->get('OUTPUT');
        $contains = $actualOutput && (false !== strpos($actualOutput, $text));

        return $this->expect($contains, 'contains', $text, $actualOutput, 'Response not contains expected text.');
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
        $actualOutput = $this->fw->get('OUTPUT');
        $notcontains = !$actualOutput || (false === strpos($actualOutput, $text));

        return $this->expect($notcontains, 'notcontains', $text, $actualOutput, 'Response contains expected text.');
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
        $actualOutput = $this->fw->get('OUTPUT');
        $match = $actualOutput && preg_match($pattern, $actualOutput);

        return $this->expect($match, 'regexp', $pattern, $actualOutput, 'Response not match expected pattern.');
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
        $actualOutput = $this->fw->get('OUTPUT');
        $notmatch = !$actualOutput || !preg_match($pattern, $actualOutput);

        return $this->expect($notmatch, 'notregexp', $pattern, $actualOutput, 'Response match expected pattern.');
    }

    /**
     * Expect response content has element match xpath.
     *
     * @param string $xpath
     *
     * @return Test
     */
    public function elementExists(string $xpath): Test
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
     * @return Test
     */
    public function elementNotExists(string $xpath): Test
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
     * @return Test
     */
    public function elementEquals(string $xpath, string $text): Test
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
     * @return Test
     */
    public function elementNotEquals(string $xpath, string $text): Test
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
     * @return Test
     */
    public function elementContains(string $xpath, string $text): Test
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
     * @return Test
     */
    public function elementNotContains(string $xpath, string $text): Test
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
     * @return Test
     */
    public function elementRegexp(string $xpath, string $pattern): Test
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
     * @return Test
     */
    public function elementNotRegexp(string $xpath, string $pattern): Test
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
     * @return Test
     */
    public function expect(bool $success, string $assertion, ...$args): Test
    {
        $this->latest = $success;

        // @codeCoverageIgnoreStart
        if ($this->test) {
            $assert = 'assert'.$assertion;

            $this->test->$assert(...$args);
        }
        // @codeCoverageIgnoreEnd

        return $this;
    }

    /**
     * Resolve DomDocument.
     */
    protected function resolveDocument(): void
    {
        $actualOutput = $this->fw->get('OUTPUT');

        if (!$this->document && $actualOutput && $document = \DomDocument::loadHtml($actualOutput)) {
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
