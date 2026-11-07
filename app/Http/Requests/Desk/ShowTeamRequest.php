<?php

namespace App\Http\Requests\Desk;

use App\Http\Requests\Concerns\ValidatesReportPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowTeamRequest extends FormRequest
{
    use ValidatesReportPeriod;

    /**
     * Only the leads of the team see how its members perform.
     */
    public function authorize(): bool
    {
        return $this->user()->can('lead', $this->route('team'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->periodRules();
    }
}
