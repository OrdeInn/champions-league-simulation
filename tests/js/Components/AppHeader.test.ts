import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import AppHeader from '../../../resources/js/Components/AppHeader.vue'

const { usePageMock } = vi.hoisted(() => ({
  usePageMock: vi.fn(),
}))

vi.mock('@inertiajs/vue3', () => ({
  Link: {
    name: 'InertiaLink',
    props: {
      href: { type: String, required: true },
      ariaCurrent: { type: String, required: false },
    },
    template: '<a :href="href" :aria-current="ariaCurrent"><slot /></a>',
  },
  usePage: usePageMock,
}))

const mountHeader = (options?: { url?: string; props?: Record<string, unknown> }) => {
  usePageMock.mockReturnValue({
    url: options?.url ?? '/',
    props: options?.props ?? {},
  })

  return mount(AppHeader)
}

const navStep = (wrapper: ReturnType<typeof mount>, label: string) =>
  wrapper.findAll('nav li').find((item) => item.text().includes(label))

describe('AppHeader', () => {
  it('renders Fixtures and Simulation as disabled spans when navigation flags are false', () => {
    const wrapper = mountHeader({
      props: {
        navigation: {
          fixturesAvailable: false,
          simulationAvailable: false,
        },
      },
    })

    const fixturesStep = navStep(wrapper, 'Fixtures')
    const simulationStep = navStep(wrapper, 'Simulation')

    expect(fixturesStep?.find('span.nav-link-disabled').exists()).toBe(true)
    expect(simulationStep?.find('span.nav-link-disabled').exists()).toBe(true)
    expect(fixturesStep?.find('a').exists()).toBe(false)
    expect(simulationStep?.find('a').exists()).toBe(false)
  })

  it('applies accessibility and tooltip attributes to disabled links', () => {
    const wrapper = mountHeader({
      props: {
        navigation: {
          fixturesAvailable: false,
          simulationAvailable: false,
        },
      },
    })

    const fixturesDisabled = navStep(wrapper, 'Fixtures')?.get('span.nav-link-disabled')

    expect(fixturesDisabled?.attributes('role')).toBe('link')
    expect(fixturesDisabled?.attributes('aria-disabled')).toBe('true')
    expect(fixturesDisabled?.attributes('title')).toBe('Generate fixtures first')
    expect(fixturesDisabled?.attributes('tabindex')).toBeUndefined()
  })

  it('renders all tournament steps as links when both flags are true', () => {
    const wrapper = mountHeader({
      props: {
        navigation: {
          fixturesAvailable: true,
          simulationAvailable: true,
        },
      },
    })

    expect(navStep(wrapper, 'Teams')?.find('a').exists()).toBe(true)
    expect(navStep(wrapper, 'Fixtures')?.find('a').exists()).toBe(true)
    expect(navStep(wrapper, 'Simulation')?.find('a').exists()).toBe(true)
    expect(wrapper.findAll('nav li a')).toHaveLength(3)
  })

  it('keeps Teams as a link even when other steps are disabled', () => {
    const wrapper = mountHeader({
      props: {
        navigation: {
          fixturesAvailable: false,
          simulationAvailable: false,
        },
      },
    })

    expect(navStep(wrapper, 'Teams')?.find('a').exists()).toBe(true)
    expect(navStep(wrapper, 'Teams')?.find('span.nav-link-disabled').exists()).toBe(false)
  })

  it('defaults Fixtures and Simulation to disabled when navigation prop is missing', () => {
    const wrapper = mountHeader({
      props: {},
    })

    expect(navStep(wrapper, 'Fixtures')?.find('span.nav-link-disabled').exists()).toBe(true)
    expect(navStep(wrapper, 'Simulation')?.find('span.nav-link-disabled').exists()).toBe(true)
  })
})
