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

namespace Fal\Stick\Template;

use Fal\Stick\Fw;

/**
 * Template engine.
 *
 * Ported from F3/Template.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Environment
{
    /**
     * @var Fw
     */
    public $fw;

    /**
     * @var array
     */
    protected $directories;

    /**
     * @var string
     */
    protected $temp;

    /**
     * @var string
     */
    protected $tags = 'otherwise';

    /**
     * @var array
     */
    protected $customs = array();

    /**
     * @var array
     */
    protected $filters = array();

    /**
     * @var bool
     */
    protected $autoreload;

    /**
     * Class constructor.
     *
     * @param Fw           $fw
     * @param string|array $directories
     * @param string       $temp
     * @param bool         $autoreload
     */
    public function __construct(Fw $fw, $directories = null, string $temp = null, bool $autoreload = false)
    {
        $this->fw = $fw;
        $this->directories = $fw->split($directories);
        $this->temp = $temp ?? $fw->get('TEMP').'template/';
        $this->tags .= str_replace('_', '|', implode('', preg_grep('/^_(?=[[:alpha:]])/', get_class_methods($this))));
        $this->autoreload = $autoreload;
    }

    /**
     * Proxy to filters and framework methods.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (isset($this->filters[$method])) {
            return $this->fw->call($this->filters[$method], ...$arguments);
        }

        return $this->fw->$method(...$arguments);
    }

    /**
     * Returns autoreload status.
     *
     * @return bool
     */
    public function isAutoreload(): bool
    {
        return $this->autoreload;
    }

    /**
     * Sets autoreload status.
     *
     * @param bool $autoreload
     *
     * @return Environtment
     */
    public function setAutoreload(bool $autoreload): Environment
    {
        $this->autoreload = $autoreload;

        return $this;
    }

    /**
     * Returns template filepath.
     *
     * @param string $templateName
     *
     * @return string
     */
    public function findTemplate(string $templateName): string
    {
        foreach ($this->directories as $dir) {
            if (is_file($file = $dir.$templateName)) {
                return $file;
            }
        }

        throw new \LogicException(sprintf('Template not found: %s.', $templateName));
    }

    /**
     * Load template.
     *
     * @param string        $templateName
     * @param Template|null $child
     *
     * @return Template
     */
    public function loadTemplate(string $templateName, Template $child = null): Template
    {
        $file = $this->findTemplate($templateName);
        $hash = $this->fw->hash($file);
        $temp = $this->temp.$this->fw->get('SEED').'.'.$hash.'.php';
        $build = $this->autoreload || (!is_file($temp) || filemtime($temp) < filemtime($file));

        if ($build) {
            $source = $this->fw->trimTrailingSpace(rtrim($this->build($this->parseXml($this->fw->read($file)))));

            $this->fw->mkdir($this->temp);
            $this->fw->write($temp, $source);
        }

        return new Template($this, $templateName, $file, $temp, $child);
    }

    /**
     * Render template.
     *
     * @param string     $templateName
     * @param array|null $context
     *
     * @return string
     */
    public function render(string $templateName, array $context = null): string
    {
        return $this->loadTemplate($templateName)->render($context);
    }

    /**
     * Extend template with custom tag.
     *
     * @param string $tag
     * @param mixed  $func
     *
     * @return Environment
     */
    public function extend(string $tag, $func): Environment
    {
        $this->tags .= '|'.$tag;
        $this->customs[$tag] = $func;

        return $this;
    }

    /**
     * Register token filters.
     *
     * @param string $key
     * @param mixed  $func
     *
     * @return mixed
     */
    public function filter(string $key, $func): Environment
    {
        $this->filters[strtolower($key)] = $func;

        return $this;
    }

    /**
     * Encode characters to equivalent HTML entities.
     *
     * @param mixed $arg
     *
     * @return mixed
     */
    public function esc($arg)
    {
        return is_string($arg) ? htmlspecialchars($arg) : $arg;
    }

    /**
     * Decode HTML entities to equivalent characters.
     *
     * @param mixed $arg
     *
     * @return mixed
     */
    public function raw($arg)
    {
        return is_string($arg) ? htmlspecialchars_decode($arg) : $arg;
    }

    /**
     * Convert JS-style token to PHP expression.
     *
     * @param string $str
     *
     * @return string
     */
    public function compile(string $str): string
    {
        return preg_replace_callback(
            '/(?<!\w)@(\w+(?:(?:\->|::)\w+)?)'.
            '((?:\.\w+|\[(?:(?:[^\[\]]*|(?R))*)\]|(?:\->|::)\w+|\()*)/',
            function ($expr) {
                $str = '$'.$expr[1];

                if (isset($expr[2])) {
                    $str .= preg_replace_callback(
                        '/\.(\w+)(\()?|\[((?:[^\[\]]*|(?R))*)\]/',
                        function ($sub) {
                            if (empty($sub[2])) {
                                if (ctype_digit($sub[1])) {
                                    $sub[1] = (int) $sub[1];
                                }

                                return '['.(isset($sub[3]) ? $this->compile($sub[3]) : var_export($sub[1], true)).']';
                            }

                            return function_exists($sub[1]) ? $sub[0] : '['.var_export($sub[1], true).']'.$sub[2];
                        },
                        $expr[2]
                    );
                }

                return $str;
            },
            $str
        );
    }

    /**
     * Convert token to variable, also parsing filter.
     *
     * @param string $str
     *
     * @return string
     */
    public function token(string $str): string
    {
        $expr = trim(preg_replace('/\{\{(.+?)\}\}/s', trim('\1'), $this->compile($str)));

        if (preg_match('/^(.+)(?<!\|)\|((?:\h*\w+(?:\h*[,;]?))+)$/s', $expr, $parts)) {
            $expr = trim($parts[1]);

            foreach ($this->fw->split(trim($parts[2], "\xC2\xA0")) as $func) {
                $self = !is_callable($func) || isset($this->filters[$func]) ? '$this->' : '';
                $expr = $self.$func.'('.$expr.')';
            }
        }

        return $expr;
    }

    /**
     * Tokenize if needed.
     *
     * @param string|null $text
     *
     * @return string
     */
    public function tokenize(string $text = null): string
    {
        if (!$text) {
            return '';
        }

        if (preg_match('/\{\{(.+?)\}\}/', $text)) {
            return $this->token($text);
        }

        return $this->fw->stringify($text);
    }

    /**
     * Build text.
     *
     * @param string $text
     *
     * @return string
     */
    public function buildText(string $text): string
    {
        return preg_replace_callback('/\{~(.+?)~\}|\{\#(.+?)\#\}|\{\-(.+?)\-\}|\{\{(.+?)\}\}((\r?\n)*)/s', function ($e) {
            if ($e[1]) {
                return '<?php '.$this->token($e[1]).' ?>';
            } elseif ($e[2]) {
                return '';
            } elseif ($e[3]) {
                return trim($e[3]);
            } else {
                $str = '<?= '.trim($this->token($e[4])).(empty($e[6]) ? '' : ".'".$e[6]."'").' ?>';

                if (isset($e[5])) {
                    $str .= $e[5];
                }

                return $str;
            }
        }, $text);
    }

    /**
     * Assemble markup.
     *
     * @param string|array $nodes
     *
     * @return string
     */
    public function build($nodes): string
    {
        if (is_string($nodes)) {
            return $this->buildText($nodes);
        }

        $out = '';

        foreach ($nodes as $tag => $node) {
            if (is_int($tag)) {
                $out .= $this->build($node);
            } elseif (isset($this->customs[$tag])) {
                $out .= $this->fw->call($this->customs[$tag], $this, $node);
            } else {
                $out .= $this->{'_'.$tag}($node);
            }
        }

        return $out;
    }

    /**
     * Unbuild nodes.
     *
     * @param string|array $nodes
     *
     * @return string
     */
    public function unbuild($nodes): string
    {
        if (is_string($nodes)) {
            return $nodes;
        }

        $out = '';

        foreach ($nodes as $tag => $node) {
            if (is_int($tag)) {
                $out .= $this->unbuild($node);
            } else {
                $out .= '<'.$tag;

                foreach ($node['@attrib'] ?? array() as $attribute => $value) {
                    $out .= ' ';
                    $out .= is_int($attribute) ? $value : $attribute.'="'.$value.'"';
                }

                unset($node['@attrib']);

                if (empty($node)) {
                    $out .= ' />';
                } else {
                    $out .= '>'.$this->unbuild($node).'</'.$tag.'>';
                }
            }
        }

        return $out;
    }

    /**
     * Returns tree from html structure.
     *
     * @param string $text
     *
     * @return array
     */
    public function parseXml(string $text): array
    {
        // Remove PHP code and comments
        $text = preg_replace('/\h*<\?(?!xml)(?:php|\s*=)?.+?\?>\h*|\{\*.+?\*\}/is', '', $text);

        // Build tree structure
        for ($ptr = 0,$w = 5,$len = strlen($text),$tree = array(),$tmp = ''; $ptr < $len;) {
            if (preg_match('/^(.{0,'.$w.'}?)<(\/?)(?:F3:)?'.
                '('.$this->tags.')\b((?:\s+[\w.:@!-]+'.
                '(?:\h*=\h*(?:"(?:.*?)"|\'(?:.*?)\'))?|'.
                '\h*\{\{.+?\}\})*)\h*(\/?)>/is',
                substr($text, $ptr), $match)) {
                if (strlen($tmp) || $match[1]) {
                    $tree[] = $tmp.$match[1];
                }

                // Element node
                if ($match[2]) {
                    // Find matching start tag
                    $stack = array();
                    for ($i = count($tree) - 1; $i >= 0; --$i) {
                        $item = $tree[$i];
                        if (is_array($item) &&
                            array_key_exists($match[3], $item) &&
                            !isset($item[$match[3]][0])) {
                            // Start tag found
                            $tree[$i][$match[3]] += array_reverse($stack);
                            $tree = array_slice($tree, 0, $i + 1);
                            break;
                        } else {
                            $stack[] = $item;
                        }
                    }
                } else {
                    // Start tag
                    $node = &$tree[][$match[3]];
                    $node = array();
                    if ($match[4]) {
                        // Process attributes
                        preg_match_all(
                            '/(?:(\{\{.+?\}\})|([^\s\/"\'=]+))'.
                            '\h*(?:=\h*(?:"(.*?)"|\'(.*?)\'))?/s',
                            $match[4], $attr, PREG_SET_ORDER);
                        foreach ($attr as $kv) {
                            if (!empty($kv[1]) && !isset($kv[3]) && !isset($kv[4])) {
                                $node['@attrib'][] = $kv[1];
                            } else {
                                $node['@attrib'][$kv[1] ?: $kv[2]] =
                                    (isset($kv[3]) && '' !== $kv[3] ?
                                        $kv[3] :
                                        (isset($kv[4]) && '' !== $kv[4] ?
                                            $kv[4] : null));
                            }
                        }
                    }
                }

                $tmp = '';
                $ptr += strlen($match[0]);
                $w = 5;
            } else {
                // Text node
                $tmp .= substr($text, $ptr, $w);
                $ptr += $w;

                if ($w < 50) {
                    ++$w;
                }
            }
        }

        if (strlen($tmp)) {
            // Append trailing text
            $tree[] = $tmp;
        }

        // Break references
        unset($node);

        return $tree;
    }

    /**
     * Csv expression to array expression.
     *
     * @param string $str
     *
     * @return string
     */
    public function contextify(string $str): string
    {
        if (preg_match_all('/(\w+)\h*=\h*(.+?)(?=,|$)/', $this->token($str), $pairs, PREG_SET_ORDER)) {
            $arr = '';

            foreach ($pairs as $pair) {
                $arr .= ",'$pair[1]'=>";

                if (preg_match("/^'.*'\$/", $pair[2]) || preg_match('/\$/', $pair[2])) {
                    $arr .= $pair[2];
                } else {
                    $arr .= $this->fw->stringify($this->fw->cast($pair[2]));
                }
            }

            return '['.ltrim($arr, ',').']';
        }

        return '[]';
    }

    /**
     * Returns attrib and remove it from node.
     *
     * @param array &$node
     * @param array $required
     *
     * @return array
     */
    public function attrib(array &$node, array $required): array
    {
        $attrib = array();

        foreach ($required as $key => $value) {
            $attrib[$key] = $node['@attrib'][$key] ?? $value;

            if ('***required***' === $attrib[$key]) {
                throw new \LogicException(sprintf('Missing property: %s.', $key));
            }
        }

        unset($node['@attrib']);

        return $attrib;
    }

    /**
     * Remove empty nodes.
     *
     * @param array $nodes
     *
     * @return array
     */
    public function clearEmptyNode(array $nodes): array
    {
        $newNode = array();

        foreach ($nodes as $pos => $node) {
            if (!is_string($node) || preg_replace('/\s+/', '', $node)) {
                $newNode[$pos] = $node;
            }
        }

        return $newNode;
    }

    /**
     * Parse otherwise tag.
     *
     * @param array       &$node
     * @param string|null $default
     *
     * @return string
     */
    public function otherwise(array &$node, string $default = null): string
    {
        // Grab <otherwise> block
        foreach ($node as $pos => $block) {
            if (isset($block['otherwise'])) {
                $otherwise = $block['otherwise'];
                unset($node[$pos]);

                if (!isset($otherwise['@attrib']['if'])) {
                    $otherwise['@attrib']['if'] = $default;
                }

                return $this->_check($otherwise);
            }
        }

        return '';
    }

    /**
     * Template -extends- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _extends(array $node): string
    {
        extract($this->attrib($node, array(
            'href' => '***required***',
        )));

        return '<?php $this->extend('.$this->tokenize($href).') ?>';
    }

    /**
     * Template -include- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _include(array $node): string
    {
        extract($this->attrib($node, array(
            'if' => '',
            'href' => '***required***',
            'with' => '',
        )));

        return
            '<?php '.($if ? 'if ('.$this->token($if).') ' : '').
            'echo $this->env->render('.$this->tokenize($href).','.$this->contextify($with).'+$__context)'.
            ' ?>';
    }

    /**
     * Template -set- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _set(array $node): string
    {
        $out = '';

        foreach ($node['@attrib'] as $key => $val) {
            $out .= '$'.$key.'='.$this->tokenize($val).';';
        }

        return '<?php '.trim($out, ';').' ?>';
    }

    /**
     * Template -block- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _block(array $node): string
    {
        extract($this->attrib($node, array(
            'name' => '***required***',
        )));

        if (empty($node)) {
            return '<?= $this->block('.$this->tokenize($name).') ?>';
        }

        return
            '<?php $this->start('.$this->tokenize($name).') ?>'.
            $this->build($node).
            '<?php $this->stop() ?>';
    }

    /**
     * Template -parent- tag handler.
     *
     * Write parent block content.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _parent(array $node): string
    {
        extract($this->attrib($node, array(
            'name' => null,
        )));

        return '<?= $this->parent('.$this->tokenize($name).') ?>';
    }

    /**
     * Template -exclude- tag handler.
     *
     * @param array $node
     *
     * @return string
     */
    public function _exclude(array $node): string
    {
        return '';
    }

    /**
     * Template -ignore- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _ignore(array $node): string
    {
        extract($this->attrib($node, array(
            'escape' => false,
        )));
        $text = $this->unbuild($node);

        return $this->fw->cast($escape) ? $this->esc($text) : $text;
    }

    /**
     * Template -loop- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _loop(array $node): string
    {
        extract($this->attrib($node, array(
            'from' => '',
            'to' => '',
            'step' => '',
        )));
        $otherwise = $this->otherwise($node);

        return
            '<?php for ('.
                $this->token($from).';'.
                $this->token($to).';'.
                $this->token($step).'): ?>'.
                $this->build($node).
            '<?php endfor ?>'.$otherwise;
    }

    /**
     * Template -repeat- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _repeat(array $node): string
    {
        extract($this->attrib($node, array(
            'counter' => '',
            'group' => '***required***',
            'key' => '',
            'value' => '***required***',
        )));
        $value = $this->token($value);
        $otherwise = $this->otherwise($node, '!isset('.$value.')');

        return
            '<?php '.($counter ? ($ctr = $this->token($counter)).'=0; ' : '').
                'foreach ('.$this->token($group).'?:[] as '.($key ? ($this->token($key).'=>') : '').
                    $value.'):'.(isset($ctr) ? (' '.$ctr.'++') : '').' ?>'.
                $this->build($node).
            '<?php endforeach ?>'.$otherwise;
    }

    /**
     * Template -check- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _check(array $node): string
    {
        extract($this->attrib($node, array(
            'if' => '***required***',
        )));
        $ifTrue = null;
        $ifFalse = null;
        $ifElse = array();
        $newNode = array();

        foreach ($this->clearEmptyNode($node) as $pos => $block) {
            if (isset($block['false'])) {
                $ifFalse = $block;
            } elseif (isset($block['true'])) {
                if (isset($block['true']['@attrib']['if'])) {
                    $ifElse[] = $block;
                } else {
                    $ifTrue = $block;
                }
            } else {
                $newNode[] = $block;
            }
        }

        if ($ifTrue) {
            array_unshift($newNode, $ifTrue);
        } elseif (!$newNode) {
            throw new \LogicException('Invalid check statement.');
        }

        if ($ifElse) {
            array_push($newNode, ...$ifElse);
        }

        if ($ifFalse) {
            array_push($newNode, $ifFalse);
        }

        return
            '<?php if ('.$this->token($if).'): ?>'.
                $this->build($newNode).
            '<?php endif ?>';
    }

    /**
     * Template -true- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _true(array $node): string
    {
        extract($this->attrib($node, array(
            'if' => '',
        )));

        if ($if) {
            return '<?php elseif ('.$this->token($if).'): ?>'.$this->build($node);
        }

        return $this->build($node);
    }

    /**
     * Template -false- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _false(array $node): string
    {
        unset($node['@attrib']);

        return '<?php else: ?>'.$this->build($node);
    }

    /**
     * Template -switch- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _switch(array $node): string
    {
        extract($this->attrib($node, array(
            'expr' => '***required***',
        )));
        $newNode = $this->clearEmptyNode($node);

        return
            '<?php switch ('.$this->token($expr).'): ?>'.
                $this->build($newNode).
            '<?php endswitch ?>';
    }

    /**
     * Template -case- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _case(array $node): string
    {
        extract($this->attrib($node, array(
            'value' => '***required***',
            'break' => null,
        )));

        return
            '<?php case '.$this->tokenize($value).': ?>'.
                $this->build($node).
            '<?php '.($break ? 'if ('.$this->token($break).') ' : '').'break ?>';
    }

    /**
     * Template -default- tag handler.
     *
     * @param array $node
     *
     * @return string
     **/
    public function _default(array $node): string
    {
        unset($node['@attrib']);

        return
            '<?php default: ?>'.
                $this->build($node).
            '<?php break ?>';
    }
}
