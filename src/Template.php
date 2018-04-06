<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

/**
 * Template engine mimic Twig
 *
 * Note:
 * - Not support autoescaping
 */
class Template
{
    /** @var App */
    protected $app;

    /** @var array */
    protected $dirs = [];

    /** @var array */
    protected $events = [];

    /** @var string */
    protected $tmp;

    /** @var array */
    protected $funcs = [
        'esc' => 'htmlspecialchars',
    ];

    /**
     * Class constructor
     *
     * @param App   $app
     * @param tmp   $tmp  Temporary dir
     * @param array $dirs
     */
    public function __construct(App $app, string $tmp, $dirs)
    {
        $this->app = $app;
        $this->tmp = $tmp;
        $this->dirs = (array) $dirs;

        mkdir($tmp);
    }

    /**
     * Add function alias
     *
     * @param string $name
     * @param mixed $callback
     *
     * @return Template
     */
    public function addFunction(string $name, $callback = null): Template
    {
        $this->funcs[$name] = $callback ?? $name;

        return $this;
    }

    /**
     * Call registered function
     *
     * @param  string $func
     * @param  mixed $args
     *
     * @return mixed
     */
    public function call(string $func, ...$args)
    {
        return call_user_func_array($this->funcs[$func], $args);
    }

    /**
     * Register before render event
     *
     * @param  callable $callback
     *
     * @return Template
     */
    public function beforeRender(callable $callback): Template
    {
        $this->events['before'][] = $callback;

        return $this;
    }

    /**
     * Register after render event
     *
     * @param  callable $callback
     *
     * @return Template
     */
    public function afterRender(callable $callback): Template
    {
        $this->events['after'][] = $callback;

        return $this;
    }

    /**
     * Render template
     *
     * @param  string $file
     * @param  array  $data
     * @param  bool   $onlyData
     *
     * @return string
     */
    public function render(string $file, array $data = [], bool $onlyData = false): string
    {
        foreach ($this->dirs as $dir) {
            $view = $dir . $file;

            if (file_exists($view)) {
                $parsed = $this->tmp . hash($view) . '.php';

                if (!is_file($parsed) || filemtime($parsed) < filemtime($view)) {
                    $content = read($view);

                    foreach ($this->events['before'] ?? [] as $cb) {
                        $content = $this->app->call($cb, [$content, $view]);
                    }

                    file_put_contents($parsed, $this->parse($content));
                }

                $data = $this->sandbox($parsed, $data + ($onlyData ? [] : $this->app->getHive()));

                foreach ($this->events['after'] ?? [] as $cb) {
                    $data = $this->app->call($cb, [$data, $view]);
                }

                return $data;
            }
        }

        throw new \LogicException('View file does not exists: '. $file);
    }

    /**
     * Include parsed template file
     *
     * @param  string $file
     * @param  array  $data
     *
     * @return string
     */
    protected function sandbox(string $file, array $data): string
    {
        extract($data);

        ob_start();
        require($file);
        return ob_get_clean();
    }

    /**
     * Parse file template
     *
     * @param  string $text
     *
     * @return string
     */
    protected function parse(string $text): string
    {
        $parsed = '';
        $tmp = '';
        $len = strlen($text);
        $commentStart = -1;
        $isExpr = false;
        $isOut = false;

        for ($ptr = 0; $ptr < $len; $ptr++) {
            $char = $text[$ptr];
            $open = $char . ($text[$ptr + 1] ?? '');
            $close = ($text[$ptr - 1] ?? '') . $char;

            if ($open === '{#') {
                $parsed .= $tmp;
                $commentStart = strlen($parsed);
                $tmp = '';
                $ptr++;
            } elseif ($close === '#}' && $commentStart > -1) {
                $parsed = substr($parsed, 0, $commentStart);
                $commentStart = -1;
                $tmp = '';
            } elseif ($open === '{%' && $commentStart === -1) {
                $parsed .= $tmp;
                $isExpr = true;
                $tmp = '';
                $ptr++;
            } elseif ($close === '%}' && $isExpr) {
                $parsed .= $this->parseExpr(trim(substr($tmp, 0, -1)));
                $isExpr = false;
                $tmp = '';
            } elseif ($open === '{{' && $commentStart === -1) {
                $parsed .= $tmp;
                $isOut = true;
                $tmp = '';
                $ptr++;
            } elseif ($close === '}}' && $isOut) {
                $parsed .= $this->parseOut(trim(substr($tmp, 0, -1)));
                $isOut = false;
                $tmp = '';
            } else {
                $tmp .= $char;
            }
        }

        $parsed .= $tmp;

        return $parsed;
    }

