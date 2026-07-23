<?php

namespace App\Modules\Organizations\Domain\Enums;

enum OrganizationRelationshipType: string
{
    case BelongsTo = 'belongs_to';
    case PartnerOf = 'partner_of';
    case ServesAt = 'serves_at';
    case AuthorizedBy = 'authorized_by';
    case LinkedTo = 'linked_to';
}
