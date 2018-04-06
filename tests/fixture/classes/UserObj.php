<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\classes;

class UserObj
{
    public $profile;
    public $roles;
    public $age;

    /**
     * Class constructor
     *
     * @param mixed  $profile
     * @param array $roles
     * @param int   $age
     */
    public function __construct(ProfileObj $profile, array $roles, int $age)
    {
        $this->profile = $profile;
        $this->roles = $roles;
        $this->age = $age;
    }

    /**
     * Encode password
     *
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }
}
