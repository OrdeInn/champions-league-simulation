import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import PredictionPanel from '../../../resources/js/Components/PredictionPanel.vue'

describe('PredictionPanel', () => {
  it('shows available from week 4 when null', () => {
    const wrapper = mount(PredictionPanel, { props: { predictions: null } })
    expect(wrapper.get('[data-testid="prediction-placeholder"]').text()).toContain('Week 4')
  })

  it('renders prediction bars when data provided', () => {
    const wrapper = mount(PredictionPanel, {
      props: {
        predictions: [
          { team: { id: 1, name: 'Team A' }, probability: 60 },
          { team: { id: 2, name: 'Team B' }, probability: 40 },
        ],
      },
    })

    expect(wrapper.findAll('[data-testid="probability-bar"]')).toHaveLength(2)
  })

  it('sorts teams by probability descending', () => {
    const wrapper = mount(PredictionPanel, {
      props: {
        predictions: [
          { team: { id: 1, name: 'Team A' }, probability: 10 },
          { team: { id: 2, name: 'Team B' }, probability: 80 },
        ],
      },
    })

    expect(wrapper.findAll('[data-testid="probability-bar"]')[0].text()).toContain('Team B')
  })

  it('passes correct probability to bars', () => {
    const wrapper = mount(PredictionPanel, {
      props: {
        predictions: [{ team: { id: 1, name: 'Team A' }, probability: 33.3 }],
      },
    })

    expect(wrapper.text()).toContain('33.3%')
  })
})
