<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite;

use PHPUnit\Framework\TestCase;

/**
 * Test case base class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MyTestCase extends TestCase
{
    protected $_namespace;
    protected $_classname;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $full = static::class;
        $last = strrpos($full, '\\') + 1;
        $this->_namespace = str_replace('\\Test\\', '\\', substr($full, 0, $last));
        $this->_classname = lcfirst(substr($full, $last, -4));
    }

    public function __get($prop)
    {
        if (0 === strpos($this->_classname, $prop)) {
            if (isset($this->$prop)) {
                return $this->$prop;
            }

            return $this->$prop = $this->createInstance();
        }

        throw new \LogicException('Undefined property: '.$prop.'.');
    }

    protected function createInstance()
    {
        $class = $this->_namespace.ucfirst($this->_classname);

        return  new $class();
    }

    public function tmp($file = null, $createDir = false)
    {
        $dir = TEST_TEMP.'/test_dir_'.$this->_classname;

        if ($createDir && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir.$file;
    }

    public static function sessDestroy()
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_destroy();
        }
    }

    public static function project($path = null)
    {
        return dirname(TEST_ROOT).$path;
    }

    public static function src($path = null)
    {
        return self::project('/src/Stick'.$path);
    }

    public static function fixture($path = null)
    {
        return TEST_FIXTURE.$path;
    }

    public static function read($path)
    {
        return file_get_contents(self::fixture($path));
    }

    public static function response($file, array $replace = null)
    {
        $content = self::read('/response/'.$file);

        return $replace ? strtr($content, $replace) : $content;
    }

    public static function clear($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $dirFlags = \FilesystemIterator::CURRENT_AS_FILEINFO|\FilesystemIterator::SKIP_DOTS;
        $itFlags = \RecursiveIteratorIterator::CHILD_FIRST;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, $dirFlags), $itFlags);

        foreach ($it as $path) {
            if (is_dir($path)) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
