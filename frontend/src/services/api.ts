import type { ApiEnvelope, HealthData } from '../types/api'

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api/v1'

export class ApiError extends Error {
  readonly status: number

  constructor(message: string, status: number) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

export async function fetchHealth(signal?: AbortSignal): Promise<HealthData> {
  const response = await fetch(`${apiBaseUrl}/health`, {
    headers: {
      Accept: 'application/json',
    },
    signal,
  })

  if (!response.ok) {
    throw new ApiError('Não foi possível consultar a API.', response.status)
  }

  const payload: ApiEnvelope<HealthData> = await response.json()

  return payload.data
}
