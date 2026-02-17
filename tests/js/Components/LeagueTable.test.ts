import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import LeagueTable from '../../../resources/js/Components/LeagueTable.vue'

const standings = [
  { position: 1, team: { id: 1, name: 'Real Madrid' }, played: 3, won: 3, drawn: 0, lost: 0, goals_for: 7, goals_against: 2, goal_difference: 5, points: 9 },
  { position: 2, team: { id: 2, name: 'Liverpool' }, played: 3, won: 2, drawn: 0, lost: 1, goals_for: 5, goals_against: 3, goal_difference: 2, points: 6 },
  { position: 3, team: { id: 3, name: 'Bayern' }, played: 3, won: 1, drawn: 1, lost: 1, goals_for: 3, goals_against: 3, goal_difference: 0, points: 4 },
  { position: 4, team: { id: 4, name: 'Galatasaray' }, played: 3, won: 0, drawn: 1, lost: 2, goals_for: 1, goals_against: 4, goal_difference: -3, points: 1 },
]

describe('LeagueTable', () => {
  it('renders all teams', () => {
    const wrapper = mount(LeagueTable, { props: { standings } })
    expect(wrapper.findAll('[data-testid="standing-row"]')).toHaveLength(4)
  })

  it('displays correct column headers', () => {
    const wrapper = mount(LeagueTable, { props: { standings } })
    ;['#', 'Team', 'P', 'W', 'D', 'L', 'GF', 'GA', 'GD', 'PTS'].forEach(header => {
      expect(wrapper.text()).toContain(header)
    })
  })

  it('shows points correctly', () => {
    const wrapper = mount(LeagueTable, { props: { standings } })
    expect(wrapper.text()).toContain('9')
    expect(wrapper.text()).toContain('1')
  })

  it('highlights first place with gold border', () => {
    const wrapper = mount(LeagueTable, { props: { standings } })
    expect(wrapper.findAll('[data-testid="standing-row"]')[0].classes()).toContain('border-l-[var(--accent-gold)]')
  })

  it('sorts teams by position in provided order', () => {
    const wrapper = mount(LeagueTable, { props: { standings } })
    const rows = wrapper.findAll('[data-testid="standing-row"]')
    expect(rows[0].text()).toContain('Real Madrid')
    expect(rows[3].text()).toContain('Galatasaray')
  })
})
