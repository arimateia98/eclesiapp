import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import OrganizationDashboard from './OrganizationDashboard.vue'
import type { AuthSession, Organization } from '../types/api'

const session: AuthSession = {
  user: { id: '01KUSER', name: 'Maria Silva', email: 'maria@example.test' },
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
  current_user_role: 'owner',
  created_at: '2026-07-22T12:00:00Z',
}

describe('OrganizationDashboard', () => {
  it('gera o endereço curto e solicita a criação da organização', async () => {
    const wrapper = mount(OrganizationDashboard, {
      props: {
        session,
        organizations: [],
        loading: false,
        creating: false,
        error: null,
        notice: null,
        apiOnline: true,
      },
    })

    await wrapper.get('[data-test="open-create-organization"]').trigger('click')
    await wrapper.get('.organization-form input').setValue('Comunidade São José')

    const slugInput = wrapper.get('.slug-input input')
    expect((slugInput.element as HTMLInputElement).value).toBe('comunidade-sao-jose')

    await wrapper.get('.organization-form').trigger('submit')

    expect(wrapper.emitted('create')?.[0]?.[0]).toMatchObject({
      name: 'Comunidade São José',
      slug: 'comunidade-sao-jose',
      type: 'community',
      visibility: 'private',
    })
  })

  it('abre a organização selecionada', async () => {
    const wrapper = mount(OrganizationDashboard, {
      props: {
        session,
        organizations: [organization],
        loading: false,
        creating: false,
        error: null,
        notice: null,
        apiOnline: true,
      },
    })

    await wrapper.get('[data-test="open-01KORG"]').trigger('click')

    expect(wrapper.emitted('select')?.[0]).toEqual([organization])
  })
})
