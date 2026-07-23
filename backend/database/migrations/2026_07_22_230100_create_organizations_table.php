<?php

use App\Modules\Organizations\Domain\Enums\OrganizationStatus;
use App\Modules\Organizations\Domain\Enums\OrganizationType;
use App\Modules\Organizations\Domain\Enums\OrganizationVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', array_column(OrganizationType::cases(), 'value'));
            $table->ulid('parent_organization_id')->nullable();
            $table->enum('status', array_column(OrganizationStatus::cases(), 'value'))->default(OrganizationStatus::Active->value);
            $table->enum('visibility', array_column(OrganizationVisibility::cases(), 'value'))->default(OrganizationVisibility::Private->value);
            $table->string('timezone', 64)->default('UTC');
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['type', 'status']);
            $table->index(['parent_organization_id', 'status']);
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->foreign('parent_organization_id')
                ->references('id')
                ->on('organizations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
