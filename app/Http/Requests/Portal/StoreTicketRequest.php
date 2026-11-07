<?php

namespace App\Http\Requests\Portal;

use App\Domain\Catalog\Models\Category;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * People only choose among visible categories of active teams. Critical
     * priority is reserved for IT, who can raise it after reviewing the ticket.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists(Category::class, 'id')->where(fn (Builder $query) => $query
                    ->where('is_visible', true)
                    ->whereIn('team_id', Team::query()->active()->select('id'))),
            ],
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)->except([TicketPriority::Critical])],
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
            'category_id.exists' => __('Choose a category you can request.'),
        ];
    }
}
