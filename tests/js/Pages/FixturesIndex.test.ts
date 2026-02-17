import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import FixturesIndex from '../../../resources/js/Pages/Fixtures/Index.vue'

const { visitMock } = vi.hoisted(() => ({
  visitMock: vi.fn(),
}))

vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: visitMock,
  },
}))

describe('Fixtures/Index', () => {
  const fixtures = [
    { id: 1, week: 1, matches: [{ id: 1, home_team: { name: 'A' }, away_team: { name: 'B' } }] },
    { id: 2, week: 2, matches: [{ id: 2, home_team: { name: 'C' }, away_team: { name: 'D' } }] },
  ]

  it('renders week fixture cards', () => {
    const wrapper = mount(FixturesIndex, { props: { fixtures } })
    expect(wrapper.findAll('[data-testid="week-card"]')).toHaveLength(2)
  })

  it('shows page heading', () => {
    const wrapper = mount(FixturesIndex, { props: { fixtures } })
    expect(wrapper.text()).toContain('Fixture Draw')
  })

  it('navigates to simulation on click', async () => {
    visitMock.mockReset()
    const wrapper = mount(FixturesIndex, { props: { fixtures } })
    await wrapper.get('[data-testid="start-simulation-button"]').trigger('click')
    expect(visitMock).toHaveBeenCalledWith('/simulation.index', expect.objectContaining({
      preserveScroll: true,
      onFinish: expect.any(Function),
    }))
  })
})
