<?php

namespace App\Http\Requests\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The new assignee must be an active member of the ticket's team.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignee_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id')->where('is_active', true),
                Rule::exists('team_user', 'user_id')->where('team_id', $this->ticket()->team_id),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
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
            'assignee_id.exists' => __('Choose an active member of this team.'),
        ];
    }

    private function ticket(): Ticket
    {
        return $this->route('ticket');
    }
}
