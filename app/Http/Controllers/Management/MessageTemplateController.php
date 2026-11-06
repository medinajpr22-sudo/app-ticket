<?php

namespace App\Http\Controllers\Management;

use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Actions\SaveMessageTemplate;
use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Enums\TemplateVariable;
use App\Domain\Ticketing\Models\MessageTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreMessageTemplateRequest;
use App\Http\Requests\Management\UpdateMessageTemplateRequest;
use App\Http\Resources\MessageTemplateResource;
use App\Http\Resources\TeamResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplateController extends Controller
{
    /**
     * Display the automatic messages and the canned responses.
     */
    public function index(): Response
    {
        $templates = MessageTemplate::query()
            ->with(['team', 'author'])
            ->orderByRaw('team_id is not null')
            ->orderBy(Team::query()->select('name')->whereColumn('teams.id', 'message_templates.team_id'))
            ->orderBy('name')
            ->get();

        return Inertia::render('Management/MessageTemplates/Index', [
            'templates' => MessageTemplateResource::collection($templates),
            'purposes' => $this->purposeOptions(),
        ]);
    }

    /**
     * Show the form for creating a template, optionally for a given purpose and team.
     */
    public function create(Request $request): Response
    {
        /** @var array{purpose?: string|null, team?: int|string|null} $preselected */
        $preselected = $request->validate([
            'purpose' => ['nullable', Rule::enum(MessageTemplatePurpose::class)],
            'team' => ['nullable', 'integer', Rule::exists(Team::class, 'id')],
        ]);

        return Inertia::render('Management/MessageTemplates/Create', [
            'preselectedPurpose' => $preselected['purpose'] ?? MessageTemplatePurpose::CannedResponse->value,
            'preselectedTeamId' => isset($preselected['team']) ? (int) $preselected['team'] : null,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created template.
     */
    public function store(StoreMessageTemplateRequest $request, SaveMessageTemplate $saveTemplate): RedirectResponse
    {
        $saveTemplate->handle(new MessageTemplate, $request->validated(), $request->user());

        return to_route('management.message-templates.index')->with('success', __('Message template created.'));
    }

    /**
     * Show the form for editing the template.
     */
    public function edit(MessageTemplate $messageTemplate): Response
    {
        return Inertia::render('Management/MessageTemplates/Edit', [
            'template' => MessageTemplateResource::make($messageTemplate),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the template.
     */
    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $messageTemplate, SaveMessageTemplate $saveTemplate): RedirectResponse
    {
        $saveTemplate->handle($messageTemplate, $request->validated(), $request->user());

        return to_route('management.message-templates.index')->with('success', __('Message template updated.'));
    }

    /**
     * Choices shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'purposes' => $this->purposeOptions(),
            'teams' => TeamResource::collection(Team::query()->orderBy('name')->get()),
            'variables' => array_map(fn (TemplateVariable $variable): array => [
                'name' => $variable->value,
                'token' => $variable->token(),
                'label' => $variable->label(),
                'sample' => $variable->sample(),
                'purposes' => array_map(
                    fn (MessageTemplatePurpose $purpose): string => $purpose->value,
                    array_values(array_filter(MessageTemplatePurpose::cases(), $variable->isAvailableFor(...))),
                ),
            ], TemplateVariable::cases()),
        ];
    }

    /**
     * @return list<array{value: string, label: string, description: string, is_automatic: bool}>
     */
    private function purposeOptions(): array
    {
        return array_map(fn (MessageTemplatePurpose $purpose): array => [
            'value' => $purpose->value,
            'label' => $purpose->label(),
            'description' => $purpose->description(),
            'is_automatic' => $purpose->isAutomatic(),
        ], MessageTemplatePurpose::cases());
    }
}
