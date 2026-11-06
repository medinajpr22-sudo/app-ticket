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

    public function description(): string
    {
        return __('enums.role_slug_description.'.$this->name);
    }
}
