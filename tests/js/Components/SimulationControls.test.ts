import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import SimulationControls from '../../../resources/js/Components/SimulationControls.vue'

describe('SimulationControls', () => {
  const baseProps = {
    allWeeksPlayed: false,
    currentWeek: 1,
    isPlaying: false,
    isResetting: false,
  }

  const mountedWrappers: ReturnType<typeof mount>[] = []

  const mountWithTeleport = (props = baseProps) => {
    const wrapper = mount(SimulationControls, {
      props,
      global: { stubs: { teleport: true } },
    })
    mountedWrappers.push(wrapper)
    return wrapper
  }

  afterEach(() => {
    mountedWrappers.forEach(w => w.unmount())
    mountedWrappers.length = 0
  })

  it('renders three buttons', () => {
    const wrapper = mount(SimulationControls, { props: baseProps })
    expect(wrapper.get('[data-testid="play-week-button"]').exists()).toBe(true)
    expect(wrapper.get('[data-testid="play-all-button"]').exists()).toBe(true)
    expect(wrapper.get('[data-testid="reset-button"]').exists()).toBe(true)
  })

  it('emits playWeek on click', async () => {
    const wrapper = mount(SimulationControls, { props: baseProps })
    await wrapper.get('[data-testid="play-week-button"]').trigger('click')
    expect(wrapper.emitted('playWeek')).toBeTruthy()
  })

  it('emits playAll on click', async () => {
    const wrapper = mount(SimulationControls, { props: baseProps })
    await wrapper.get('[data-testid="play-all-button"]').trigger('click')
    expect(wrapper.emitted('playAll')).toBeTruthy()
  })

  it('shows confirm dialog on reset click', async () => {
    const wrapper = mountWithTeleport()
    await wrapper.get('[data-testid="reset-button"]').trigger('click')
    expect(wrapper.find('[data-testid="confirm-dialog-confirm"]').exists()).toBe(true)
  })

  it('emits reset on click after confirmation', async () => {
    const wrapper = mountWithTeleport()
    await wrapper.get('[data-testid="reset-button"]').trigger('click')
    await wrapper.get('[data-testid="confirm-dialog-confirm"]').trigger('click')
    expect(wrapper.emitted('reset')).toBeTruthy()
  })

  it('does not emit reset when dialog is cancelled', async () => {
    const wrapper = mountWithTeleport()
    await wrapper.get('[data-testid="reset-button"]').trigger('click')
    await wrapper.get('[data-testid="confirm-dialog-cancel"]').trigger('click')
    expect(wrapper.emitted('reset')).toBeFalsy()
  })

  it('disables play buttons when allWeeksPlayed', () => {
    const wrapper = mount(SimulationControls, { props: { ...baseProps, allWeeksPlayed: true } })
    expect(wrapper.get('[data-testid="play-week-button"]').attributes('disabled')).toBeDefined()
    expect(wrapper.get('[data-testid="play-all-button"]').attributes('disabled')).toBeDefined()
  })

  it('disables all buttons when isPlaying', () => {
    const wrapper = mount(SimulationControls, { props: { ...baseProps, isPlaying: true } })
    expect(wrapper.get('[data-testid="play-week-button"]').attributes('disabled')).toBeDefined()
    expect(wrapper.get('[data-testid="play-all-button"]').attributes('disabled')).toBeDefined()
    expect(wrapper.get('[data-testid="reset-button"]').attributes('disabled')).toBeDefined()
    expect(wrapper.text()).toContain('Playing...')
  })
})
