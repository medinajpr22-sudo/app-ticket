<?php

namespace App\Http\Requests\Management;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Enums\RoleSlug;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRolePermissionsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Only delegable roles can be sent; the super admin always has everything.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $delegableRoles = array_map(fn (RoleSlug $role): string => $role->value, RoleSlug::delegable());

        return [
            'permissions' => ['required', 'array:'.implode(',', $delegableRoles)],
            'permissions.*' => ['present', 'array'],
            'permissions.*.*' => ['string', Rule::enum(PermissionSlug::class)],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $requested = collect($this->input('permissions', []))->flatten()->filter(fn (mixed $slug): bool => is_string($slug));

                $reserved = $requested
                    ->map(fn (string $slug): ?PermissionSlug => PermissionSlug::tryFrom($slug))
                    ->filter(fn (?PermissionSlug $permission): bool => $permission?->isReservedForSuperAdmin() ?? false);

                if ($reserved->isNotEmpty()) {
                    $validator->errors()->add('permissions', __('Only the super admin can have these permissions: :permissions.', [
                        'permissions' => $reserved->unique()->map(fn (PermissionSlug $permission): string => $permission->label())->implode(', '),
                    ]));
                }
            },
        ];
    }
}
