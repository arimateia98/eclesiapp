<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_functions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->ulid('ministry_type_id');
            $table->string('name');
            $table->string('slug');
            $table->boolean('active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->foreign(
                ['organization_id', 'ministry_type_id'],
                'service_functions_organization_type_foreign',
            )->references(['organization_id', 'id'])->on('ministry_types')->cascadeOnDelete();
            $table->unique(
                ['organization_id', 'ministry_type_id', 'slug'],
                'service_functions_organization_type_slug_unique',
            );
            $table->unique(['organization_id', 'id'], 'service_functions_organization_id_unique');
            $table->index(['organization_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_functions');
    }
};