    /**
     * Parse expr
     *
     * @param  string $expr
     *
     * @return string
     */
    protected function parseExpr(string $expr): string
    {
        $common = [
            'endfor' => 'endforeach',
            'else' => 'else:',
        ];

        if (preg_match('/^(if|elseif)\h+(.+)/s', $expr, $match)) {
            $parsed = $match[1] . ' (' . $this->parseVar($match[2]) . '):';
        } elseif (preg_match('/^for\h+(.+)/', $expr, $match)) {
            $parsed = $this->parseFor($match[1]);
        } elseif (preg_match('/^set\h+(.+)/s', $expr, $match)) {
            $parsed = $this->parseVar($match[1]);
        } elseif (preg_match('/^(?|' . implode('|', array_keys($common)) . ')\b/', $expr, $match)) {
            $parsed = $common[$match[0]];
        } else {
            $parsed = $expr;
        }

        return '<?php ' . $parsed . ' ?>';
    }

    /**
     * Parse out expr
     *
     * @param  string $expr
     *
     * @return string
     */
    protected function parseOut(string $expr): string
    {
        return '<?php echo ' . $this->parseVar($expr, true) . ' ?>';
    }

    /**
     * Parse for
     *
     * @param  string $expr
     *
     * @return string
     */
    protected function parseFor(string $expr): string
    {
        preg_match(
            '/^(?:(\w+)(?:,\h*(\w+))?)\h+in\h+(?:(\d+\.{2}\d+)|([\w\.]+))(?:,\h*(\w+))?/',
            $expr,
            $match
        );

        $res = 'foreach (';
        if (isset($match[3]) && $match[3]) {
            $x = explode('..', $match[3]);
            $res .= 'range(' . min(...$x) . ',' . max(...$x) . ')';
        } else {
            $res .= $this->parseVar($match[4]) . ' ?: []';
        }

        $res .= ' as ';
        if (isset($match[2]) && $match[2]) {
            $res .= '$' . $match[1] . ' => ' . '$' . $match[2];
        } else {
            $res .= '$' . $match[1];
        }
        $res .= '):';

        if (isset($match[5]) && $match[5]) {
            $ctr = '$' . $match[5];
            $res = $ctr . " = ['index'=>0,'index0'=>-1,'odd'=>null,'even'=>null]; " .
                   $res . ' ' .
                   $ctr . "['index']++;" .
                   $ctr . "['index0']++;" .
                   $ctr . "['odd'] = " . $ctr . "['index'] % 2 === 0;" .
                   $ctr . "['even'] = " . $ctr . "['index'] % 2 !== 0" ;
        }

        return $res;
    }

