<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_functions', function (Blueprint $table): void {
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('person_id')->constrained('people')->cascadeOnDelete();
            $table->ulid('service_function_id');
            $table->foreignUlid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->primary(['person_id', 'service_function_id']);
            $table->foreign(
                ['organization_id', 'service_function_id'],
                'person_functions_organization_function_foreign',
            )->references(['organization_id', 'id'])->on('service_functions')->cascadeOnDelete();
            $table->index(['organization_id', 'person_id']);
            $table->index(['organization_id', 'service_function_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_functions');
    }
};
