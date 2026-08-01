import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import SchedulingPanel from './SchedulingPanel.vue'
import type {
  CreateEventInput,
  EventType,
  Location,
  MinistryType,
  Organization,
  ScheduledEvent,
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
  created_at: '2026-08-01T12:00:00Z',
}

const eventType: EventType = {
  id: '01KEVENTTYPE',
  organization_id: organization.id,
  name: 'Missa',
  slug: 'missa',
  active: true,
  created_at: '2026-08-01T12:00:00Z',
}

const location: Location = {
  id: '01KLOCATION',
  organization_id: organization.id,
  name: 'Igreja Matriz',
  slug: 'igreja-matriz',
  address_line: null,
  city: 'Fortaleza',
  timezone: 'America/Fortaleza',
  active: true,
  created_at: '2026-08-01T12:00:00Z',
}

const ministryType: MinistryType = {
  id: '01KMINISTRY',
  organization_id: organization.id,
  name: 'Liturgia',
  slug: 'liturgia',
  description: null,
  active: true,
  created_at: '2026-08-01T12:00:00Z',
}

const serviceFunction: ServiceFunction = {
  id: '01KFUNCTION',
  organization_id: organization.id,
  ministry_type_id: ministryType.id,
  name: 'Leitor',
  slug: 'leitor',
  active: true,
  ministry_type: ministryType,
  created_at: '2026-08-01T12:00:00Z',
}

const event: ScheduledEvent = {
  id: '01KEVENT',
  publisher_organization_id: organization.id,
  host_organization_id: organization.id,
  event_type_id: eventType.id,
  event_type: eventType,
  location_id: location.id,
  location,
  title: 'Missa dominical',
  description: null,
  starts_at: '2026-08-02T21:00:00Z',
  ends_at: '2026-08-02T22:00:00Z',
  visibility: 'private',
  status: 'draft',
  created_at: '2026-08-01T12:00:00Z',
}

function mountPanel(options: Partial<InstanceType<typeof SchedulingPanel>['$props']> = {}) {
  return mount(SchedulingPanel, {
    props: {
      organization,
      eventTypes: [eventType],
      locations: [location],
      events: [],
      missions: [],
      ministryTypes: [ministryType],
      serviceFunctions: [serviceFunction],
      selectedEventId: null,
      loading: false,
      loadingMissions: false,
      busy: null,
      error: null,
      ...options,
    },
  })
}

describe('SchedulingPanel', () => {
  it('emite um evento com instantes normalizados para ISO 8601', async () => {
    const wrapper = mountPanel()

    await wrapper.get('[data-test="toggle-event-form"]').trigger('click')
    const form = wrapper.get('[data-test="event-form"]')
    const selects = form.findAll('select')
    const inputs = form.findAll('input')
    await selects[0].setValue(eventType.id)
    await selects[1].setValue(location.id)
    await inputs[0].setValue('Missa de Nossa Senhora')
    await inputs[1].setValue('2026-08-15T19:00')
    await inputs[2].setValue('2026-08-15T20:30')
    await form.trigger('submit')

    const emittedEvent = wrapper.emitted('createEvent')?.[0]?.[0] as CreateEventInput

    expect(emittedEvent).toMatchObject({
      event_type_id: eventType.id,
      location_id: location.id,
      title: 'Missa de Nossa Senhora',
    })
    expect(emittedEvent.starts_at).toBe('2026-08-15T22:00:00.000Z')
  })

  it('cria missão com vagas distintas para o evento selecionado', async () => {
    const wrapper = mountPanel({ events: [event], selectedEventId: event.id })

    await wrapper.get('[data-test="toggle-mission-form"]').trigger('click')
    const form = wrapper.get('[data-test="mission-form"]')
    const selects = form.findAll('select')
    await selects[0].setValue(ministryType.id)
    await wrapper.vm.$nextTick()
    await form.findAll('input')[0].setValue('Equipe de leitores')
    await form.findAll('select')[1].setValue(serviceFunction.id)
    await form.find('input[type="number"]').setValue(2)
    await form.trigger('submit')

    expect(wrapper.emitted('createMission')?.[0]).toEqual([
      event.id,
      {
        ministry_type_id: ministryType.id,
        title: 'Equipe de leitores',
        description: undefined,
        slots: [{ service_function_id: serviceFunction.id, quantity: 2, required: true }],
      },
    ])
  })

  it('apresenta somente leitura para membros sem papel de planejamento', () => {
    const wrapper = mountPanel({
      organization: { ...organization, current_user_role: 'member' },
      events: [event],
    })

    expect(wrapper.find('[data-test="toggle-scheduling-catalog"]').exists()).toBe(false)
    expect(wrapper.find('[data-test="toggle-event-form"]').exists()).toBe(false)
  })

  it('preserva o formulário quando a API rejeita o rascunho', async () => {
    const wrapper = mountPanel()

    await wrapper.get('[data-test="toggle-event-form"]').trigger('click')
    const title = wrapper.get('[data-test="event-form"]').findAll('input')[0]
    await title.setValue('Celebração da comunidade')
    await wrapper.setProps({ busy: 'event' })
    await wrapper.setProps({ busy: null, error: 'O horário é inválido.' })

    expect(wrapper.find('[data-test="event-form"]').exists()).toBe(true)
    expect(wrapper.get('[data-test="event-form"]').findAll('input')[0].element.value)
      .toBe('Celebração da comunidade')
  })
})
