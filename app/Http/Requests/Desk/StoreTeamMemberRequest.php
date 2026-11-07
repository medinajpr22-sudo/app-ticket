<?php

namespace App\Http\Requests\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Location;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTeamMemberRequest extends FormRequest
{
    /**
     * A lead adds assistants to the teams they lead, never to other teams.
     */
    public function authorize(): bool
    {
        return $this->user()->can('lead', $this->route('team'));
    }

    /**
     * The role and the team are not asked: an assistant is IT staff of the
     * team the lead is adding them to.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'location_id' => ['nullable', 'integer', Rule::exists(Location::class, 'id')],
            'password' => ['nullable', 'string', Password::defaults()],
        ];
    }
}
