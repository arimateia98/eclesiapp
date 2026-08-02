<?php

use App\Modules\Identity\Domain\Enums\AccountInvitationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_account_invitations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('person_id')->constrained('people')->restrictOnDelete();
            $table->foreignUlid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUlid('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->enum('status', array_column(AccountInvitationStatus::cases(), 'value'))
                ->default(AccountInvitationStatus::Pending->value);
            $table->timestampTz('expires_at');
            $table->foreignUlid('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampsTz();

            $table->index(['organization_id', 'status']);
            $table->index(['email', 'status']);
            $table->index(['status', 'expires_at']);
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX person_account_invitations_one_pending
            ON person_account_invitations (person_id)
            WHERE status = 'pending'
            SQL);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE person_account_invitations
                ADD CONSTRAINT person_account_invitations_acceptance_consistent
                CHECK (
                    (status = 'accepted' AND accepted_by_user_id IS NOT NULL AND accepted_at IS NOT NULL)
                    OR
                    (status <> 'accepted' AND accepted_at IS NULL)
                )
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('person_account_invitations');
    }
};
