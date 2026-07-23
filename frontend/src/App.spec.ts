import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from './App.vue'
import type { AuthSession, Organization, OrganizationMembership } from './types/api'

const apiMocks = vi.hoisted(() => ({
  fetchHealth: vi.fn(),
  fetchOrganizations: vi.fn(),
  createOrganization: vi.fn(),
  fetchOrganizationMembers: vi.fn(),
  addOrganizationMember: vi.fn(),
  inviteOrganizationMember: vi.fn(),
  logout: vi.fn(),
}))

vi.mock('./services/api', async (importOriginal) => {
  const original = await importOriginal<typeof import('./services/api')>()

  return {
    ...original,
    ...apiMocks,
  }
})

const session: AuthSession = {
  user: {
    id: '01KTESTUSER00000000000000',
    name: 'Maria',
    email: 'maria@example.test',
  },
  token: 'token-de-teste',
  tokenType: 'Bearer',
}

const organization: Organization = {
  id: '01KTESTORG000000000000000',
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
  id: '01KTESTMEMBER00000000000',
  organization_id: organization.id,
  role: 'member',
  status: 'active',
  joined_at: '2026-07-22T12:00:00Z',
  person: {
    id: '01KTESTPERSON00000000000',
    full_name: 'José da Silva',
    preferred_name: 'José',
    email: 'jose@example.test',
    phone: null,
    status: 'active',
    has_user: false,
    created_at: '2026-07-22T12:00:00Z',
  },
}

describe('App', () => {
  beforeEach(() => {
    sessionStorage.clear()
    window.history.replaceState({}, '', '/')
    apiMocks.fetchHealth.mockResolvedValue({
      service: 'eclesiapp-api',
      status: 'ok',
      timestamp: '2026-07-22T12:00:00Z',
    })
    apiMocks.fetchOrganizations.mockResolvedValue([])
    apiMocks.createOrganization.mockResolvedValue(organization)
    apiMocks.fetchOrganizationMembers.mockResolvedValue([membership])
    apiMocks.addOrganizationMember.mockResolvedValue(membership)
    apiMocks.inviteOrganizationMember.mockResolvedValue({
      id: '01KINVITE',
      person_id: membership.person.id,
      status: 'pending',
      expires_at: '2026-07-24T12:00:00Z',
    })
    apiMocks.logout.mockResolvedValue(undefined)
  })

  it('apresenta o cadastro e confirma a API conectada', async () => {
    const wrapper = mount(App)

    await flushPromises()

    expect(wrapper.get('[data-test="api-status"]').text()).toContain('API conectada')
    expect(wrapper.find('[data-test="auth-form"]').exists()).toBe(true)
  })

  it('apresenta estado seguro quando a API não responde', async () => {
    apiMocks.fetchHealth.mockRejectedValue(new Error('network error'))

    const wrapper = mount(App)

    await flushPromises()

    expect(wrapper.get('[data-test="api-status"]').text()).toContain('API indisponível')
  })

  it('restaura a sessão e lista somente as organizações retornadas pela API', async () => {
    sessionStorage.setItem('eclesiapp.auth-session', JSON.stringify(session))
    apiMocks.fetchOrganizations.mockResolvedValue([organization])

    const wrapper = mount(App)

    await flushPromises()

    expect(apiMocks.fetchOrganizations).toHaveBeenCalledWith(session.token)
    expect(wrapper.get('[data-test="organization-list"]').text()).toContain('Comunidade São José')
    expect(wrapper.text()).not.toContain('Criar conta')
  })

  it('abre a equipe e envia convite para uma pessoa ainda sem usuário', async () => {
    sessionStorage.setItem('eclesiapp.auth-session', JSON.stringify(session))
    apiMocks.fetchOrganizations.mockResolvedValue([organization])

    const wrapper = mount(App)

    await flushPromises()
    await wrapper.get(`[data-test="open-${organization.id}"]`).trigger('click')
    await flushPromises()
    await wrapper.get(`[data-test="invite-${membership.person.id}"]`).trigger('click')
    await flushPromises()

    expect(apiMocks.fetchOrganizationMembers).toHaveBeenCalledWith(
      session.token,
      organization.id,
    )
    expect(apiMocks.inviteOrganizationMember).toHaveBeenCalledWith(
      session.token,
      organization.id,
      membership.person.id,
    )
    expect(wrapper.get('[data-test="workspace-notice"]').text()).toContain('Mailpit')
  })
})
