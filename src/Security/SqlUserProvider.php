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

namespace Fal\Stick\Security;

use Fal\Stick\Sql\Connection;

/**
 * User provider that utilize Sql Connection.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class SqlUserProvider implements UserProviderInterface
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var UserTransformerInterface
     */
    private $transformer;

    /**
     * @var array
     */
    private $options;

    /**
     * Class constructor.
     *
     * @param Connection               $db
     * @param callable                 $transformer
     * @param UserTransformerInterface $options
     */
    public function __construct(Connection $db, UserTransformerInterface $transformer, array $options = [])
    {
        $this->db = $db;
        $this->transformer = $transformer;
        $this->setOption($options);
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOption(): array
    {
        return $this->options;
    }

    /**
     * Set options.
     *
     * @param array $options
     *
     * @return SqlUserProvider
     */
    public function setOption(array $options): SqlUserProvider
    {
        $this->options = $options + [
            'table' => 'user',
            'username' => 'username',
            'id' => 'id',
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername(string $username): ?UserInterface
    {
        return $this->transform($this->options['username'], $username);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?UserInterface
    {
        return $this->transform($this->options['id'], $id);
    }

    /**
     * Transform record to UserInterface.
     *
     * @param string $key
     * @param scalar $val
     *
     * @return UserInterface|null
     */
    private function transform(string $key, $val): ?UserInterface
    {
        $user = $this->db->exec(
            'SELECT * FROM '.$this->db->quotekey($this->options['table']).
            ' WHERE '.$this->db->quotekey($key).' = ? LIMIT 1',
            [$val]
        );

        return $user ? $this->transformer->transform($user[0]) : null;
    }
}
