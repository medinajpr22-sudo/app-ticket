<?php

namespace Tests\Feature\Database\Seeders;

use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Models\MessageTemplate;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplateSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_one_general_template_for_each_automatic_message(): void
    {
        $this->seed(MessageTemplateSeeder::class);

        foreach (MessageTemplatePurpose::automatic() as $purpose) {
            $this->assertSame(1, MessageTemplate::query()->where('purpose', $purpose)->whereNull('team_id')->where('is_active', true)->count());
        }

        $this->assertDatabaseMissing('message_templates', ['purpose' => MessageTemplatePurpose::CannedResponse->value]);
    }

    public function test_running_again_keeps_templates_edited_afterwards(): void
    {
        $this->seed(MessageTemplateSeeder::class);
        MessageTemplate::query()->where('purpose', MessageTemplatePurpose::Greeting)->update(['body' => 'Hola {{requester.first_name}}']);

        $this->seed(MessageTemplateSeeder::class);

        $this->assertDatabaseCount('message_templates', count(MessageTemplatePurpose::automatic()));
        $this->assertDatabaseHas('message_templates', ['body' => 'Hola {{requester.first_name}}']);
    }
}
