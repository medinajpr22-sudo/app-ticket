<?php

namespace App\Domain\Identity\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

enum RoleSlug: string
{
    use HasTranslatedLabel;

    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case User = 'user';

    /**
     * Roles that belong to the IT staff and may join support teams.
     *
     * @return list<self>
     */
    public static function staff(): array
    {
        return [self::SuperAdmin, self::Admin];
    }

    public function isStaff(): bool
    {
        return in_array($this, self::staff(), true);
    }

    /**
     * Roles whose permissions can be edited. The super admin always has every permission.
     */
    public function isDelegable(): bool
    {
        return $this !== self::SuperAdmin;
    }

    /**
     * @return list<self>
     */
    public static function delegable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $role): bool => $role->isDelegable()));
    }

    public function description(): string
    {
        return __('enums.role_slug_description.'.$this->name);
    }
}
