<?php

namespace App\Domain\Identity\Models;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Location;
use App\Domain\Organization\Models\Team;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $role_id
 * @property string $name
 * @property string $email
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property-read Role $role
 */
#[Fillable([
    'role_id',
    'department_id',
    'location_id',
    'manager_id',
    'name',
    'email',
    'job_title',
    'phone',
    'password',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('is_lead')
            ->withTimestamps();
    }

    /**
     * Only users whose account is enabled.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Only IT staff, the users who can belong to support teams.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function staff(Builder $query): void
    {
        $query->whereHas('role', fn (Builder $roleQuery) => $roleQuery->whereIn('slug', RoleSlug::staff()));
    }

    public function hasRole(RoleSlug $role): bool
    {
        return $this->role->slug === $role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleSlug::SuperAdmin);
    }

    public function isStaff(): bool
    {
        return $this->role->slug->isStaff();
    }

    /**
     * Reserved permissions only ever apply to super admins, even if a row
     * granting them to another role ends up in the database.
     */
    public function hasPermission(PermissionSlug $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return ! $permission->isReservedForSuperAdmin()
            && $this->role->permissions->contains(fn (Permission $granted): bool => $granted->slug === $permission);
    }

    /**
     * Permission slugs granted to the user, used by the frontend to shape navigation.
     *
     * @return list<string>
     */
    public function permissionSlugs(): array
    {
        $permissions = array_filter(PermissionSlug::cases(), $this->hasPermission(...));

        return array_values(array_map(fn (PermissionSlug $permission): string => $permission->value, $permissions));
    }

    /**
     * Two-letter initials for avatars, e.g. "Ana Torres" becomes "AT".
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
