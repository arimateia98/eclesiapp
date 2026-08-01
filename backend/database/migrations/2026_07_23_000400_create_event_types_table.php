<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('active')->default(true);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['organization_id', 'slug'], 'event_types_organization_slug_unique');
            $table->unique(['organization_id', 'id'], 'event_types_organization_id_unique');
            $table->index(['organization_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_types');
    }
};
