<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\PastoralOrganization\Models\PastoralFunction;
use App\Modules\PastoralOrganization\Models\Servant;
use App\Modules\PastoralOrganization\Models\ServantFunction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class QualifyServant
{
    /** @param array{status?: string, qualified_on?: string|null, expires_on?: string|null, notes?: string|null} $data */
    public function execute(Servant $servant, PastoralFunction $function, User $actor, array $data): ServantFunction
    {
        return DB::transaction(function () use ($servant, $function, $actor, $data): ServantFunction {
            $lockedServant = Servant::query()->lockForUpdate()->findOrFail($servant->id);
            $lockedFunction = PastoralFunction::query()->lockForUpdate()->findOrFail($function->id);

            if ($lockedServant->parish_id !== $lockedFunction->parish_id) {
                throw ValidationException::withMessages([
                    'pastoral_function_id' => 'A funÃ§Ã£o e o servo devem pertencer Ã  mesma parÃ³quia.',
                ]);
            }

            if ($lockedServant->status !== 'ACTIVE' || $lockedFunction->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'pastoral_function_id' => 'Somente servos e funÃ§Ãµes ativos podem receber habilitaÃ§Ã£o.',
                ]);
            }

            $status = $data['status'] ?? 'QUALIFIED';
            $qualifiedOn = $data['qualified_on'] ?? ($status === 'QUALIFIED' ? now()->toDateString() : null);

            return ServantFunction::query()->updateOrCreate(
                [
                    'servant_id' => $lockedServant->id,
                    'pastoral_function_id' => $lockedFunction->id,
                ],
                [
                    'parish_id' => $lockedServant->parish_id,
                    'status' => $status,
                    'qualified_on' => $qualifiedOn,
                    'expires_on' => $data['expires_on'] ?? null,
                    'approved_by_user_id' => $actor->id,
                    'notes' => $data['notes'] ?? null,
                ],
            );
        });
    }
}
