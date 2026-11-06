<?php

namespace Database\Seeders;

use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Models\MessageTemplate;
use Illuminate\Database\Seeder;

/**
 * Reference data required in every environment: one global template for each
 * automatic message. Safe to run repeatedly; existing templates are left untouched.
 */
class MessageTemplateSeeder extends Seeder
{
    /**
     * @var array<string, array{string, string}>
     */
    private const DEFAULT_TEMPLATES = [
        'greeting' => [
            'Saludo general',
            '{{greeting}} {{requester.first_name}}, soy {{agent.name}} del equipo {{team.name}}. Ya tomé tu caso #{{ticket.number}} y te escribo apenas tenga novedades.',
        ],
        'acknowledgement' => [
            'Acuse de recibo general',
            'Hola {{requester.first_name}}, recibimos tu solicitud #{{ticket.number}} «{{ticket.subject}}». El equipo {{team.name}} la atenderá pronto.',
        ],
        'auto_close' => [
            'Cierre por falta de respuesta',
            'Hola {{requester.first_name}}, cerramos el ticket #{{ticket.number}} porque no recibimos respuesta. Si todavía necesitas ayuda, respóndenos y lo reabrimos.',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (MessageTemplatePurpose::automatic() as $purpose) {
            $hasGlobalTemplate = MessageTemplate::query()
                ->where('purpose', $purpose)
                ->whereNull('team_id')
                ->exists();

            if ($hasGlobalTemplate) {
                continue;
            }

            [$name, $body] = self::DEFAULT_TEMPLATES[$purpose->value];

            MessageTemplate::query()->create([
                'purpose' => $purpose,
                'name' => $name,
                'body' => $body,
            ]);
        }
    }
}
