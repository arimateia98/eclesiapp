<?php

namespace Database\Seeders;

use App\Modules\Identity\Domain\Enums\PersonStatus;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Domain\Models\MinistryType;
use App\Modules\Ministries\Domain\Models\PersonFunction;
use App\Modules\Ministries\Domain\Models\ServiceFunction;
use App\Modules\Missions\Domain\Enums\MissionParticipationPolicy;
use App\Modules\Missions\Domain\Enums\MissionSlotType;
use App\Modules\Missions\Domain\Enums\MissionStatus;
use App\Modules\Missions\Domain\Enums\MissionVisibility;
use App\Modules\Missions\Domain\Models\Mission;
use App\Modules\Missions\Domain\Models\MissionSlot;
use App\Modules\Organizations\Domain\Enums\MembershipRole;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Enums\OrganizationStatus;
use App\Modules\Organizations\Domain\Enums\OrganizationType;
use App\Modules\Organizations\Domain\Enums\OrganizationVisibility;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;
use App\Modules\Scheduling\Domain\Enums\AssignmentStatus;
use App\Modules\Scheduling\Domain\Enums\EventStatus;
use App\Modules\Scheduling\Domain\Enums\EventVisibility;
use App\Modules\Scheduling\Domain\Models\Assignment;
use App\Modules\Scheduling\Domain\Models\Event;
use App\Modules\Scheduling\Domain\Models\EventType;
use App\Modules\Scheduling\Domain\Models\Unavailability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DemoSeeder ignorado fora do ambiente local.');

            return;
        }

        DB::transaction(function (): void {
            $coordinator = $this->user('Coordenador Demo', 'coordenador@eclesiapp.local');
            $servant = $this->user('Servo Demo', 'servo@eclesiapp.local');
            $coordinatorPerson = $this->person($coordinator, 'Coordenador Demo');
            $servantPerson = $this->person($servant, 'Servo Demo');

            $organization = Organization::query()->updateOrCreate(
                ['slug' => 'comunidade-demo'],
                [
                    'name' => 'Comunidade Demonstração',
                    'type' => OrganizationType::Community,
                    'status' => OrganizationStatus::Active,
                    'visibility' => OrganizationVisibility::Private,
                    'timezone' => 'America/Fortaleza',
                    'created_by' => $coordinator->getKey(),
                ],
            );
            $this->membership($organization, $coordinatorPerson, MembershipRole::Owner);
            $this->membership($organization, $servantPerson, MembershipRole::Member);

            $ministry = MinistryType::query()->updateOrCreate(
                ['organization_id' => $organization->getKey(), 'name' => 'Liturgia'],
                [
                    'slug' => 'liturgia',
                    'description' => 'Catálogo local de demonstração.',
                    'active' => true,
                    'created_by' => $coordinator->getKey(),
                ],
            );
            $function = ServiceFunction::query()->updateOrCreate(
                ['organization_id' => $organization->getKey(), 'name' => 'Leitor'],
                [
                    'ministry_type_id' => $ministry->getKey(),
                    'slug' => 'leitor',
                    'active' => true,
                    'created_by' => $coordinator->getKey(),
                ],
            );
            PersonFunction::query()->firstOrCreate([
                'organization_id' => $organization->getKey(),
                'person_id' => $servantPerson->getKey(),
                'service_function_id' => $function->getKey(),
            ], ['assigned_by' => $coordinator->getKey()]);

            $eventType = EventType::query()->updateOrCreate(
                ['organization_id' => $organization->getKey(), 'name' => 'Missa'],
                ['slug' => 'missa', 'active' => true, 'created_by' => $coordinator->getKey()],
            );
            $startsAt = CarbonImmutable::now('America/Fortaleza')->next('Sunday')->setTime(19, 0)->utc();
            $event = Event::query()->updateOrCreate(
                ['publisher_organization_id' => $organization->getKey(), 'title' => 'Missa de demonstração'],
                [
                    'host_organization_id' => $organization->getKey(),
                    'event_type_id' => $eventType->getKey(),
                    'location_id' => null,
                    'description' => 'Evento local criado pelo DemoSeeder.',
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->addHour(),
                    'visibility' => EventVisibility::Private,
                    'status' => EventStatus::Published,
                    'created_by' => $coordinator->getKey(),
                ],
            );
            $mission = Mission::query()->updateOrCreate(
                ['event_id' => $event->getKey(), 'title' => 'Proclamação da Palavra'],
                [
                    'publisher_organization_id' => $organization->getKey(),
                    'target_organization_id' => $organization->getKey(),
                    'ministry_type_id' => $ministry->getKey(),
                    'description' => 'Missão local de demonstração.',
                    'visibility' => MissionVisibility::Private,
                    'participation_policy' => MissionParticipationPolicy::CoordinatorAssignment,
                    'status' => MissionStatus::Filled,
                    'created_by' => $coordinator->getKey(),
                ],
            );
            $slot = MissionSlot::query()->updateOrCreate(
                ['mission_id' => $mission->getKey(), 'service_function_id' => $function->getKey()],
                [
                    'organization_id' => $organization->getKey(),
                    'slot_type' => MissionSlotType::Person,
                    'quantity' => 1,
                    'required' => true,
                ],
            );
            Assignment::query()->updateOrCreate(
                ['mission_id' => $mission->getKey(), 'person_id' => $servantPerson->getKey()],
                [
                    'organization_id' => $organization->getKey(),
                    'mission_slot_id' => $slot->getKey(),
                    'assigned_by' => $coordinator->getKey(),
                    'status' => AssignmentStatus::Confirmed,
                    'assigned_at' => now(),
                    'confirmed_at' => now(),
                ],
            );
            Unavailability::query()->updateOrCreate(
                ['person_id' => $servantPerson->getKey(), 'starts_at' => $startsAt->subDay()->setTime(18, 0)],
                ['ends_at' => $startsAt->subDay()->setTime(20, 0)],
            );
        });

        $this->command?->info('Ambiente demo pronto: coordenador@eclesiapp.local e servo@eclesiapp.local.');
    }

    private function user(string $name, string $email): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('Eclesiapp123!')],
        );
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function person(User $user, string $name): Person
    {
        return Person::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['full_name' => $name, 'email' => $user->email, 'status' => PersonStatus::Active],
        );
    }

    private function membership(Organization $organization, Person $person, MembershipRole $role): void
    {
        OrganizationMembership::query()->updateOrCreate(
            ['organization_id' => $organization->getKey(), 'person_id' => $person->getKey()],
            ['role' => $role, 'status' => MembershipStatus::Active, 'joined_at' => now(), 'left_at' => null],
        );
    }
}
