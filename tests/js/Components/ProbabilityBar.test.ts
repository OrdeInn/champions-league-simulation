import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ProbabilityBar from '../../../resources/js/Components/ProbabilityBar.vue'

describe('ProbabilityBar', () => {
  it('renders team name', () => {
    const wrapper = mount(ProbabilityBar, { props: { team: { name: 'Team A' }, probability: 12 } })
    expect(wrapper.text()).toContain('Team A')
  })

  it('shows correct percentage', () => {
    const wrapper = mount(ProbabilityBar, { props: { team: { name: 'Team A' }, probability: 12.5 } })
    expect(wrapper.text()).toContain('12.5%')
  })

  it('bar width matches probability', () => {
    const wrapper = mount(ProbabilityBar, { props: { team: { name: 'Team A' }, probability: 55 } })
    expect(wrapper.get('.probability-fill').attributes('style')).toContain('width: 55%')
  })

  it('uses gold for high probability', () => {
    const wrapper = mount(ProbabilityBar, { props: { team: { name: 'Team A' }, probability: 70 } })
    expect(wrapper.get('.probability-fill').attributes('style')).toContain('var(--accent-gold)')
  })

  it('uses red for very low probability', () => {
    const wrapper = mount(ProbabilityBar, { props: { team: { name: 'Team A' }, probability: 2 } })
    expect(wrapper.get('.probability-fill').attributes('style')).toContain('var(--accent-red)')
  })
})
