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
