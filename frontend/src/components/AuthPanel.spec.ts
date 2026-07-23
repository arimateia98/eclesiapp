import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AuthPanel from './AuthPanel.vue'
import { ApiError } from '../services/api'
import type { AuthSession } from '../types/api'

const authMocks = vi.hoisted(() => ({
  login: vi.fn(),
  registerAccount: vi.fn(),
}))

vi.mock('../services/api', async (importOriginal) => {
  const original = await importOriginal<typeof import('../services/api')>()

  return {
    ...original,
    ...authMocks,
  }
})

const session: AuthSession = {
  user: { id: '01KUSER', name: 'João', email: 'joao@example.test' },
  token: 'token',
  tokenType: 'Bearer',
}

describe('AuthPanel', () => {
  beforeEach(() => {
    authMocks.login.mockResolvedValue(session)
    authMocks.registerAccount.mockResolvedValue(session)
  })

  it('entra com as credenciais e emite a sessão autenticada', async () => {
    const wrapper = mount(AuthPanel)

    await wrapper.get('[data-test="login-tab"]').trigger('click')
    await wrapper.get('input[name="email"]').setValue('joao@example.test')
    await wrapper.get('input[name="password"]').setValue('senha-segura')
    await wrapper.get('[data-test="auth-form"]').trigger('submit')
    await flushPromises()

    expect(authMocks.login).toHaveBeenCalledWith({
      email: 'joao@example.test',
      password: 'senha-segura',
      device_name: 'painel-web',
    })
    expect(wrapper.emitted('authenticated')?.[0]).toEqual([session])
  })

  it('mostra uma mensagem de validação retornada pela API', async () => {
    authMocks.registerAccount.mockRejectedValue(
      new ApiError('Dados inválidos.', 422, 'request.validation_failed', {
        email: ['Este e-mail já está em uso.'],
      }),
    )
    const wrapper = mount(AuthPanel)

    await wrapper.get('input[name="fullName"]').setValue('Maria de Nazaré')
    await wrapper.get('input[name="name"]').setValue('Maria')
    await wrapper.get('input[name="email"]').setValue('maria@example.test')
    await wrapper.get('input[name="password"]').setValue('senha-segura')
    await wrapper.get('input[name="passwordConfirmation"]').setValue('senha-segura')
    await wrapper.get('[data-test="auth-form"]').trigger('submit')
    await flushPromises()

    expect(wrapper.get('[data-test="auth-error"]').text()).toContain('já está em uso')
    expect(wrapper.emitted('authenticated')).toBeUndefined()
  })
})
