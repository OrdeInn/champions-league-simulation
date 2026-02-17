import { config } from '@vue/test-utils'
import { vi } from 'vitest'

config.global.stubs = {
  transition: false,
  'transition-group': false,
}

if (typeof window !== 'undefined') {
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation(() => ({
      matches: true,
      media: '(prefers-reduced-motion: reduce)',
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    })),
  })

  Object.defineProperty(window, 'confirm', {
    writable: true,
    value: vi.fn(() => true),
  })
}

;(globalThis as any).route = (name: string, param?: string | number) => {
  if (param === undefined) {
    return `/${name}`
  }

  return `/${name}/${param}`
}
