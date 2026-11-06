<?php

namespace App\Http\Requests\Management;

use App\Domain\Catalog\Models\Category;
use App\Domain\Organization\Models\Team;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Shared\Enums\Icon;
use App\Domain\Ticketing\Enums\TicketType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', $this->teamRule()],
            'sla_policy_id' => ['nullable', 'integer', Rule::exists(SlaPolicy::class, 'id')],
            'name' => ['required', 'string', 'max:100', $this->uniqueNameRule()],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', Rule::enum(Icon::class)],
            'default_ticket_type' => ['required', Rule::enum(TicketType::class)],
            'is_visible' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
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
            'name.unique' => __('This team already has a category with that name.'),
            'team_id.exists' => __('Choose an active team.'),
        ];
    }

    /**
     * New categories can only go to teams that receive tickets.
     */
    protected function teamRule(): Exists
    {
        return Rule::exists(Team::class, 'id')->where('is_active', true);
    }

    /**
     * Names only need to be unique within the same team.
     */
    protected function uniqueNameRule(): Unique
    {
        return Rule::unique(Category::class)->where('team_id', $this->integer('team_id'));
    }
}
