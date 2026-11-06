<?php

namespace App\Http\Requests\Management;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Location;
use App\Domain\Organization\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', Rule::exists(Role::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'department_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'location_id' => ['nullable', 'integer', Rule::exists(Location::class, 'id')],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', Password::defaults()],
            'team_ids' => ['array'],
            'team_ids.*' => ['integer', 'distinct', Rule::exists(Team::class, 'id')],
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
                if ($validator->errors()->has('role_id') || $this->input('team_ids', []) === []) {
                    return;
                }

                if (! Role::query()->findOrFail($this->integer('role_id'))->slug->isStaff()) {
                    $validator->errors()->add('team_ids', __('Only IT staff can belong to a team.'));
                }
            },
        ];
    }
}
