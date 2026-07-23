import type {
  ApiEnvelope,
  ApiErrorPayload,
  AcceptAccountInvitationInput,
  AccountInvitation,
  AddOrganizationMemberInput,
  AuthResponse,
  AuthSession,
  CreateOrganizationInput,
  HealthData,
  LoginInput,
  Organization,
  OrganizationMembership,
  RegisterInput,
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
