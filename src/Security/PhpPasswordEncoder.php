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

namespace Ekok\Stick\Security;

/**
 * PHP password encoder.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class PhpPasswordEncoder implements PasswordEncoderInterface
{
    private $passwordAlgorithm;
    private $hashOptions;

    public function __construct(int $passwordAlgorithm = null, array $hashOptions = null)
    {
        $this->passwordAlgorithm = $passwordAlgorithm ?? PASSWORD_BCRYPT;
        $this->hashOptions = $hashOptions ?? array();
    }

    /**
     * {@inheritdoc}
     */
    public function hash(string $plainText): string
    {
        return password_hash($plainText, $this->passwordAlgorithm, $this->hashOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $plainText, string $hash): bool
    {
        return password_verify($plainText, $hash);
    }
}
