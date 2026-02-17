import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import SimulationIndex from '../../../resources/js/Pages/Simulation/Index.vue'

const { postMock } = vi.hoisted(() => ({
  postMock: vi.fn(),
}))

vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: postMock,
  },
}))

describe('Simulation/Index', () => {
  const baseProps = {
    standings: [{ position: 1, team: { id: 1, name: 'A' }, played: 0, won: 0, drawn: 0, lost: 0, goals_for: 0, goals_against: 0, goal_difference: 0, points: 0 }],
    fixtures: [{ id: 1, week: 1, matches: [] }],
    currentWeek: 3,
    predictions: null,
    allWeeksPlayed: false,
  }

  const stubs = {
    LeagueTable: { template: '<div data-testid="league-table-stub" />' },
    PredictionPanel: { template: '<div data-testid="prediction-panel-stub" />' },
    WeeklyMatches: {
      props: ['selectedWeek'],
      emits: ['update:selectedWeek', 'editMatch'],
      template: `
        <div>
          <span data-testid="selected-week">{{ selectedWeek }}</span>
          <button data-testid="emit-edit" @click="$emit('editMatch', { id: 99 })">emit edit</button>
        </div>
      `,
    },
    SimulationControls: {
      emits: ['playWeek', 'playAll', 'reset'],
      template: `
        <div>
          <button data-testid="emit-play-week" @click="$emit('playWeek')">play week</button>
          <button data-testid="emit-play-all" @click="$emit('playAll')">play all</button>
          <button data-testid="emit-reset" @click="$emit('reset')">reset</button>
        </div>
      `,
    },
    MatchResultEditor: {
      props: ['match'],
      template: '<div data-testid="editor-match">{{ match ? match.id : "none" }}</div>',
    },
  }

  it('defaults selected week to currentWeek', () => {
    const wrapper = mount(SimulationIndex, { props: baseProps, global: { stubs } })
    expect(wrapper.get('[data-testid="selected-week"]').text()).toBe('3')
  })

  it('defaults selected week to one when currentWeek is zero', () => {
    const wrapper = mount(SimulationIndex, { props: { ...baseProps, currentWeek: 0 }, global: { stubs } })
    expect(wrapper.get('[data-testid="selected-week"]').text()).toBe('1')
  })

  it('calls play-week route on action', async () => {
    postMock.mockReset()
    const wrapper = mount(SimulationIndex, { props: baseProps, global: { stubs } })
    await wrapper.get('[data-testid="emit-play-week"]').trigger('click')
    expect(postMock).toHaveBeenCalledWith('/simulation.play-week', {}, expect.objectContaining({
      preserveScroll: true,
      onFinish: expect.any(Function),
    }))
  })

  it('calls play-all route on action', async () => {
    postMock.mockReset()
    const wrapper = mount(SimulationIndex, { props: baseProps, global: { stubs } })
    await wrapper.get('[data-testid="emit-play-all"]').trigger('click')
    expect(postMock).toHaveBeenCalledWith('/simulation.play-all', {}, expect.objectContaining({
      preserveScroll: true,
      onFinish: expect.any(Function),
    }))
  })

  it('calls reset route on action', async () => {
    postMock.mockReset()
    const wrapper = mount(SimulationIndex, { props: baseProps, global: { stubs } })
    await wrapper.get('[data-testid="emit-reset"]').trigger('click')
    expect(postMock).toHaveBeenCalledWith('/simulation.reset', {}, expect.objectContaining({
      preserveScroll: true,
      onFinish: expect.any(Function),
    }))
  })

  it('opens editor when weekly matches emits editMatch', async () => {
    const wrapper = mount(SimulationIndex, { props: baseProps, global: { stubs } })
    await wrapper.get('[data-testid="emit-edit"]').trigger('click')
    expect(wrapper.get('[data-testid="editor-match"]').text()).toBe('99')
  })
})
