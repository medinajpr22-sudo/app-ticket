<?php

namespace Database\Seeders;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Enums\TeamType;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Location;
use App\Domain\Organization\Models\Team;
use App\Domain\Shared\Enums\Icon;
use Illuminate\Database\Seeder;

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
}
