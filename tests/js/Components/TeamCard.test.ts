import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { describe, expect, it } from 'vitest'
import TeamCard from '../../../resources/js/Components/TeamCard.vue'

describe('TeamCard', () => {
  const team = { id: 1, name: 'Real Madrid', short_name: 'RMA', power: 90 }

  it('renders team name', () => {
    const wrapper = mount(TeamCard, { props: { team, index: 0 } })
    expect(wrapper.text()).toContain('Real Madrid')
  })

  it('renders power bar with correct width', async () => {
    const wrapper = mount(TeamCard, { props: { team, index: 0 } })
    await nextTick()

    const bar = wrapper.get('[data-testid="team-power-fill"]')
    expect(bar.attributes('style')).toContain('width: 90%')
  })

  it('applies animation delay based on index', () => {
    const wrapper = mount(TeamCard, { props: { team, index: 2 } })
    expect(wrapper.get('[data-testid="team-card"]').attributes('style')).toContain('--stagger-delay: 200ms')
  })
})
