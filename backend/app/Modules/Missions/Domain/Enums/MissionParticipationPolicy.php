<?php

namespace App\Modules\Missions\Domain\Enums;

enum MissionParticipationPolicy: string
{
    case InvitationOnly = 'invitation_only';
    case ApplicationRequired = 'application_required';
    case AutomaticAcceptance = 'automatic_acceptance';
    case CoordinatorAssignment = 'coordinator_assignment';
}