    /**
     * Parse var
     *
     * @param  string $expr
     *
     * @return string
     */
    protected function parseVar(string $expr): string
    {
        $expr = $this->parseFilter($expr);

        $len = strlen($expr);
        $res = '';
        $tmp = '';
        $process = '';
        $sep = '';
        $var = '';
        $quote = null;
        $arr = false;
        $arrOpen = "['";
        $arrClose = "']";
        $obj = false;
        $func = false;
        $keywords = ['and','or','xor'];

        for ($ptr = 0; $ptr < $len; $ptr++) {
            $char = $expr[$ptr];
            $prev = $expr[$ptr - 1] ?? null;
            $next = $expr[$ptr + 1] ?? null;

            if (($char === '"' || $char === "'") && $prev !== '\\') {
                if ($quote) {
                    $quote = $quote === $char ? null : $quote;
                } else {
                    $quote = $char;
                }
                $tmp .= $char;
            } elseif (!$quote) {
                if ($char === '(') {
                    $func = true;
                    $process = $tmp . ($obj ? $char : '');
                    $tmp = '';
                } elseif ($char === ')') {
                    $process = $tmp;
                    $tmp = $char;
                } elseif ($char === '.') {
                    if ($arr) {
                        $tmp .= $arrClose. $arrOpen;
                    } else {
                        $var = '$' . $tmp;
                        $tmp = $arrOpen;
                        $arr = true;
                    }
                } elseif ($char === '-' && $next === '>') {
                    if ($obj) {
                        $tmp .= $char . $next;
                    } else {
                        $var = '$' . $tmp;
                        $tmp = $char . $next;
                        $obj = true;
                    }
                    $ptr++;
                } elseif ($char === ' ') {
                    $process = $tmp;
                    $tmp = '';
                    $sep = $char;
                } elseif ($char === '~') {
                    $process = $tmp;
                    $tmp = '.';
                } elseif ($char === ',') {
                    $process = $tmp;
                    $tmp = $char;
                } else {
                    $tmp .= $char;
                }
            } else {
                $tmp .= $char;
            }

            if ($process === '' && $ptr === $len - 1) {
                $process = $tmp;
                $tmp = '';
            }

            if ($process !== '') {
                if ($arr) {
                    $line = $var . $process . $arrClose;
                    $arr = false;
                    $var = '';
                } elseif ($obj) {
                    $line = $var . $process;
                    $func = false;
                    $obj = false;
                    $var = '';
                } elseif ($func) {
                    $alt = $this->funcs[$process] ?? $process;
                    $line = is_string($alt) ? $alt . '(' : '$this->call(' .
                            "'" . $process . "'" . ($next === ')' ? '' : ', ');
                    $func = false;
                } elseif (preg_match('/^[\d.]+$/', $process) || defined($process)) {
                    $line = $process;
                } elseif (
                    preg_match('/^\w+$/', $process)
                    && !in_array($process, $keywords)
                ) {
                    $line = '$' . $process;
                } else {
                    $line = $process;
                }

                $res .= $line . $sep;
                $process = '';
                $sep = '';
            }
        }

        $res .= $tmp;

        return $res;
    }

    /**
     * Parse filter
     *
     * @param  string $expr
     *
     * @return string
     */
    protected function parseFilter(string $expr): string
    {
        $len = strlen($expr);
        $res = '';
        $tmp = '';
        $sep = '';
        $filter = [];
        $process = null;
        $quote = null;
        $pstate = 0;
        $findName = false;
        $inFilter = false;

        for ($ptr = 0; $ptr < $len; $ptr++) {
            $char = $expr[$ptr];
            $next = $expr[$ptr + 1] ?? null;
            $prev = $expr[$ptr - 1] ?? null;

            if (($char === '"' || $char === "'") && $prev !== '\\') {
                if ($quote) {
                    $quote = $quote === $char ? null : $quote;
                } else {
                    $quote = $char;
                }
                $tmp .= $char;
            } elseif (!$quote) {
                if ($char === '|') {
                    if ($findName) {
                        array_unshift($filter, ')');
                        if ($filter) {
                            $filter[] = '(';
                        }
                    }
                    $filter[] = $tmp;
                    $tmp = '';

                    $findName = true;
                } elseif ($char === '(' && $findName && !$inFilter) {
                    if ($filter) {
                        $filter[] = '(';
                    }
                    $filter[] = $tmp;
                    $tmp = '';

                    $findName = false;
                    $inFilter = true;
                    $pstate = 1;
                } elseif ($char === ')' && $inFilter && $pstate === 1) {
                    array_unshift($filter, ', ' . $tmp . $char);
                    $tmp = '';
                    $inFilter = false;
                    $pstate = 0;
                } elseif ($char === ' ' && !$inFilter) {
                    $process = $filter;
                    $filter = [];
                    $sep = $char;
                } else {
                    $tmp .= $char;
                    $pstate += $char === '(' ? 1 : ($char === ')' ? -1 : 0);
                }
            } else {
                $tmp .= $char;
            }

            if ($process === null && $ptr === $len - 1) {
                $process = $filter;
                $filter = [];
            }

            if ($process !== null) {
                if ($tmp !== '') {
                    if ($findName) {
                        array_unshift($process, ')');
                    }
                    if ($process) {
                        $process[] = '(';
                    }
                    $process[] = $tmp;
                    $tmp = '';
                }

                krsort($process);

                $res .= implode('', $process) . $sep;
                $process = null;
                $findName = false;
                $sep = '';
            }
        }

        return $res;
    }
}
