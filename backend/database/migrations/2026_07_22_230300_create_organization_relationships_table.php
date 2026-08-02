<?php

use App\Modules\Organizations\Domain\Enums\OrganizationRelationshipStatus;
use App\Modules\Organizations\Domain\Enums\OrganizationRelationshipType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_relationships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('source_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('target_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->enum('relationship_type', array_column(OrganizationRelationshipType::cases(), 'value'));
            $table->enum('status', array_column(OrganizationRelationshipStatus::cases(), 'value'))->default(OrganizationRelationshipStatus::Active->value);
            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(
                ['source_organization_id', 'target_organization_id', 'relationship_type', 'status'],
                'organization_relationships_identity_unique',
            );
            $table->index(['target_organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_relationships');
    }
};
