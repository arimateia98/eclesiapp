import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import OrganizationWorkspace from './OrganizationWorkspace.vue'
import type { AuthSession, Organization, OrganizationMembership } from '../types/api'

const session: AuthSession = {
  user: { id: '01KUSER', name: 'Maria', email: 'maria@example.test' },
  token: 'token',
  tokenType: 'Bearer',
}

const organization: Organization = {
  id: '01KORG',
  name: 'Comunidade São José',
  slug: 'comunidade-sao-jose',
  type: 'community',
  parent_organization_id: null,
  status: 'active',
  visibility: 'private',
  timezone: 'America/Fortaleza',
  created_at: '2026-07-22T12:00:00Z',
}

const membership: OrganizationMembership = {
  id: '01KMEMBER',
  organization_id: organization.id,
  role: 'member',
  status: 'active',
  joined_at: '2026-07-22T12:00:00Z',
  person: {
    id: '01KPERSON',
    full_name: 'José da Silva',
    preferred_name: 'José',
    email: 'jose@example.test',
    phone: null,
    status: 'active',
    has_user: false,
    created_at: '2026-07-22T12:00:00Z',
  },
}

describe('OrganizationWorkspace', () => {
  it('cadastra pessoas separadas de usuários e permite convidá-las depois', async () => {
    const wrapper = mount(OrganizationWorkspace, {
      props: {
        session,
        organization,
        members: [membership],
        loading: false,
        adding: false,
        invitingPersonId: null,
        error: null,
        notice: null,
      },
    })

    await wrapper.get('[data-test="open-add-member"]').trigger('click')
    const inputs = wrapper.findAll('[data-test="member-form"] input')
    await inputs[0].setValue('Ana de Nazaré')
    await inputs[1].setValue('Ana')
    await inputs[2].setValue('ana@example.test')
    await wrapper.get('[data-test="member-form"]').trigger('submit')

    expect(wrapper.emitted('addMember')?.[0]?.[0]).toMatchObject({
      full_name: 'Ana de Nazaré',
      preferred_name: 'Ana',
      email: 'ana@example.test',
      role: 'member',
    })

    await wrapper.get('[data-test="invite-01KPERSON"]').trigger('click')
    expect(wrapper.emitted('invite')?.[0]).toEqual(['01KPERSON'])
  })
})
