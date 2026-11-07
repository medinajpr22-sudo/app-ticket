<?php

namespace App\Http\Requests\Portal;

use App\Http\Requests\Concerns\ValidatesAttachments;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
{
    use ValidatesAttachments;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            ...$this->attachmentRules(),
        ];
    }
}
