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
 * Json response.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class JsonResponse extends Response
{
    /**
     * @var array
     */
    protected $data;

    /**
     * Class constructor.
     *
     * @param array      $data
     * @param int|null   $code
     * @param array|null $headers
     */
    public function __construct(array $data, int $code = null, array $headers = null)
    {
        parent::__construct($data, $code, $headers);

        $this->data = $data;

        if (!$this->headers->exists('Content-Type')) {
            $this->headers->set('Content-Type', 'application/json');
        }
    }

    /**
     * Returns data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function setContent($content): Response
    {
        return parent::setContent(json_encode($content));
    }
}
