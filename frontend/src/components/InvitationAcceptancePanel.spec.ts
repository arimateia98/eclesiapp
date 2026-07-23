import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import InvitationAcceptancePanel from './InvitationAcceptancePanel.vue'
import { ApiError } from '../services/api'
import type { AuthSession } from '../types/api'

const invitationMocks = vi.hoisted(() => ({
  acceptAccountInvitation: vi.fn(),
}))

vi.mock('../services/api', async (importOriginal) => {
  const original = await importOriginal<typeof import('../services/api')>()

  return {
    ...original,
    ...invitationMocks,
  }
})

const session: AuthSession = {
  user: { id: '01KUSER', name: 'José', email: 'jose@example.test' },
  token: 'token',
  tokenType: 'Bearer',
}

describe('InvitationAcceptancePanel', () => {
  beforeEach(() => {
    invitationMocks.acceptAccountInvitation.mockReset()
    invitationMocks.acceptAccountInvitation.mockResolvedValue(session)
  })

  it('aceita o convite uma única vez e entrega a sessão autenticada', async () => {
    const wrapper = mount(InvitationAcceptancePanel, { props: { token: 'convite-seguro' } })

    await wrapper.get('input[name="name"]').setValue('José')
    await wrapper.get('input[name="password"]').setValue('senha-segura')
    await wrapper.get('input[name="passwordConfirmation"]').setValue('senha-segura')
    await wrapper.get('[data-test="invitation-form"]').trigger('submit')
    await flushPromises()

    expect(invitationMocks.acceptAccountInvitation).toHaveBeenCalledWith({
      token: 'convite-seguro',
      name: 'José',
      password: 'senha-segura',
      password_confirmation: 'senha-segura',
      device_name: 'painel-web',
    })
    expect(wrapper.emitted('authenticated')?.[0]).toEqual([session])
  })

  it('explica quando o convite não pode mais ser usado', async () => {
    invitationMocks.acceptAccountInvitation.mockRejectedValue(
      new ApiError('Este convite expirou.', 422, 'identity.invitation_expired'),
    )
    const wrapper = mount(InvitationAcceptancePanel, { props: { token: 'expirado' } })

    await wrapper.get('input[name="name"]').setValue('José')
    await wrapper.get('input[name="password"]').setValue('senha-segura')
    await wrapper.get('input[name="passwordConfirmation"]').setValue('senha-segura')
    await wrapper.get('[data-test="invitation-form"]').trigger('submit')
    await flushPromises()

    expect(wrapper.get('[data-test="invitation-error"]').text()).toContain('expirou')
    expect(wrapper.emitted('authenticated')).toBeUndefined()
  })
})
