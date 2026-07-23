import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import App from './App.vue'

const fetchHealthMock = vi.hoisted(() => vi.fn())

vi.mock('./services/api', () => ({
  fetchHealth: fetchHealthMock,
}))

describe('App', () => {
  beforeEach(() => {
    fetchHealthMock.mockResolvedValue({
      service: 'eclesiapp-api',
      status: 'ok',
      timestamp: '2026-07-22T12:00:00Z',
    })
  })

  it('confirma a integração quando a API responde', async () => {
    const wrapper = mount(App)

    await flushPromises()

    expect(wrapper.get('[data-test="api-status"]').text()).toContain('API conectada')
  })

  it('apresenta um erro seguro quando a API não responde', async () => {
    fetchHealthMock.mockRejectedValue(new Error('network error'))

    const wrapper = mount(App)

    await flushPromises()

    expect(wrapper.get('[data-test="api-status"]').text()).toContain('ainda não está disponível')
  })
})
