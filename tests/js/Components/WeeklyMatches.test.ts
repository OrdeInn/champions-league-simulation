import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import WeeklyMatches from '../../../resources/js/Components/WeeklyMatches.vue'

const fixtures = [
  {
    week: 1,
    matches: [
      { id: 1, is_played: true, home_score: 2, away_score: 1, home_team: { name: 'Real Madrid' }, away_team: { name: 'Liverpool' } },
      { id: 2, is_played: false, home_score: null, away_score: null, home_team: { name: 'Bayern' }, away_team: { name: 'Galatasaray' } },
    ],
  },
  {
    week: 2,
    matches: [
      { id: 3, is_played: false, home_score: null, away_score: null, home_team: { name: 'Real Madrid' }, away_team: { name: 'Bayern' } },
      { id: 4, is_played: false, home_score: null, away_score: null, home_team: { name: 'Liverpool' }, away_team: { name: 'Galatasaray' } },
    ],
  },
]

const playedWeekFixtures = [
  {
    week: 1,
    matches: [
      { id: 1, is_played: true, home_score: 2, away_score: 1, home_team: { name: 'Real Madrid' }, away_team: { name: 'Liverpool' } },
    ],
  },
  {
    week: 2,
    matches: [
      { id: 3, is_played: true, home_score: 3, away_score: 0, home_team: { name: 'Bayern' }, away_team: { name: 'Galatasaray' } },
    ],
  },
]

