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
        Schema::table('servants', function (Blueprint $table): void {
            $table->unique(['id', 'parish_id'], 'servants_id_parish_unique');
        });

        Schema::create('pastoral_areas', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('parish_id')->constrained('parishes')->restrictOnDelete();
            $table->string('code', 60);
            $table->string('name', 140);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->foreignUuid('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['parish_id', 'code'], 'pastoral_areas_parish_code_unique');
            $table->unique(['id', 'parish_id'], 'pastoral_areas_id_parish_unique');
            $table->index(['parish_id', 'status'], 'pastoral_areas_parish_status_idx');
        });

        DB::statement("ALTER TABLE pastoral_areas ADD CONSTRAINT pastoral_areas_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");

        Schema::create('pastoral_functions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('parish_id');
            $table->uuid('pastoral_area_id');
            $table->string('code', 80);
            $table->string('name', 140);
            $table->string('assignment_mode', 20)->default('PERSON');
            $table->boolean('requires_qualification')->default(true);
            $table->string('status', 20)->default('ACTIVE');
            $table->foreignUuid('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->foreign(['pastoral_area_id', 'parish_id'], 'pastoral_functions_area_parish_foreign')
                ->references(['id', 'parish_id'])
                ->on('pastoral_areas')
                ->restrictOnDelete();
            $table->unique(['pastoral_area_id', 'code'], 'pastoral_functions_area_code_unique');
            $table->unique(['id', 'parish_id'], 'pastoral_functions_id_parish_unique');
            $table->index(['parish_id', 'status'], 'pastoral_functions_parish_status_idx');
        });

        DB::statement("ALTER TABLE pastoral_functions ADD CONSTRAINT pastoral_functions_mode_check CHECK (assignment_mode IN ('PERSON', 'TEAM', 'EITHER'))");
        DB::statement("ALTER TABLE pastoral_functions ADD CONSTRAINT pastoral_functions_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");

        Schema::create('servant_functions', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('parish_id');
            $table->uuid('servant_id');
            $table->uuid('pastoral_function_id');
            $table->string('status', 20)->default('QUALIFIED');
            $table->date('qualified_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->foreignUuid('approved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->foreign(['servant_id', 'parish_id'], 'servant_functions_servant_parish_foreign')
                ->references(['id', 'parish_id'])
                ->on('servants')
                ->restrictOnDelete();
            $table->foreign(['pastoral_function_id', 'parish_id'], 'servant_functions_function_parish_foreign')
                ->references(['id', 'parish_id'])
                ->on('pastoral_functions')
                ->restrictOnDelete();
            $table->unique(['servant_id', 'pastoral_function_id'], 'servant_functions_servant_function_unique');
            $table->index(['parish_id', 'status'], 'servant_functions_parish_status_idx');
        });

        DB::statement("ALTER TABLE servant_functions ADD CONSTRAINT servant_functions_status_check CHECK (status IN ('PENDING', 'QUALIFIED', 'SUSPENDED', 'EXPIRED'))");
        DB::statement('ALTER TABLE servant_functions ADD CONSTRAINT servant_functions_period_check CHECK (expires_on IS NULL OR qualified_on IS NULL OR expires_on >= qualified_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('servant_functions');
        Schema::dropIfExists('pastoral_functions');
        Schema::dropIfExists('pastoral_areas');

        Schema::table('servants', function (Blueprint $table): void {
            $table->dropUnique('servants_id_parish_unique');
        });
    }
};
