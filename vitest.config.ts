import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    include: ['tests/js/**/*.test.ts'],
    setupFiles: ['tests/js/setup.ts'],
    coverage: {
      provider: 'v8',
      include: ['resources/js/**/*.vue'],
    },
  },
})
