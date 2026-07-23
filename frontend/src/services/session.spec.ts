import { beforeEach, describe, expect, it } from 'vitest'
import { clearSession, persistSession, restoreSession } from './session'

describe('sessão do painel', () => {
  beforeEach(() => sessionStorage.clear())

  it('restaura apenas uma sessão com contrato válido', () => {
    const session = {
      user: { id: '01KUSER', name: 'Maria', email: 'maria@example.test' },
      token: 'token',
      tokenType: 'Bearer' as const,
    }

    persistSession(session)

    expect(restoreSession()).toEqual(session)
  })

  it('descarta dados corrompidos do navegador', () => {
    sessionStorage.setItem('eclesiapp.auth-session', '{inválido')

    expect(restoreSession()).toBeNull()
    expect(sessionStorage.length).toBe(0)

    clearSession()
  })
})
