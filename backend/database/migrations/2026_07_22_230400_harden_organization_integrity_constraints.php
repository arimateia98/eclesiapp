<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_memberships', function (Blueprint $table): void {
            $table->dropUnique('memberships_organization_person_status_unique');
        });

        Schema::table('organization_relationships', function (Blueprint $table): void {
            $table->dropUnique('organization_relationships_identity_unique');
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX memberships_one_active_per_person
            ON organization_memberships (organization_id, person_id)
            WHERE status = 'active'
            SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX relationships_one_active_per_type
            ON organization_relationships (source_organization_id, target_organization_id, relationship_type)
            WHERE status = 'active'
            SQL);

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE organizations
            ADD CONSTRAINT organizations_parent_must_differ
            CHECK (parent_organization_id IS NULL OR parent_organization_id <> id)
            SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE organization_memberships
            ADD CONSTRAINT memberships_status_dates_are_consistent
            CHECK (
                (status = 'active' AND left_at IS NULL)
                OR
                (status = 'inactive' AND left_at IS NOT NULL AND left_at >= joined_at)
            )
            SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE organization_relationships
            ADD CONSTRAINT relationships_organizations_must_differ
            CHECK (source_organization_id <> target_organization_id)
            SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE organization_relationships
            ADD CONSTRAINT relationships_status_dates_are_consistent
            CHECK (
                (status = 'active' AND ended_at IS NULL)
                OR
                (status = 'inactive' AND ended_at IS NOT NULL AND ended_at >= started_at)
            )
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE organizations DROP CONSTRAINT IF EXISTS organizations_parent_must_differ');
            DB::statement('ALTER TABLE organization_memberships DROP CONSTRAINT IF EXISTS memberships_status_dates_are_consistent');
            DB::statement('ALTER TABLE organization_relationships DROP CONSTRAINT IF EXISTS relationships_organizations_must_differ');
            DB::statement('ALTER TABLE organization_relationships DROP CONSTRAINT IF EXISTS relationships_status_dates_are_consistent');
        }

        DB::statement('DROP INDEX IF EXISTS memberships_one_active_per_person');
        DB::statement('DROP INDEX IF EXISTS relationships_one_active_per_type');

        Schema::table('organization_memberships', function (Blueprint $table): void {
            $table->unique(
                ['organization_id', 'person_id', 'status'],
                'memberships_organization_person_status_unique',
            );
        });

        Schema::table('organization_relationships', function (Blueprint $table): void {
            $table->unique(
                ['source_organization_id', 'target_organization_id', 'relationship_type', 'status'],
                'organization_relationships_identity_unique',
            );
        });
    }
};
