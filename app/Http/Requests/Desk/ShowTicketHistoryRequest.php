<?php

namespace App\Http\Requests\Desk;

use App\Domain\Reporting\Data\ReportPeriod;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Http\Requests\Concerns\ValidatesReportPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowTicketHistoryRequest extends FormRequest
{
    use ValidatesReportPeriod;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->periodRules(),
            'view' => ['nullable', Rule::in(['mine'])],
            'status' => ['nullable', Rule::in([TicketStatus::Resolved->value, TicketStatus::Closed->value])],
            'team' => ['nullable', 'integer', Rule::in($this->user()->teams->modelKeys())],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * The days to look at, or null to look at the whole history.
     */
    public function periodOrNull(): ?ReportPeriod
    {
        return $this->filled('from') || $this->filled('to') ? $this->period() : null;
    }
}
