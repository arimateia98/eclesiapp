import type {
  ApiEnvelope,
  ApiErrorPayload,
  AcceptAccountInvitationInput,
  AccountInvitation,
  Assignment,
  AddOrganizationMemberInput,
  AuthResponse,
  AuthSession,
  CreateOrganizationInput,
  CreateMinistryTypeInput,
  CreateEventInput,
  CreateEventTypeInput,
  CreateInternalMissionInput,
  CreateAssignmentInput,
  CreateLocationInput,
  CreateServiceFunctionInput,
  HealthData,
  EventType,
  Location,
  Mission,
  LoginInput,
  Organization,
  OrganizationMembership,
  MinistryType,
  PersonFunction,
  RegisterInput,
  ServiceFunction,
  ScheduledEvent,
} from '../types/api'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api/v1'

export class ApiError extends Error {
  readonly status: number
  readonly code: string | null
  readonly validationErrors: Record<string, string[]>

  constructor(
    message: string,
    status: number,
    code: string | null = null,
    validationErrors: Record<string, string[]> = {},
  ) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.code = code
    this.validationErrors = validationErrors
  }
}

async function request<T>(
  path: string,
  options: RequestInit = {},
  token?: string,
): Promise<T> {
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')

  if (options.body) {
    headers.set('Content-Type', 'application/json')
  }

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(`${apiBaseUrl}${path}`, { ...options, headers })

  if (!response.ok) {
    let payload: ApiErrorPayload = {}

    try {
      payload = (await response.json()) as ApiErrorPayload
    } catch {
      // Responses without JSON still receive a safe, user-facing fallback.
    }

    throw new ApiError(
      payload.message || 'Não foi possível concluir a solicitação.',
      response.status,
      payload.code || null,
      payload.errors || {},
    )
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}

export async function fetchHealth(signal?: AbortSignal): Promise<HealthData> {
  const payload = await request<ApiEnvelope<HealthData>>('/health', { signal })

  return payload.data
}

export async function registerAccount(input: RegisterInput): Promise<AuthSession> {
  const payload = await request<ApiEnvelope<AuthResponse>>('/auth/register', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return {
    user: payload.data.user,
    token: payload.data.token,
    tokenType: payload.data.token_type,
  }
}

export async function login(input: LoginInput): Promise<AuthSession> {
  const payload = await request<ApiEnvelope<AuthResponse>>('/auth/login', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return {
    user: payload.data.user,
    token: payload.data.token,
    tokenType: payload.data.token_type,
  }
}

export async function logout(token: string): Promise<void> {
  await request<void>('/auth/token', { method: 'DELETE' }, token)
}

export async function fetchOrganizations(token: string): Promise<Organization[]> {
  const payload = await request<ApiEnvelope<Organization[]>>('/organizations', {}, token)

  return payload.data
}

export async function createOrganization(
  token: string,
  input: CreateOrganizationInput,
): Promise<Organization> {
  const payload = await request<ApiEnvelope<Organization>>(
    '/organizations',
    {
      method: 'POST',
      body: JSON.stringify(input),
    },
    token,
  )

  return payload.data
}

export async function fetchOrganizationMembers(
  token: string,
  organizationId: string,
): Promise<OrganizationMembership[]> {
  const payload = await request<ApiEnvelope<OrganizationMembership[]>>(
    `/organizations/${organizationId}/members`,
    {},
    token,
  )

  return payload.data
}

export async function addOrganizationMember(
  token: string,
  organizationId: string,
  input: AddOrganizationMemberInput,
): Promise<OrganizationMembership> {
  const payload = await request<ApiEnvelope<OrganizationMembership>>(
    `/organizations/${organizationId}/members`,
    {
      method: 'POST',
      body: JSON.stringify(input),
    },
    token,
  )

  return payload.data
}

export async function inviteOrganizationMember(
  token: string,
  organizationId: string,
  personId: string,
): Promise<AccountInvitation> {
  const payload = await request<ApiEnvelope<AccountInvitation>>(
    `/organizations/${organizationId}/members/${personId}/account-invitations`,
    { method: 'POST' },
    token,
  )

  return payload.data
}

export async function acceptAccountInvitation(
  input: AcceptAccountInvitationInput,
): Promise<AuthSession> {
  const payload = await request<ApiEnvelope<AuthResponse>>('/auth/account-invitations/accept', {
    method: 'POST',
    body: JSON.stringify(input),
  })

  return {
    user: payload.data.user,
    token: payload.data.token,
    tokenType: payload.data.token_type,
  }
}

export async function fetchMinistryTypes(
  token: string,
  organizationId: string,
): Promise<MinistryType[]> {
  const payload = await request<ApiEnvelope<MinistryType[]>>(
    `/organizations/${organizationId}/ministry-types?per_page=100`,
    {},
    token,
  )

  return payload.data
}

export async function createMinistryType(
  token: string,
  organizationId: string,
  input: CreateMinistryTypeInput,
): Promise<MinistryType> {
  const payload = await request<ApiEnvelope<MinistryType>>(
    `/organizations/${organizationId}/ministry-types`,
    { method: 'POST', body: JSON.stringify(input) },
    token,
  )

  return payload.data
}

export async function fetchServiceFunctions(
  token: string,
  organizationId: string,
): Promise<ServiceFunction[]> {
  const payload = await request<ApiEnvelope<ServiceFunction[]>>(
    `/organizations/${organizationId}/service-functions?per_page=100`,
    {},
    token,
  )

  return payload.data
}

export async function createServiceFunction(
  token: string,
  organizationId: string,
  input: CreateServiceFunctionInput,
): Promise<ServiceFunction> {
  const payload = await request<ApiEnvelope<ServiceFunction>>(
    `/organizations/${organizationId}/service-functions`,
    { method: 'POST', body: JSON.stringify(input) },
    token,
  )

  return payload.data
}

export async function fetchPersonFunctions(
  token: string,
  organizationId: string,
  personId: string,
): Promise<PersonFunction[]> {
  const payload = await request<ApiEnvelope<PersonFunction[]>>(
    `/organizations/${organizationId}/members/${personId}/functions?per_page=100`,
    {},
    token,
  )

  return payload.data
}

export async function assignPersonFunction(
  token: string,
  organizationId: string,
  personId: string,
  serviceFunctionId: string,
): Promise<PersonFunction> {
  const payload = await request<ApiEnvelope<PersonFunction>>(
    `/organizations/${organizationId}/members/${personId}/functions`,
    {
      method: 'POST',
      body: JSON.stringify({ service_function_id: serviceFunctionId }),
    },
    token,
  )

  return payload.data
}

export async function removePersonFunction(
  token: string,
  organizationId: string,
  personId: string,
  serviceFunctionId: string,
): Promise<void> {
  await request<void>(
    `/organizations/${organizationId}/members/${personId}/functions/${serviceFunctionId}`,
    { method: 'DELETE' },
    token,
  )
}

export async function fetchEventTypes(
  token: string,
  organizationId: string,
): Promise<EventType[]> {
  const payload = await request<ApiEnvelope<EventType[]>>(
    `/organizations/${organizationId}/event-types?per_page=100`,
    {},
    token,
  )

  return payload.data
}

export async function createEventType(
  token: string,
  organizationId: string,
  input: CreateEventTypeInput,
): Promise<EventType> {
  const payload = await request<ApiEnvelope<EventType>>(
    `/organizations/${organizationId}/event-types`,
    { method: 'POST', body: JSON.stringify(input) },
    token,
  )

  return payload.data
}

export async function fetchLocations(
  token: string,
  organizationId: string,
): Promise<Location[]> {
  const payload = await request<ApiEnvelope<Location[]>>(
    `/organizations/${organizationId}/locations?per_page=100`,
    {},
    token,
  )

  return payload.data
}

export async function createLocation(
  token: string,
  organizationId: string,
  input: CreateLocationInput,
): Promise<Location> {
  const payload = await request<ApiEnvelope<Location>>(
    `/organizations/${organizationId}/locations`,
    { method: 'POST', body: JSON.stringify(input) },
    token,
  )

  return payload.data
}

export async function fetchEvents(
  token: string,
  organizationId: string,
): Promise<ScheduledEvent[]> {
  const payload = await request<ApiEnvelope<ScheduledEvent[]>>(
    `/organizations/${organizationId}/events?per_page=100`,
    {},
    token,
  )

  return payload.data
}

export async function createEvent(
  token: string,
  organizationId: string,
  input: CreateEventInput,
): Promise<ScheduledEvent> {
  const payload = await request<ApiEnvelope<ScheduledEvent>>(
    `/organizations/${organizationId}/events`,
    { method: 'POST', body: JSON.stringify(input) },
    token,
  )

  return payload.data
}

export async function fetchInternalMissions(
  token: string,
  organizationId: string,
  eventId: string,
): Promise<Mission[]> {
  const payload = await request<ApiEnvelope<Mission[]>>(
    `/organizations/${organizationId}/events/${eventId}/missions?per_page=100`,
    {},
    token,
  )

  return payload.data
}

export async function createInternalMission(
  token: string,
  organizationId: string,
  eventId: string,
  input: CreateInternalMissionInput,
): Promise<Mission> {
  const payload = await request<ApiEnvelope<Mission>>(
    `/organizations/${organizationId}/events/${eventId}/missions`,
    { method: 'POST', body: JSON.stringify(input) },
    token,
  )

  return payload.data
}

export async function fetchAssignments(
  token: string,
  organizationId: string,
  eventId: string,
  missionId: string,
): Promise<Assignment[]> {
  const payload = await request<ApiEnvelope<Assignment[]>>(
    `/organizations/${organizationId}/events/${eventId}/missions/${missionId}/assignments?per_page=100`,
    {},
    token,
  )

  return payload.data
}

export async function fetchEligibleMembers(
  token: string,
  organizationId: string,
  eventId: string,
  missionId: string,
  slotId: string,
): Promise<OrganizationMembership[]> {
  const payload = await request<ApiEnvelope<OrganizationMembership[]>>(
    `/organizations/${organizationId}/events/${eventId}/missions/${missionId}/slots/${slotId}/eligible-members?per_page=100`,
    {},
    token,
  )

  return payload.data
}

export async function createAssignment(
  token: string,
  organizationId: string,
  eventId: string,
  missionId: string,
  input: CreateAssignmentInput,
): Promise<Assignment> {
  const payload = await request<ApiEnvelope<Assignment>>(
    `/organizations/${organizationId}/events/${eventId}/missions/${missionId}/assignments`,
    { method: 'POST', body: JSON.stringify(input) },
    token,
  )

  return payload.data
}
