import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import SimulationControls from '../../../resources/js/Components/SimulationControls.vue'

describe('SimulationControls', () => {
  const baseProps = {
    allWeeksPlayed: false,
    currentWeek: 1,
    isPlaying: false,
    isResetting: false,
  }

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

  it('emits reset on click after confirmation', async () => {
    const confirmSpy = vi.spyOn(window, 'confirm').mockReturnValue(true)
    const wrapper = mount(SimulationControls, { props: baseProps })
    await wrapper.get('[data-testid="reset-button"]').trigger('click')
    expect(confirmSpy).toHaveBeenCalled()
    expect(wrapper.emitted('reset')).toBeTruthy()
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
