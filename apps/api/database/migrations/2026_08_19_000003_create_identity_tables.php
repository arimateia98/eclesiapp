<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('full_name', 180);
            $table->string('preferred_name', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index('full_name', 'people_full_name_idx');
        });

        DB::statement('ALTER TABLE people ALTER COLUMN email TYPE citext');

        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('person_id')->unique()->constrained('people')->restrictOnDelete();
            $table->string('login_email')->unique();
            $table->text('password_hash')->nullable();
            $table->string('auth_provider', 40)->default('LOCAL');
            $table->string('status', 20)->default('INVITED');
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE users ALTER COLUMN login_email TYPE citext');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('INVITED', 'ACTIVE', 'BLOCKED', 'DISABLED'))");

        Schema::create('parish_user_memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('parish_id')->constrained('parishes')->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('INVITED');
            $table->timestampTz('joined_at')->nullable();
            $table->timestampTz('ended_at')->nullable();
            $table->timestampsTz();

            $table->unique(['parish_id', 'user_id'], 'parish_user_memberships_parish_user_unique');
            $table->index(['parish_id', 'status'], 'parish_user_memberships_parish_status_idx');
        });

        DB::statement("ALTER TABLE parish_user_memberships ADD CONSTRAINT parish_user_memberships_status_check CHECK (status IN ('INVITED', 'ACTIVE', 'SUSPENDED', 'ENDED'))");
        DB::statement('ALTER TABLE parish_user_memberships ADD CONSTRAINT parish_user_memberships_period_check CHECK (ended_at IS NULL OR joined_at IS NULL OR ended_at >= joined_at)');

        Schema::create('role_catalog', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->timestampsTz();
        });

        Schema::create('parish_user_roles', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('parish_id');
            $table->uuid('user_id');
            $table->foreignUuid('role_id')->constrained('role_catalog')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->foreignUuid('granted_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->foreign(['parish_id', 'user_id'], 'parish_user_roles_membership_foreign')
                ->references(['parish_id', 'user_id'])
                ->on('parish_user_memberships')
                ->restrictOnDelete();
            $table->unique(['parish_id', 'user_id', 'role_id', 'starts_on'], 'parish_user_roles_grant_unique');
            $table->index(['parish_id', 'starts_on', 'ends_on'], 'parish_user_roles_parish_period_idx');
        });

        DB::statement('ALTER TABLE parish_user_roles ADD CONSTRAINT parish_user_roles_period_check CHECK (ends_on IS NULL OR ends_on >= starts_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('parish_user_roles');
        Schema::dropIfExists('role_catalog');
        Schema::dropIfExists('parish_user_memberships');
        Schema::dropIfExists('users');
        Schema::dropIfExists('people');
    }
};
