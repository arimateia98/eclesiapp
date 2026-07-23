<?php

use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('personal_access_tokens')
            ->whereIn('tokenable_type', ['App\\Models\\User', User::class])
            ->update(['tokenable_type' => 'user']);
    }

    public function down(): void
    {
        DB::table('personal_access_tokens')
            ->where('tokenable_type', 'user')
            ->update(['tokenable_type' => User::class]);
    }
};
