<?php

namespace App\Http\Requests\Management;

use App\Domain\Identity\Enums\RoleSlug;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Enums\TeamType;
use App\Domain\Organization\Models\Team;
use App\Domain\Shared\Enums\Icon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $staffRoleIds = Role::query()->whereIn('slug', RoleSlug::staff())->pluck('id');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Team::class)],
            'type' => ['required', Rule::enum(TeamType::class)],
            'icon' => ['required', Rule::enum(Icon::class)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'members' => ['present', 'array'],
            'members.*.user_id' => ['required', 'integer', 'distinct', Rule::exists(User::class, 'id')->whereIn('role_id', $staffRoleIds)],
            'members.*.is_lead' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'members.*.user_id.exists' => __('Only IT staff can belong to a team.'),
        ];
    }
}
