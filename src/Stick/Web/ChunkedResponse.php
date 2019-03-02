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

namespace Fal\Stick\Web;

/**
 * Chunked response.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ChunkedResponse extends Response
{
    /**
     * @var int
     */
    protected $kbps = 0;

    /**
     * Returns kbps.
     *
     * @return int
     */
    public function getKbps(): int
    {
        return $this->kbps;
    }

    /**
     * Assign kbps.
     *
     * @param int $kbps
     *
     * @return ChunkedResponse
     */
    public function setKbps(int $kbps): ChunkedResponse
    {
        $this->kbps = $kbps;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function send(): Response
    {
        if (0 >= $this->kbps || !$this->content) {
            return parent::send();
        }

        $this->sendChunked();

        return $this;
    }

    /**
     * Send file per kbps.
     */
    protected function sendChunked()
    {
        $ctr = 0;
        $now = microtime(true);

        foreach (str_split($this->content, 1024) as $part) {
            // Throttle output
            ++$ctr;

            if ($ctr / $this->kbps > ($elapsed = microtime(true) - $now) && !connection_aborted()) {
                usleep(intval(1e6 * ($ctr / $this->kbps - $elapsed)));
            }

            echo $part;
        }
    }
}
