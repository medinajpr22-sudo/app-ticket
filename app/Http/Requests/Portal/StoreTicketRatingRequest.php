<?php

namespace App\Http\Requests\Portal;

use App\Domain\Ticketing\Models\TicketRating;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRatingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'between:'.TicketRating::MIN_SCORE.','.TicketRating::MAX_SCORE],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
