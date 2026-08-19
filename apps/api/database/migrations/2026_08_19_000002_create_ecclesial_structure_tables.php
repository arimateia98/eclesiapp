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
        Schema::create('dioceses', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 160);
            $table->string('canonical_name', 160)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 80)->nullable();
            $table->char('country_code', 2)->default('BR');
            $table->string('timezone', 64)->default('America/Fortaleza');
            $table->string('status', 20)->default('ACTIVE');
            $table->timestampsTz();

            $table->index(['status', 'name'], 'dioceses_status_name_idx');
        });

        DB::statement("ALTER TABLE dioceses ADD CONSTRAINT dioceses_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");

        Schema::create('parishes', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('diocese_id')->constrained('dioceses')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('patron_name', 160)->nullable();
            $table->string('timezone', 64);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestampsTz();

            $table->unique(['diocese_id', 'name'], 'parishes_diocese_name_unique');
            $table->unique(['id', 'diocese_id'], 'parishes_id_diocese_unique');
            $table->index(['status', 'name'], 'parishes_status_name_idx');
        });

        DB::statement("ALTER TABLE parishes ADD CONSTRAINT parishes_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");

        Schema::create('communities', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('parish_id')->constrained('parishes')->restrictOnDelete();
            $table->string('name', 160);
            $table->boolean('is_parish_seat')->default(false);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestampsTz();

            $table->unique(['parish_id', 'name'], 'communities_parish_name_unique');
            $table->unique(['id', 'parish_id'], 'communities_id_parish_unique');
            $table->index(['parish_id', 'status'], 'communities_parish_status_idx');
        });

        DB::statement("ALTER TABLE communities ADD CONSTRAINT communities_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");

        Schema::create('locations', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('parish_id')->constrained('parishes')->restrictOnDelete();
            $table->uuid('community_id')->nullable();
            $table->string('name', 160);
            $table->string('location_type', 20);
            $table->jsonb('address_json')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->timestampsTz();

            $table->foreign(['community_id', 'parish_id'], 'locations_community_parish_foreign')
                ->references(['id', 'parish_id'])
                ->on('communities')
                ->restrictOnDelete();
            $table->unique(['parish_id', 'name'], 'locations_parish_name_unique');
            $table->index(['parish_id', 'status'], 'locations_parish_status_idx');
        });

        DB::statement("ALTER TABLE locations ADD CONSTRAINT locations_type_check CHECK (location_type IN ('CHURCH', 'CHAPEL', 'HALL', 'HOME', 'OTHER'))");
        DB::statement("ALTER TABLE locations ADD CONSTRAINT locations_status_check CHECK (status IN ('ACTIVE', 'INACTIVE'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('communities');
        Schema::dropIfExists('parishes');
        Schema::dropIfExists('dioceses');
    }
};
