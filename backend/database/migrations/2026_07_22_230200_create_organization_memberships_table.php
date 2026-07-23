<?php

use App\Modules\Organizations\Domain\Enums\MembershipRole;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_memberships', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('person_id')->constrained('people')->restrictOnDelete();
            $table->enum('role', array_column(MembershipRole::cases(), 'value'));
            $table->enum('status', array_column(MembershipStatus::cases(), 'value'))->default(MembershipStatus::Active->value);
            $table->timestampTz('joined_at');
            $table->timestampTz('left_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['organization_id', 'person_id', 'status'], 'memberships_organization_person_status_unique');
            $table->index(['person_id', 'status']);
            $table->index(['organization_id', 'role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_memberships');
    }
};
