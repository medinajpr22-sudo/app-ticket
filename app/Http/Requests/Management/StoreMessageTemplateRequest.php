<?php

namespace App\Http\Requests\Management;

use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Models\MessageTemplate;
use App\Domain\Ticketing\Rules\AvailableTemplateVariables;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMessageTemplateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teamId = $this->integer('team_id') ?: null;

        return [
            'purpose' => ['required', Rule::enum(MessageTemplatePurpose::class)],
            'team_id' => ['nullable', 'integer', Rule::exists(Team::class, 'id')],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique(MessageTemplate::class)
                    ->where(fn (Builder $query) => $teamId ? $query->where('team_id', $teamId) : $query->whereNull('team_id'))
                    ->ignore($this->template()),
            ],
            'body' => ['required', 'string', 'max:2000', new AvailableTemplateVariables($this->purpose())],
            'is_active' => ['required', 'boolean'],
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
            'name.unique' => __('There is already a template with that name for the same teams.'),
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * Automatic messages are sent by the system, so only one of each kind can be
     * active for all teams, and one more per team that wants its own.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $purpose = $this->purpose();

                if ($purpose === null || ! $purpose->isAutomatic() || ! $this->boolean('is_active') || $validator->errors()->has('team_id')) {
                    return;
                }

                $teamId = $this->integer('team_id') ?: null;

                $anotherIsActive = MessageTemplate::query()
                    ->where('purpose', $purpose)
                    ->where('is_active', true)
                    ->when($teamId, fn ($query) => $query->where('team_id', $teamId), fn ($query) => $query->whereNull('team_id'))
                    ->when($this->template(), fn ($query, MessageTemplate $template) => $query->whereKeyNot($template->id))
                    ->exists();

                if ($anotherIsActive) {
                    $scope = $teamId
                        ? __('the :team team', ['team' => Team::query()->whereKey($teamId)->value('name')])
                        : __('all teams');

                    $validator->errors()->add('is_active', __('Another active template of this kind already applies to :scope. Disable it first.', ['scope' => $scope]));
                }
            },
        ];
    }

    /**
     * The template being edited, or null when creating one.
     */
    protected function template(): ?MessageTemplate
    {
        return null;
    }

    private function purpose(): ?MessageTemplatePurpose
    {
        return MessageTemplatePurpose::tryFrom($this->string('purpose')->toString());
    }
}
