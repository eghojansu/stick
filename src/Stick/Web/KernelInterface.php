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

use Fal\Stick\Container\ContainerInterface;

/**
 * Stick kernel interface.
 *
 * It holds system environment and service container.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface KernelInterface
{
    const MASTER_REQUEST = 1;
    const SUB_REQUEST = 2;

    const ON_PREPARE = 'kernel.prepare';
    const ON_REQUEST = 'kernel.request';
    const ON_CONTROLLER = 'kernel.controller';
    const ON_CONTROLLER_ARGUMENTS = 'kernel.controller_arguments';
    const ON_VIEW = 'kernel.view';
    const ON_RESPONSE = 'kernel.response';
    const ON_FINISH_REQUEST = 'kernel.finish';
    const ON_EXCEPTION = 'kernel.exception';

    /**
     * Returns current environment name.
     *
     * @return string
     */
    public function getEnvironment(): string;

    /**
     * Returns true if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebug(): bool;

    /**
     * Returns container instance.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface;

    /**
     * Handle request and returns response.
     *
     * @param Request $request
     * @param int     $requestType
     *
     * @return Response
     */
    public function handle(Request $request, int $requestType = self::MASTER_REQUEST): Response;
}
