<?php

use App\Shared\Auditing\AuditAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->enum('action', array_column(AuditAction::cases(), 'value'));
            $table->string('entity_type', 100);
            $table->ulid('entity_id');
            $table->json('previous_state')->nullable();
            $table->json('new_state');
            $table->text('justification')->nullable();
            $table->timestampTz('created_at');

            $table->index(['organization_id', 'created_at']);
            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
