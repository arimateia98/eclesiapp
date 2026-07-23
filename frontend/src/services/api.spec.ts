import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  acceptAccountInvitation,
  assignPersonFunction,
  fetchOrganizations,
  inviteOrganizationMember,
  login,
  removePersonFunction,
} from './api'

const fetchMock = vi.fn()

describe('serviço da API', () => {
  beforeEach(() => {
    fetchMock.mockReset()
    vi.stubGlobal('fetch', fetchMock)
  })

  it('envia o bearer token somente nas chamadas autenticadas', async () => {
    fetchMock.mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ data: [] }),
    })

    await fetchOrganizations('token-seguro')

    const [, options] = fetchMock.mock.calls[0] as [string, RequestInit]
    expect(new Headers(options.headers).get('Authorization')).toBe('Bearer token-seguro')
  })

  it('preserva código e mensagens de validação em erros previsíveis', async () => {
    fetchMock.mockResolvedValue({
      ok: false,
      status: 422,
      json: async () => ({
        message: 'Os dados informados são inválidos.',
        code: 'request.validation_failed',
        errors: { email: ['O e-mail é obrigatório.'] },
      }),
    })

    const promise = login({ email: '', password: '', device_name: 'painel-web' })

    await expect(promise).rejects.toMatchObject({
      status: 422,
      code: 'request.validation_failed',
      validationErrors: { email: ['O e-mail é obrigatório.'] },
    })
  })

  it('envia convites autenticados e aceita o token sem bearer anterior', async () => {
    fetchMock
      .mockResolvedValueOnce({
        ok: true,
        status: 201,
        json: async () => ({ data: { id: '01KINVITE' } }),
      })
      .mockResolvedValueOnce({
        ok: true,
        status: 200,
        json: async () => ({
          data: {
            user: { id: '01KUSER', name: 'José', email: 'jose@example.test' },
            token: 'novo-token',
            token_type: 'Bearer',
          },
        }),
      })

    await inviteOrganizationMember('token-coordenador', '01KORG', '01KPERSON')
    await acceptAccountInvitation({
      token: 'token-convite',
      name: 'José',
      password: 'senha-segura',
      password_confirmation: 'senha-segura',
      device_name: 'painel-web',
    })

    const [inviteUrl, inviteOptions] = fetchMock.mock.calls[0] as [string, RequestInit]
    const [acceptUrl, acceptOptions] = fetchMock.mock.calls[1] as [string, RequestInit]
    expect(inviteUrl).toContain('/organizations/01KORG/members/01KPERSON/account-invitations')
    expect(new Headers(inviteOptions.headers).get('Authorization')).toBe('Bearer token-coordenador')
    expect(acceptUrl).toContain('/auth/account-invitations/accept')
    expect(new Headers(acceptOptions.headers).has('Authorization')).toBe(false)
  })

  it('atribui e remove funções usando o escopo completo da organização e da pessoa', async () => {
    fetchMock
      .mockResolvedValueOnce({
        ok: true,
        status: 201,
        json: async () => ({ data: { service_function_id: '01KFUNCTION' } }),
      })
      .mockResolvedValueOnce({ ok: true, status: 204 })

    await assignPersonFunction('token', '01KORG', '01KPERSON', '01KFUNCTION')
    await removePersonFunction('token', '01KORG', '01KPERSON', '01KFUNCTION')

    const [assignUrl, assignOptions] = fetchMock.mock.calls[0] as [string, RequestInit]
    const [removeUrl, removeOptions] = fetchMock.mock.calls[1] as [string, RequestInit]
    expect(assignUrl).toContain('/organizations/01KORG/members/01KPERSON/functions')
    expect(assignOptions.method).toBe('POST')
    expect(removeUrl).toContain('/members/01KPERSON/functions/01KFUNCTION')
    expect(removeOptions.method).toBe('DELETE')
  })
})
