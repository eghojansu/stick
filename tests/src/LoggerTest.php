<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test;

use Fal\Stick\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    const DIR = TEMP.'logtest/';

    private $logger;

    public function setUp()
    {
        $this->logger = new Logger(self::DIR, Logger::LEVEL_DEBUG);

        if (!is_dir(self::DIR)) {
            mkdir(self::DIR);
        }
    }

    public function tearDown()
    {
        if (is_dir(self::DIR)) {
            foreach (glob(self::DIR.'*') as $file) {
                unlink($file);
            }
            rmdir(self::DIR);
        }
    }

    public function testErrorCodeToLogLevel()
    {
        $this->assertEquals(Logger::LEVEL_EMERGENCY, Logger::errorCodeToLogLevel(E_ERROR));
        $this->assertEquals(Logger::LEVEL_ALERT, Logger::errorCodeToLogLevel(E_WARNING));
        $this->assertEquals(Logger::LEVEL_CRITICAL, Logger::errorCodeToLogLevel(E_STRICT));
        $this->assertEquals(Logger::LEVEL_ERROR, Logger::errorCodeToLogLevel(E_USER_ERROR));
        $this->assertEquals(Logger::LEVEL_WARNING, Logger::errorCodeToLogLevel(E_USER_WARNING));
        $this->assertEquals(Logger::LEVEL_NOTICE, Logger::errorCodeToLogLevel(E_USER_NOTICE));
        $this->assertEquals(Logger::LEVEL_INFO, Logger::errorCodeToLogLevel(E_DEPRECATED));
        $this->assertEquals(Logger::LEVEL_DEBUG, Logger::errorCodeToLogLevel(0));
    }

    public function testEmergency()
    {
        $logs = $this->logger->emergency('foo')->files();

        $this->assertContains('foo', file_get_contents($logs[0]));
    }

    public function testAlert()
    {
        $logs = $this->logger->alert('foo')->files();

        $this->assertContains('foo', file_get_contents($logs[0]));
    }

    public function testCritical()
    {
        $logs = $this->logger->critical('foo')->files();

        $this->assertContains('foo', file_get_contents($logs[0]));
    }

    public function testError()
    {
        $logs = $this->logger->error('foo')->files();

        $this->assertContains('foo', file_get_contents($logs[0]));
    }

    public function testWarning()
    {
        $logs = $this->logger->warning('foo')->files();

        $this->assertContains('foo', file_get_contents($logs[0]));
    }

    public function testNotice()
    {
        $logs = $this->logger->notice('foo')->files();

        $this->assertContains('foo', file_get_contents($logs[0]));
    }

    public function testInfo()
    {
        $logs = $this->logger->info('foo')->files();

        $this->assertContains('foo', file_get_contents($logs[0]));
    }

    public function testDebug()
    {
        $logs = $this->logger->debug('foo')->files();

        $this->assertContains('foo', file_get_contents($logs[0]));
    }

    public function testLogByCode()
    {
        $logs = $this->logger->logByCode(0, 'foo')->files();

        $this->assertContains('foo', file_get_contents($logs[0]));
    }

    public function logProvider()
    {
        return [
            [Logger::LEVEL_EMERGENCY.' foo', Logger::LEVEL_EMERGENCY, 'foo'],
            [Logger::LEVEL_ALERT.' foo', Logger::LEVEL_ALERT, 'foo'],
            [Logger::LEVEL_CRITICAL.' foo', Logger::LEVEL_CRITICAL, 'foo'],
            [Logger::LEVEL_ERROR.' foo', Logger::LEVEL_ERROR, 'foo'],
            [Logger::LEVEL_WARNING.' foo', Logger::LEVEL_WARNING, 'foo'],
            [Logger::LEVEL_NOTICE.' foo', Logger::LEVEL_NOTICE, 'foo'],
            [Logger::LEVEL_INFO.' foo', Logger::LEVEL_INFO, 'foo'],
            [Logger::LEVEL_DEBUG.' foo', Logger::LEVEL_DEBUG, 'foo'],
            [Logger::LEVEL_EMERGENCY.' foo baz', Logger::LEVEL_EMERGENCY, 'foo {bar}', ['bar' => 'baz']],
        ];
    }

    /**
     * @dataProvider logProvider
     */
    public function testLog($expected, $level, $message, $context = [])
    {
        $logs = $this->logger->log($level, $message, $context)->files();

        $this->assertContains($expected, file_get_contents($logs[0]));
    }

    public function testLogLevel()
    {
        $this->logger->setLogLevelThreshold(Logger::LEVEL_EMERGENCY);

        $logs = $this->logger->log(Logger::LEVEL_DEBUG, 'foo')->files();

        $this->assertEmpty($logs);
    }

    public function logFrequencyProvider()
    {
        return [
            [Logger::LOG_DAILY],
            [Logger::LOG_WEEKLY],
            [Logger::LOG_MONTHLY],
            ['freeform'],
        ];
    }

    /**
     * @dataProvider logFrequencyProvider
     */
    public function testLogFrequency($frequency)
    {
        $this->logger->setLogFrequency($frequency);

        $first = 'foo';
        $second = 'bar';

        $this->logger->log(Logger::LEVEL_ERROR, $first);
        $this->logger->log(Logger::LEVEL_CRITICAL, $second);

        $files = $this->logger->files();
        $log = file_get_contents($files[0]);

        $this->assertContains(Logger::LEVEL_ERROR.' '.$first, $log);
        $this->assertContains(Logger::LEVEL_CRITICAL.' '.$second, $log);
    }

    public function filesProvider()
    {
        $prefix = self::DIR.'log_';
        $ext = '.log';
        $fd = date('Y-m-d');
        $md = date('Y-m');

        return [
            [
                [],
            ],
            [
                [],
                [$prefix.'invalid'.$ext],
            ],
            [
                [$prefix.$fd.$ext],
                [$prefix.$fd.$ext],
            ],
            [
                [$prefix.$md.'-01'.$ext],
                [$prefix.$md.'-01'.$ext, $prefix.$md.'-02'.$ext],
                new \DateTime($md.'-01'),
            ],
            [
                [$prefix.$md.'-01'.$ext, $prefix.$md.'-02'.$ext],
                [$prefix.$md.'-01'.$ext, $prefix.$md.'-02'.$ext],
                new \DateTime($md.'-01'),
                new \DateTime($md.'-02'),
            ],
        ];
    }

    /**
     * @dataProvider filesProvider
     */
    public function testFiles($expected, $touches = null, $from = null, $to = null)
    {
        foreach ($touches ?? [] as $file) {
            touch($file);
        }

        $this->assertEquals($expected, $this->logger->files($from, $to));
    }

    public function clearProvider()
    {
        $prefix = self::DIR.'log_';
        $ext = '.log';
        $fd = date('Y-m-d');
        $md = date('Y-m');

        return [
            [
            ],
            [
                [$prefix.$fd.$ext],
            ],
            [
                [$prefix.$md.'-01'.$ext, $prefix.$md.'-02'.$ext],
                new \DateTime($md.'-01'),
            ],
            [
                [$prefix.$md.'-01'.$ext, $prefix.$md.'-02'.$ext],
                new \DateTime($md.'-01'),
                new \DateTime($md.'-02'),
            ],
        ];
    }

    /**
     * @dataProvider clearProvider
     */
    public function testClear($touches = null, $from = null, $to = null)
    {
        foreach ($touches ?? [] as $file) {
            touch($file);
        }

        $this->logger->clear($from, $to);

        $this->assertEmpty($this->logger->files($from, $to));
    }

    public function testGetDateFormat()
    {
        $this->assertEquals('Y-m-d G:i:s.u', $this->logger->getDateFormat());
    }

    public function testSetDateFormat()
    {
        $this->assertEquals('foo', $this->logger->setDateFormat('foo')->getDateFormat());
    }

    public function testGetDir()
    {
        $this->assertEquals(self::DIR, $this->logger->getDir());
    }

    public function testSetDir()
    {
        $this->assertEquals('foo', $this->logger->setDir('foo')->getDir());
    }

    public function testGetLogLevelThreshold()
    {
        $this->assertEquals('debug', $this->logger->getLogLevelThreshold());
    }

    public function testSetLogLevelThreshold()
    {
        $this->assertEquals('error', $this->logger->setLogLevelThreshold('error')->getLogLevelThreshold());
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Invalid log level: foo
     */
    public function testSetLogLevelThresholdException()
    {
        $this->logger->setLogLevelThreshold('foo');
    }

    public function testGetExtension()
    {
        $this->assertEquals('log', $this->logger->getExtension());
    }

    public function testSetExtension()
    {
        $this->assertEquals('foo', $this->logger->setExtension('foo')->getExtension());
    }

    public function testGetPrefix()
    {
        $this->assertEquals('log_', $this->logger->getPrefix());
    }

    public function testSetPrefix()
    {
        $this->assertEquals('foo', $this->logger->setPrefix('foo')->getPrefix());
    }

    public function testGetLogFrequency()
    {
        $this->assertEquals('daily', $this->logger->getLogFrequency());
    }

    public function testSetLogFrequency()
    {
        $this->assertEquals('foo', $this->logger->setLogFrequency('foo')->getLogFrequency());
    }
}
