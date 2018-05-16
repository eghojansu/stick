<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Security;

use Fal\Stick\Validation\AbstractValidator;

final class AuthValidator extends AbstractValidator
{
    protected $messages = [
        'password' => 'This value should be equal to current user password.',
    ];

    private $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    protected function _password($val): bool
    {
        $user = $this->auth->getUser();

        return $user ? $this->auth->getEncoder()->verify($val, $user->getPassword()) : true;
    }
}
