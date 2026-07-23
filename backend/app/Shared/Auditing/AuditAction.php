<?php

namespace App\Shared\Auditing;

enum AuditAction: string
{
    case OrganizationCreated = 'organization.created';
    case MembershipGranted = 'organization.membership_granted';
    case RelationshipCreated = 'organization.relationship_created';
    case AccountInvitationCreated = 'identity.account_invitation_created';
    case PersonAccountLinked = 'identity.person_account_linked';
    case MinistryTypeCreated = 'ministries.ministry_type_created';
    case ServiceFunctionCreated = 'ministries.service_function_created';
    case PersonFunctionAssigned = 'ministries.person_function_assigned';
    case PersonFunctionRemoved = 'ministries.person_function_removed';
}
