import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import TeamsIndex from '../../../resources/js/Pages/Teams/Index.vue'

const { postMock } = vi.hoisted(() => ({
  postMock: vi.fn(),
}))

vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: postMock,
  },
}))

describe('Teams/Index', () => {
  const teams = [
    { id: 1, name: 'Real Madrid', short_name: 'RMA', power: 90 },
    { id: 2, name: 'Liverpool', short_name: 'LIV', power: 85 },
  ]

  it('renders team cards', () => {
    const wrapper = mount(TeamsIndex, { props: { teams } })
    expect(wrapper.findAll('[data-testid="team-card"]')).toHaveLength(2)
  })

  it('shows page heading', () => {
    const wrapper = mount(TeamsIndex, { props: { teams } })
    expect(wrapper.text()).toContain('Tournament Teams')
  })

  it('posts fixture generation on click', async () => {
    postMock.mockReset()
    const wrapper = mount(TeamsIndex, { props: { teams } })
    await wrapper.get('[data-testid="generate-fixture-button"]').trigger('click')
    expect(postMock).toHaveBeenCalledWith('/fixtures.generate', {}, expect.objectContaining({
      preserveScroll: true,
      onFinish: expect.any(Function),
    }))
  })
})
