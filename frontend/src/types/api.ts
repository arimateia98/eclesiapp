export interface ApiEnvelope<T> {
  data: T
}

export interface ApiErrorPayload {
  message?: string
  code?: string
  errors?: Record<string, string[]>
}

export interface HealthData {
  service: string
  status: 'ok'
  timestamp: string
}

export interface AuthUser {
  id: string
  name: string
  email: string
}

export interface AuthSession {
  user: AuthUser
  token: string
  tokenType: 'Bearer'
}

export interface AuthResponse {
  user: AuthUser
  token: string
  token_type: 'Bearer'
}

export interface RegisterInput {
  name: string
  email: string
  password: string
  password_confirmation: string
  full_name: string
  preferred_name?: string
  device_name: string
}

export interface LoginInput {
  email: string
  password: string
  device_name: string
}

export type OrganizationType =
  | 'diocese'
  | 'parish'
  | 'community'
  | 'chapel'
  | 'ministry'
  | 'movement'
  | 'group'

export type OrganizationVisibility = 'public' | 'private' | 'unlisted'

export interface Organization {
  id: string
  name: string
  slug: string
  type: OrganizationType
  parent_organization_id: string | null
  status: 'active' | 'inactive'
  visibility: OrganizationVisibility
  timezone: string
  current_user_role: MembershipRole | null
  created_at: string
}

export interface CreateOrganizationInput {
  name: string
  slug: string
  type: OrganizationType
  visibility: OrganizationVisibility
  timezone: string
}

export type MembershipRole = 'owner' | 'administrator' | 'coordinator' | 'member' | 'guest'

export interface Person {
  id: string
  full_name: string
  preferred_name: string | null
  email: string | null
  phone: string | null
  status: 'active' | 'inactive'
  has_user: boolean
  created_at: string
}

export interface OrganizationMembership {
  id: string
  organization_id: string
  role: MembershipRole
  status: 'active' | 'inactive'
  joined_at: string
  person: Person
}

export interface AddOrganizationMemberInput {
  full_name: string
  preferred_name?: string
  email?: string
  phone?: string
  role: Exclude<MembershipRole, 'owner'>
}

export interface AccountInvitation {
  id: string
  person_id: string
  status: 'pending' | 'accepted' | 'revoked' | 'expired'
  expires_at: string
}

export interface AcceptAccountInvitationInput {
  token: string
  name: string
  password: string
  password_confirmation: string
  device_name: string
}

export interface MinistryType {
  id: string
  organization_id: string
  name: string
  slug: string
  description: string | null
  active: boolean
  created_at: string
}

export interface CreateMinistryTypeInput {
  name: string
  description?: string
}

export interface ServiceFunction {
  id: string
  organization_id: string
  ministry_type_id: string
  name: string
  slug: string
  active: boolean
  ministry_type: MinistryType
  created_at: string
}

export interface CreateServiceFunctionInput {
  ministry_type_id: string
  name: string
}

export interface PersonFunction {
  organization_id: string
  person_id: string
  service_function_id: string
  service_function: ServiceFunction
  assigned_at: string
}

export interface EventType {
  id: string
  organization_id: string
  name: string
  slug: string
  active: boolean
  created_at: string
}

export interface CreateEventTypeInput {
  name: string
}

export interface Location {
  id: string
  organization_id: string
  name: string
  slug: string
  address_line: string | null
  city: string | null
  timezone: string
  active: boolean
  created_at: string
}

export interface CreateLocationInput {
  name: string
  address_line?: string
  city?: string
  timezone: string
}

export type EventVisibility = 'public' | 'restricted' | 'private' | 'unlisted'
export type EventStatus = 'draft' | 'published' | 'cancelled' | 'completed'

export interface ScheduledEvent {
  id: string
  publisher_organization_id: string
  host_organization_id: string
  event_type_id: string
  event_type: EventType
  location_id: string | null
  location: Location | null
  title: string
  description: string | null
  starts_at: string
  ends_at: string
  visibility: EventVisibility
  status: EventStatus
  missions?: Mission[]
  created_at: string
}

export interface CreateEventInput {
  event_type_id: string
  location_id?: string
  title: string
  description?: string
  starts_at: string
  ends_at: string
}

export interface MissionSlot {
  id: string
  mission_id: string
  slot_type: 'person' | 'organization'
  service_function_id: string | null
  service_function: ServiceFunction | null
  quantity: number
  required: boolean
  created_at: string
}

export interface Mission {
  id: string
  event_id: string
  publisher_organization_id: string
  target_organization_id: string
  ministry_type_id: string
  ministry_type: MinistryType
  title: string
  description: string | null
  visibility: 'public' | 'restricted' | 'private' | 'unlisted'
  participation_policy:
    | 'invitation_only'
    | 'application_required'
    | 'automatic_acceptance'
    | 'coordinator_assignment'
  status: 'draft' | 'open' | 'filled' | 'cancelled' | 'completed'
  response_deadline: string | null
  slots: MissionSlot[]
  created_at: string
}

export interface CreateInternalMissionInput {
  ministry_type_id: string
  title: string
  description?: string
  slots: Array<{
    service_function_id: string
    quantity: number
    required?: boolean
  }>
}
