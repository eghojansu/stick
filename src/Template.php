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
    const CONTEXT = '$context';
    const ARR_OPEN = "['";
    const ARR_CLOSE = "']";
    const REG_WORD = '/^\w+$/';

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
        'startswith' => __NAMESPACE__ . '\\' . 'startswith',
        'endswith' => __NAMESPACE__ . '\\' . 'endswith',
        'istartswith' => __NAMESPACE__ . '\\' . 'istartswith',
        'iendswith' => __NAMESPACE__ . '\\' . 'iendswith',
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
     * @param  bool   $global
     *
     * @return string
     */
    public function render(string $file, array $data = [], bool $global = true): string
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

                $data = $this->sandbox($parsed, $data + ($global ? $this->app->getHive() : []));

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
     * @param  array  $context
     *
     * @return string
     */
    protected function sandbox(string $file, array $context): string
    {
        ob_start();
        require $file;
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
            'endmacro' => '} endif',
            'endfor' => 'endforeach',
            'else' => 'else:',
        ];
        $parser = [
            'for' => 'parseFor',
            'set' => 'parseVar',
            'include' => 'parseInclude',
            'macro' => 'parseMacro',
            'import' => 'parseImport',
        ];

        if (preg_match('/^(if|elseif)\h+(.+)/s', $expr, $match)) {
            $parsed = $match[1] . ' (' . $this->parseVar($match[2]) . '):';
        } elseif (preg_match('/^(?|(' . implode('|', array_keys($parser)) . '))\h+(.+)/s', $expr, $match)) {
            $parse = $parser[$match[1]];
            $parsed = $this->$parse($match[2]);
        } elseif (preg_match('/^(?|(' . implode('|', array_keys($common)) . '))\b(.*)/s', $expr, $match)) {
            $parsed = $common[$match[1]] . rtrim($match[2]);
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
     * Parse macro expr
     *
     * @param  string $expr
     *
     * @return string
     */
    protected function parseMacro(string $expr): string
    {
        $args = '';
        $process = null;
        $name = '';
        $line = '';
        $last = '';
        $tmp = '';
        $len = strlen($expr);
        $quote = null;

        for ($ptr = 0; $ptr < $len; $ptr++) {
            $char = $expr[$ptr];
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
                    $name = $tmp;
                    $tmp = '';
                } elseif ($char === ')') {
                    $process = $tmp;
                    $tmp = '';
                } elseif ($char === ',') {
                    $process = $tmp . $char;
                    $tmp = '';
                } else {
                    $tmp .= $char;
                }
            } else {
                $tmp .= $char;
            }

            if ($process !== null) {
                $line .= '$' . ltrim($process);
                preg_match('/^\w+/', trim($process), $match);
                $last = $match[0];
                $args .= self::context($last) . ' = $' . $last . ';';
                $process = null;
            }
        }

        $context = substr(self::CONTEXT, 1);

        return 'if (!function_exists(' . "'" . $name . "'" . ')):' .
               'function ' . $name . '(' . $line . ') {' .
               'if (!isset(' . self::CONTEXT . ')):' .
               self::CONTEXT . ' = ' .
               (
                    $last === $context ?
                    '$GLOBALS' . self::ARR_OPEN . $context . self::ARR_CLOSE :
                    '[]'
               ) . ';' .
               'endif;' .
               $args
               ;
    }

    /**
     * Parse include expr
     *
     * @param  string $expr
     *
     * @return string
     */
    protected function parseInclude(string $expr): string
    {
        $context = self::CONTEXT;

        if (preg_match('/^(.+)\h+with\h+(.+)\h+only/s', $expr, $match)) {
            $file = $this->parseVar($match[1]);
            $context = $this->parseVar($match[2]);
        } elseif (preg_match('/^(.+)\h+with\h+(.+)/s', $expr, $match)) {
            $file = $this->parseVar($match[1]);
            $context = $this->parseVar($match[2]) . ' + ' . $context;
        } else {
            $file = $this->parseVar($expr);
        }

        return 'echo $this->render(' . $file . ', ' . $context . ', false)';
    }

    /**
     * Like include without outputing
     *
     * @param  string $expr
     *
     * @return string
     */
    protected function parseImport(string $expr): string
    {
        return '$this->render(' . $this->parseVar($expr) . ', ' . self::CONTEXT . ', false)';
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
            '/^(?:(\w+)(?:,\h*(\w+))?)(?:\h+with\h+(\w+))?\h+in\h+(?:(?:(\d+)\.{2}(\d+))|(.+))/s',
            $expr,
            $match
        );

        $res = 'foreach (';
        if (isset($match[4]) && $match[4]) {
            $res .= 'range(' . $match[4] . ',' . $match[5] . ')';
        } else {
            $res .= $this->parseVar($match[6]) . ' ?: []';
        }

        $res .= ' as ';
        $s = '$';

        if (isset($match[2]) && $match[2]) {
            $key = $match[1];
            $val = $match[2];
            $res .= $s . $key . ' => ' . $s . $val;
        } else {
            $key = '';
            $val = $match[1];
            $res .= $s . $val;
        }

        $res .= '):';

        if (isset($match[3]) && $match[3]) {
            $ctr = self::context($match[3]);
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
        $process = null;
        $sep = '';
        $var = '';
        $quote = null;
        $arr = false;
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
                if (in_array($char, ['[',']',','])) {
                    $process = $tmp;
                    $sep .= $char;
                    $tmp = '';
                } elseif ($char === '=' && $next === '>') {
                    $process = $tmp;
                    $sep .= $char . $next;
                    $tmp = '';
                    $ptr++;
                } elseif ($char === '(') {
                    $func = true;
                    $process = $tmp . ($obj ? $char : '');
                    $tmp = '';
                } elseif ($char === ')') {
                    $process = $tmp;
                    $tmp = $char;
                } elseif ($char === '.') {
                    if ($arr) {
                        $tmp .= self::ARR_CLOSE . self::ARR_OPEN;
                    } else {
                        $var = self::context($tmp);
                        $tmp = self::ARR_OPEN;
                        $arr = true;
                    }
                } elseif ($char === '-' && $next === '>') {
                    if ($obj) {
                        $tmp .= $char . $next;
                    } else {
                        $var = self::context($tmp);
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
                } else {
                    $tmp .= $char;
                }
            } else {
                $tmp .= $char;
            }

            if ($process === null && $ptr === $len - 1) {
                $process = $tmp;
                $tmp = '';
            }

            if ($process !== null) {
                if ($arr) {
                    $line = $var . $process . self::ARR_CLOSE;
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
                } elseif (
                    !is_numeric($process)
                    && !defined($process)
                    && !in_array($process, $keywords)
                    && preg_match(self::REG_WORD, $process)
                ) {
                    $line = self::context($process);
                } else {
                    $line = $process;
                }

                $res .= $line . $sep;
                $process = null;
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

    /**
     * Context var helper
     *
     * @param  string $var
     *
     * @return string
     */
    protected static function context(string $var): string
    {
        return self::CONTEXT . self::ARR_OPEN . $var . self::ARR_CLOSE;
    }
}
