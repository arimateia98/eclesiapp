import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import MinistryCapabilitiesPanel from './MinistryCapabilitiesPanel.vue'
import type {
  MinistryType,
  Organization,
  OrganizationMembership,
  PersonFunction,
  ServiceFunction,
} from '../types/api'

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
    email: null,
    phone: null,
    status: 'active',
    has_user: false,
    created_at: '2026-07-22T12:00:00Z',
  },
}

const ministryType: MinistryType = {
  id: '01KTYPE',
  organization_id: organization.id,
  name: 'Liturgia',
  slug: 'liturgia',
  description: null,
  active: true,
  created_at: '2026-07-22T12:00:00Z',
}

const serviceFunction: ServiceFunction = {
  id: '01KFUNCTION',
  organization_id: organization.id,
  ministry_type_id: ministryType.id,
  name: 'Leitor',
  slug: 'leitor',
  active: true,
  ministry_type: ministryType,
  created_at: '2026-07-22T12:00:00Z',
}

const personFunction: PersonFunction = {
  organization_id: organization.id,
  person_id: membership.person.id,
  service_function_id: serviceFunction.id,
  service_function: serviceFunction,
  assigned_at: '2026-07-22T12:00:00Z',
}

describe('MinistryCapabilitiesPanel', () => {
  it('cria catálogo e alterna uma competência da pessoa selecionada', async () => {
    const wrapper = mount(MinistryCapabilitiesPanel, {
      props: {
        organization,
        members: [membership],
        ministryTypes: [ministryType],
        serviceFunctions: [serviceFunction],
        personFunctions: [personFunction],
        selectedPersonId: membership.person.id,
        loading: false,
        catalogBusy: null,
        updatingFunctionId: null,
        error: null,
      },
    })

    await wrapper.get('[data-test="ministry-type-form"] input').setValue('Música')
    await wrapper.get('[data-test="ministry-type-form"]').trigger('submit')
    expect(wrapper.emitted('createMinistryType')?.[0]?.[0]).toMatchObject({ name: 'Música' })

    await wrapper.get('[data-test="service-function-form"] input').setValue('Salmista')
    await wrapper.get('[data-test="service-function-form"]').trigger('submit')
    expect(wrapper.emitted('createServiceFunction')?.[0]?.[0]).toEqual({
      ministry_type_id: ministryType.id,
      name: 'Salmista',
    })

    await wrapper.get(`[data-test="toggle-function-${serviceFunction.id}"]`).trigger('click')
    expect(wrapper.emitted('toggleFunction')?.[0]).toEqual([serviceFunction.id, true])
  })
})
