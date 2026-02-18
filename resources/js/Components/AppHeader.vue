<template>
  <header class="app-header sticky top-0 z-40 border-b border-[var(--border)]/90 bg-[color:rgba(10,14,26,0.9)] backdrop-blur-md">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-4 md:flex-row md:items-center md:justify-between">
      <Link href="/" class="group inline-flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--accent-primary)]/40 text-[18px] shadow-[0_0_16px_var(--glow)]" aria-hidden="true">✦</span>
        <div>
          <p class="font-display text-xl uppercase tracking-[0.06em] text-[var(--text-primary)]">Champions League Simulator</p>
          <p class="text-sm text-[var(--text-secondary)]">Stadium Broadcast Center</p>
        </div>
      </Link>

      <nav aria-label="Tournament progress">
        <ol class="flex flex-wrap items-center gap-2 text-sm">
          <li v-for="step in steps" :key="step.label" class="flex items-center gap-2">
            <span
              v-if="step.disabled"
              class="nav-link-disabled rounded-md px-2 py-1 font-semibold"
              role="link"
              aria-disabled="true"
              :title="step.tooltip"
            >
              {{ step.label }}
            </span>
            <Link
              v-else
              :href="step.href"
              :aria-current="step.isActive ? 'page' : undefined"
              class="rounded-md px-2 py-1 font-semibold transition-colors"
              :class="step.isActive ? 'text-[var(--accent-primary)] underline decoration-[var(--accent-primary)] underline-offset-8' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
            >
              {{ step.label }}
            </Link>
            <span v-if="step.label !== 'Simulation'" class="text-[var(--text-muted)]" aria-hidden="true">›</span>
          </li>
        </ol>
      </nav>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const steps = computed(() => {
  const url = page.url || '/'
  const navigation = page.props?.navigation
  const fixturesAvailable = navigation?.fixturesAvailable === true
  const simulationAvailable = navigation?.simulationAvailable === true

  return [
    { label: 'Teams', href: '/', isActive: url === '/' || url.startsWith('/?'), disabled: false, tooltip: null },
    { label: 'Fixtures', href: '/fixtures', isActive: url.startsWith('/fixtures'), disabled: !fixturesAvailable, tooltip: 'Generate fixtures first' },
    { label: 'Simulation', href: '/simulation', isActive: url.startsWith('/simulation'), disabled: !simulationAvailable, tooltip: 'Generate fixtures first' },
  ]
})
</script>