describe('WeeklyMatches', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.spyOn(window, 'matchMedia').mockImplementation(query => ({
      matches: false,
      media: query,
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    }))
  })

  afterEach(() => {
    vi.clearAllTimers()
    vi.useRealTimers()
    vi.restoreAllMocks()
  })

  it('renders matches for selected week', () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })
    expect(wrapper.text()).toContain('Real Madrid')
    expect(wrapper.text()).toContain('Liverpool')
  })

  it('shows vs for unplayed matches', () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })
    expect(wrapper.text()).toContain('vs')
  })

  it('emits selectedWeek on arrow click', async () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })
    await wrapper.get('[data-testid="week-next"]').trigger('click')

    expect(wrapper.emitted('update:selectedWeek')?.[0]).toEqual([2])
  })

  it('emits editMatch when played match clicked', async () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })
    await wrapper.get('[data-testid="edit-match-1"]').trigger('click')

    expect(wrapper.emitted('editMatch')).toBeTruthy()
  })

  it('renders edit button for played matches', () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })

    expect(wrapper.find('[data-testid="edit-match-1"]').exists()).toBe(true)
  })

  it('does not render edit button for unplayed matches', () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })

    expect(wrapper.find('[data-testid="edit-match-2"]').exists()).toBe(false)
  })

  it('edit button has accessible aria-label', () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })
    const editButton = wrapper.get('[data-testid="edit-match-1"]')

    expect(editButton.attributes('aria-label')).toContain('Real Madrid')
    expect(editButton.attributes('aria-label')).toContain('Liverpool')
  })

  it('played match row is a div, not a button', () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })

    expect(wrapper.get('[data-testid="played-match-1"]').element.tagName).toBe('DIV')
  })

  it('clicking played match row does not emit editMatch', async () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })
    await wrapper.get('[data-testid="played-match-1"]').trigger('click')

    expect(wrapper.emitted('editMatch')).toBeUndefined()
  })

  it('edit button is a focusable button element', () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })
    const editButton = wrapper.get('[data-testid="edit-match-1"]')

    expect(editButton.element.tagName).toBe('BUTTON')
    expect(editButton.attributes('type')).toBe('button')
  })

  it('disables previous arrow on week 1', () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 1 } })
    expect(wrapper.get('[data-testid="week-prev"]').attributes('disabled')).toBeDefined()
  })

  it('disables next arrow on last week', () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures, selectedWeek: 2 } })
    expect(wrapper.get('[data-testid="week-next"]').attributes('disabled')).toBeDefined()
  })

  it('does not animate when navigating between played weeks', async () => {
    const setIntervalSpy = vi.spyOn(globalThis, 'setInterval')
    const wrapper = mount(WeeklyMatches, { props: { fixtures: playedWeekFixtures, selectedWeek: 1 } })

    setIntervalSpy.mockClear()
    await wrapper.setProps({ selectedWeek: 2 })

    expect(setIntervalSpy).not.toHaveBeenCalled()
    expect(wrapper.get('[data-testid="played-match-3"]').text()).toContain('3')
    expect(wrapper.get('[data-testid="played-match-3"]').text()).toContain('0')
  })

  it('animates when fixtures update for the current week', async () => {
    const wrapper = mount(WeeklyMatches, { props: { fixtures: playedWeekFixtures, selectedWeek: 1 } })

    await wrapper.setProps({
      fixtures: [
        {
          week: 1,
          matches: [
            { id: 1, is_played: true, home_score: 4, away_score: 2, home_team: { name: 'Real Madrid' }, away_team: { name: 'Liverpool' } },
          ],
        },
        playedWeekFixtures[1],
      ],
    })

    expect(wrapper.get('[data-testid="played-match-1"]').text()).toContain('0')
    vi.advanceTimersByTime(300)
    await nextTick()

    const text = wrapper.get('[data-testid="played-match-1"]').text()
    expect(text).toContain('4')
    expect(text).toContain('2')
  })

  it('clears existing timers before starting new ones for rapid fixture updates', async () => {
    const setIntervalSpy = vi.spyOn(globalThis, 'setInterval')
    const clearIntervalSpy = vi.spyOn(globalThis, 'clearInterval')
    const wrapper = mount(WeeklyMatches, { props: { fixtures: playedWeekFixtures, selectedWeek: 1 } })

    await wrapper.setProps({
      fixtures: [
        {
          week: 1,
          matches: [
            { id: 1, is_played: true, home_score: 3, away_score: 1, home_team: { name: 'Real Madrid' }, away_team: { name: 'Liverpool' } },
          ],
        },
        playedWeekFixtures[1],
      ],
    })

    const firstHomeTimer = setIntervalSpy.mock.results[0]?.value
    const firstAwayTimer = setIntervalSpy.mock.results[1]?.value

    await wrapper.setProps({
      fixtures: [
        {
          week: 1,
          matches: [
            { id: 1, is_played: true, home_score: 5, away_score: 3, home_team: { name: 'Real Madrid' }, away_team: { name: 'Liverpool' } },
          ],
        },
        playedWeekFixtures[1],
      ],
    })

    expect(clearIntervalSpy).toHaveBeenCalledWith(firstHomeTimer)
    expect(clearIntervalSpy).toHaveBeenCalledWith(firstAwayTimer)
  })

  it('clears active timers on unmount', async () => {
    const setIntervalSpy = vi.spyOn(globalThis, 'setInterval')
    const clearIntervalSpy = vi.spyOn(globalThis, 'clearInterval')
    const wrapper = mount(WeeklyMatches, { props: { fixtures: playedWeekFixtures, selectedWeek: 1 } })

    await wrapper.setProps({
      fixtures: [
        {
          week: 1,
          matches: [
            { id: 1, is_played: true, home_score: 4, away_score: 2, home_team: { name: 'Real Madrid' }, away_team: { name: 'Liverpool' } },
          ],
        },
        playedWeekFixtures[1],
      ],
    })

    const homeTimer = setIntervalSpy.mock.results[0]?.value
    const awayTimer = setIntervalSpy.mock.results[1]?.value

    wrapper.unmount()

    expect(clearIntervalSpy).toHaveBeenCalledWith(homeTimer)
    expect(clearIntervalSpy).toHaveBeenCalledWith(awayTimer)
  })
})
