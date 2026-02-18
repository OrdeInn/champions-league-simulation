<template>
  <section class="space-y-8" data-testid="fixtures-page">
    <header class="space-y-3">
      <p class="inline-flex items-center gap-2 rounded-full border border-[var(--border)] bg-[var(--bg-tertiary)] px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--accent-gold)]">
        <span aria-hidden="true">🏆</span>
        Fixture Draw
      </p>

      <div>
        <h1 class="font-display text-4xl uppercase tracking-[0.06em] text-[var(--text-primary)] md:text-5xl">
          Fixture Draw
        </h1>
        <p class="mt-2 text-base text-[var(--text-secondary)] md:text-lg">
          6 Weeks - Home &amp; Away Round Robin
        </p>
      </div>
    </header>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5">
      <WeekFixtureCard
        v-for="(fixture, index) in fixtures"
        :key="fixture.id"
        :fixture="fixture"
        :index="index"
      />
    </div>

    <button
      type="button"
      data-testid="start-simulation-button"
      class="cta-primary w-full rounded-xl px-5 py-4 font-display text-lg uppercase tracking-[0.14em] text-[#001018] disabled:cursor-not-allowed disabled:opacity-70"
      :disabled="isNavigating"
      @click="startSimulation"
    >
      <span class="inline-flex items-center gap-2">
        <span v-if="isNavigating" class="loading-spinner" aria-hidden="true"></span>
        {{ isNavigating ? 'Starting...' : 'Start Simulation' }}
      </span>
    </button>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import WeekFixtureCard from '../../Components/WeekFixtureCard.vue'

defineProps({
  fixtures: {
    type: Array,
    required: true,
  },
})

const isNavigating = ref(false)

const startSimulation = () => {
  if (isNavigating.value) {
    return
  }

  isNavigating.value = true

  router.visit(route('simulation.index'), {
    preserveScroll: true,
    onFinish: () => {
      isNavigating.value = false
    },
  })
}
</script>
