import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import MatchResultEditor from '../../../resources/js/Components/MatchResultEditor.vue'

const putMock = vi.fn()
const clearErrorsMock = vi.fn()
const resetMock = vi.fn()

const createFormState = () => ({
  home_score: 0,
  away_score: 0,
  errors: {} as Record<string, string>,
  processing: false,
  put: putMock,
  clearErrors: clearErrorsMock,
  reset: resetMock,
})

let formState = createFormState()

vi.mock('@inertiajs/vue3', () => ({
  useForm: () => formState,
}))

describe('MatchResultEditor', () => {
  const match = {
    id: 9,
    home_score: 2,
    away_score: 1,
    home_team: { name: 'Real Madrid' },
    away_team: { name: 'Liverpool' },
  }

  beforeEach(() => {
    putMock.mockReset()
    clearErrorsMock.mockReset()
    resetMock.mockReset()
    formState = createFormState()
  })

  it('renders modal when match is provided', () => {
    const wrapper = mount(MatchResultEditor, { props: { match } })
    expect(wrapper.get('[data-testid="match-result-editor"]').exists()).toBe(true)
  })

  it('hidden when match is null', () => {
    const wrapper = mount(MatchResultEditor, { props: { match: null } })
    expect(wrapper.find('[data-testid="match-result-editor"]').exists()).toBe(false)
  })

  it('displays team names and pre-fills current scores', () => {
    const wrapper = mount(MatchResultEditor, { props: { match } })
    expect(wrapper.text()).toContain('Real Madrid')
    expect(wrapper.text()).toContain('Liverpool')
    expect((wrapper.get('[data-testid="home-score-input"]').element as HTMLInputElement).value).toBe('2')
    expect((wrapper.get('[data-testid="away-score-input"]').element as HTMLInputElement).value).toBe('1')
  })

  it('emits close on cancel', async () => {
    const wrapper = mount(MatchResultEditor, { props: { match } })
    await wrapper.get('[data-testid="cancel-edit-button"]').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('submits updated scores on save', async () => {
    const wrapper = mount(MatchResultEditor, { props: { match } })
    await wrapper.get('[data-testid="home-score-input"]').setValue('3')
    await wrapper.get('[data-testid="away-score-input"]').setValue('0')
    await wrapper.get('form').trigger('submit')

    expect(putMock).toHaveBeenCalledTimes(1)
  })

  it('enforces non-negative score input constraints', () => {
    const wrapper = mount(MatchResultEditor, { props: { match } })
    const homeInput = wrapper.get('[data-testid="home-score-input"]')
    const awayInput = wrapper.get('[data-testid="away-score-input"]')

    expect(homeInput.attributes('min')).toBe('0')
    expect(awayInput.attributes('min')).toBe('0')
    expect(homeInput.attributes('max')).toBe('20')
    expect(awayInput.attributes('max')).toBe('20')
  })
})
