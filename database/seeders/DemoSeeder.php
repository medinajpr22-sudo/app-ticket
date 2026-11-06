<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Enums\TeamType;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Location;
use App\Domain\Organization\Models\Team;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\Shared\Enums\Icon;
use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\MessageTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Sample organization, teams and people taken from the approved prototype.
 * Only meant for local and demo environments.
 */
class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = collect(['Dirección TIC', 'Finanzas', 'Marketing', 'Talento Humano', 'Operaciones Clínicas'])
            ->mapWithKeys(fn (string $name): array => [$name => Department::query()->create(['name' => $name])]);

        $locations = collect(['Sede Principal', 'Sede Norte', 'Sede Sur'])
            ->mapWithKeys(fn (string $name): array => [$name => Location::factory()->create(['name' => $name])]);

        $teams = collect([
            ['Dialisoft', TeamType::ApplicationSupport, Icon::Pulse, 'Sistema clínico de diálisis'],
            ['Urosoft', TeamType::ApplicationSupport, Icon::Drop, 'Sistema de urología'],
            ['ERP / SAP', TeamType::ApplicationSupport, Icon::Grid, 'Finanzas, compras e inventario'],
            ['IA y Automatización', TeamType::ApplicationSupport, Icon::Spark, 'Asistentes, flujos y automatización'],
            ['Analítica de Datos', TeamType::ApplicationSupport, Icon::Chart, 'Reportes, dashboards y calidad de datos'],
            ['Infraestructura y Hardware', TeamType::GeneralSupport, Icon::Server, 'Equipos, accesos, correo y software'],
            ['Redes y Conectividad', TeamType::GeneralSupport, Icon::Wifi, 'WiFi, VPN, telefonía y enlaces'],
        ])->mapWithKeys(fn (array $team): array => [$team[0] => Team::query()->create([
            'name' => $team[0],
            'type' => $team[1],
            'icon' => $team[2],
            'description' => $team[3],
        ])]);

        $this->seedCatalog($teams);
        $this->seedMessageTemplates($teams);

        $staff = [
            ['Ana Torres', 'ana.torres', 'Analista de soporte', ['Infraestructura y Hardware' => false]],
            ['Luis Pardo', 'luis.pardo', 'Líder de redes', ['Redes y Conectividad' => true, 'Infraestructura y Hardware' => false]],
            ['Sofía Reyes', 'sofia.reyes', 'Analista de aplicaciones clínicas', ['Dialisoft' => false, 'Urosoft' => false]],
            ['Marco Díaz', 'marco.diaz', 'Consultor SAP', ['ERP / SAP' => true]],
            ['Lucía Vega', 'lucia.vega', 'Líder de datos e IA', ['IA y Automatización' => true, 'Analítica de Datos' => true]],
        ];

        foreach ($staff as [$name, $alias, $jobTitle, $memberships]) {
            $member = User::factory()->admin()->create([
                'name' => $name,
                'email' => "{$alias}@example.com",
                'job_title' => $jobTitle,
                'department_id' => $departments['Dirección TIC']->id,
                'location_id' => $locations['Sede Principal']->id,
            ]);

            foreach ($memberships as $teamName => $isLead) {
                $member->teams()->attach($teams[$teamName], ['is_lead' => $isLead]);
            }
        }

        User::factory()->create([
            'name' => 'Carolina Méndez',
            'email' => 'carolina.mendez@example.com',
            'job_title' => 'Coordinadora de marketing',
            'department_id' => $departments['Marketing']->id,
            'location_id' => $locations['Sede Norte']->id,
        ]);

        User::factory()->create([
            'name' => 'Jorge Peña',
            'email' => 'jorge.pena@example.com',
            'job_title' => 'Analista de compras',
            'department_id' => $departments['Finanzas']->id,
            'location_id' => $locations['Sede Principal']->id,
        ]);

        User::factory()->inactive()->create([
            'name' => 'Patricia Rojas',
            'email' => 'patricia.rojas@example.com',
            'job_title' => 'Enfermera jefe',
            'department_id' => $departments['Operaciones Clínicas']->id,
            'location_id' => $locations['Sede Sur']->id,
        ]);
    }

    /**
     * Holidays, a stricter policy for clinical systems and the categories of the prototype.
     *
     * @param  Collection<string, Team>  $teams
     */
    private function seedCatalog(Collection $teams): void
    {
        $calendar = BusinessCalendar::query()->where('is_default', true)->firstOrFail();

        foreach ([['Navidad', '2026-12-25'], ['Año Nuevo', '2027-01-01'], ['Día del Trabajo', '2027-05-01']] as [$name, $date]) {
            $calendar->holidays()->create(['name' => $name, 'date' => $date]);
        }

        $clinicalPolicy = SlaPolicy::query()->create([
            'business_calendar_id' => $calendar->id,
            'name' => 'Sistemas clínicos',
            'description' => 'Incidentes que pueden afectar la atención de pacientes.',
        ]);

        foreach ([
            [TicketPriority::Critical, 15, 120],
            [TicketPriority::High, 30, 240],
            [TicketPriority::Medium, 120, 720],
            [TicketPriority::Low, 240, 1440],
        ] as [$priority, $firstResponse, $resolution]) {
            $clinicalPolicy->targets()->create([
                'priority' => $priority,
                'first_response_minutes' => $firstResponse,
                'resolution_minutes' => $resolution,
            ]);
        }

        $categories = [
            ['Infraestructura y Hardware', 'Equipo y hardware', 'Laptop, monitor, impresora', Icon::Laptop, TicketType::Incident],
            ['Infraestructura y Hardware', 'Accesos y claves', 'Usuarios, permisos, contraseñas', Icon::Key, TicketType::ServiceRequest],
            ['Infraestructura y Hardware', 'Software y licencias', 'Instalar o actualizar aplicaciones', Icon::Puzzle, TicketType::ServiceRequest],
            ['Infraestructura y Hardware', 'Correo y Teams', 'Outlook, calendario, Teams', Icon::Mail, TicketType::Incident],
            ['Infraestructura y Hardware', 'Otro / No sé', 'Te ayudamos a clasificarlo', Icon::Help, TicketType::Incident],
            ['Redes y Conectividad', 'Red, WiFi y VPN', 'Conectividad e internet', Icon::Wifi, TicketType::Incident],
            ['Redes y Conectividad', 'Telefonía y videollamadas', 'Extensiones, softphone, salas', Icon::Video, TicketType::Incident],
            ['Dialisoft', 'Incidente en Dialisoft', 'Errores o caídas del sistema', Icon::Pulse, TicketType::Incident],
            ['Dialisoft', 'Reportes y datos de Dialisoft', 'Reportes clínicos y extracciones', Icon::Chart, TicketType::ServiceRequest],
            ['Urosoft', 'Incidente en Urosoft', 'Errores o caídas del sistema', Icon::Drop, TicketType::Incident],
            ['Urosoft', 'Agenda y citas en Urosoft', 'Agendas, usuarios y plantillas', Icon::Clock, TicketType::ServiceRequest],
            ['ERP / SAP', 'Accesos y roles en SAP', 'Altas, bajas y perfiles', Icon::Key, TicketType::ServiceRequest],
            ['ERP / SAP', 'Errores y procesos en SAP', 'Contabilidad, compras, inventario', Icon::Grid, TicketType::Incident],
            ['IA y Automatización', 'Solicitud de automatización', 'Flujos y tareas repetitivas', Icon::Spark, TicketType::ServiceRequest],
            ['IA y Automatización', 'Asistentes y herramientas de IA', 'Accesos, buenas prácticas, dudas', Icon::Spark, TicketType::ServiceRequest],
            ['Analítica de Datos', 'Nuevo reporte o dashboard', 'Indicadores y tableros', Icon::Chart, TicketType::ServiceRequest],
            ['Analítica de Datos', 'Calidad y acceso a datos', 'Inconsistencias, permisos, extracciones', Icon::Search, TicketType::ServiceRequest],
        ];

        $clinicalIncidents = ['Incidente en Dialisoft', 'Incidente en Urosoft'];

        foreach ($categories as $position => [$teamName, $name, $description, $icon, $type]) {
            Category::query()->create([
                'team_id' => $teams[$teamName]->id,
                'sla_policy_id' => in_array($name, $clinicalIncidents, true) ? $clinicalPolicy->id : null,
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'default_ticket_type' => $type,
                'sort_order' => $position,
            ]);
        }
    }

    /**
     * Canned responses from the prototype and a greeting of its own for a clinical team.
     *
     * @param  Collection<string, Team>  $teams
     */
    private function seedMessageTemplates(Collection $teams): void
    {
        foreach ([
            ['Estamos revisando', 'Hola {{requester.first_name}}, estamos revisando tu caso.'],
            ['Pedir captura del error', '¿Podrías compartir una captura del error que aparece?'],
            ['Confirmar solución', 'Aplicamos una solución. ¿Puedes confirmar si ya funciona?'],
        ] as [$name, $body]) {
            MessageTemplate::query()->create([
                'purpose' => MessageTemplatePurpose::CannedResponse,
                'name' => $name,
                'body' => $body,
            ]);
        }

        MessageTemplate::query()->create([
            'team_id' => $teams['Dialisoft']->id,
            'purpose' => MessageTemplatePurpose::Greeting,
            'name' => 'Saludo de Dialisoft',
            'body' => '{{greeting}} {{requester.first_name}}, soy {{agent.name}} del equipo de Dialisoft. Ya tomé tu caso #{{ticket.number}}. Si hay pacientes afectados, llámanos a la extensión 1010.',
        ]);
    }
}
