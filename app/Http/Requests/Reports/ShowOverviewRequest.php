<?php

namespace App\Http\Requests\Reports;

use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Team;
use App\Domain\Reporting\Data\TicketFilters;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Http\Requests\Concerns\ValidatesReportPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowOverviewRequest extends FormRequest
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
            'department' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'team' => ['nullable', 'integer', Rule::exists(Team::class, 'id')],
            'status' => ['nullable', Rule::in(['open', ...array_map(fn (TicketStatus $status): string => $status->value, TicketStatus::current())])],
        ];
    }

    public function filters(): TicketFilters
    {
        $status = $this->validated('status');

        return new TicketFilters(
            period: $this->period(),
            teamId: $this->filled('team') ? $this->integer('team') : null,
            departmentId: $this->filled('department') ? $this->integer('department') : null,
            status: $status === null || $status === 'open' ? $status : TicketStatus::from($status),
        );
    }
}
