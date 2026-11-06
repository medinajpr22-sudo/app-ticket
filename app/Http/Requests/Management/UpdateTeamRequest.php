<?php

namespace App\Http\Requests\Management;

use App\Domain\Organization\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends StoreTeamRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => ['required', 'string', 'max:100', Rule::unique(Team::class)->ignore($this->route('team'))],
        ];
    }
}
