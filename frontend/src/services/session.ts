import type { AuthSession } from '../types/api'

const sessionKey = 'eclesiapp.auth-session'

function isAuthSession(value: unknown): value is AuthSession {
  if (!value || typeof value !== 'object') {
    return false
  }

  const candidate = value as Partial<AuthSession>

  return (
    typeof candidate.token === 'string' &&
    candidate.tokenType === 'Bearer' &&
    typeof candidate.user?.id === 'string' &&
    typeof candidate.user.name === 'string' &&
    typeof candidate.user.email === 'string'
  )
}

export function restoreSession(): AuthSession | null {
  const serialized = sessionStorage.getItem(sessionKey)

  if (!serialized) {
    return null
  }

  try {
    const session: unknown = JSON.parse(serialized)

    if (isAuthSession(session)) {
      return session
    }
  } catch {
    // Invalid browser data is discarded below.
  }

  clearSession()

  return null
}

export function persistSession(session: AuthSession): void {
  sessionStorage.setItem(sessionKey, JSON.stringify(session))
}

export function clearSession(): void {
  sessionStorage.removeItem(sessionKey)
}
