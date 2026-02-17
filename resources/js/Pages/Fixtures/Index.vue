<template>
  <section class="space-y-8">
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
      class="start-button w-full rounded-xl px-5 py-4 font-display text-lg uppercase tracking-[0.14em] text-[#001018] disabled:cursor-not-allowed disabled:opacity-70"
      :disabled="isNavigating"
      @click="startSimulation"
    >
      <span class="inline-flex items-center gap-2">
        <span v-if="isNavigating" class="spinner" aria-hidden="true"></span>
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

<style scoped>
.start-button {
  background: linear-gradient(100deg, var(--accent-primary) 0%, #67e8f9 52%, #22d3ee 100%);
  box-shadow: 0 0 20px var(--glow), 0 16px 30px rgba(0, 229, 255, 0.22);
  transition: transform 180ms ease, box-shadow 180ms ease, filter 180ms ease;
}

.start-button:hover:not(:disabled) {
  transform: scale(1.01);
  filter: brightness(1.04);
  box-shadow: 0 0 26px rgba(0, 229, 255, 0.35), 0 20px 34px rgba(0, 229, 255, 0.25);
}

.start-button:active:not(:disabled) {
  transform: scale(0.996);
}

.spinner {
  width: 1.05rem;
  height: 1.05rem;
  border-radius: 9999px;
  border: 2px solid rgba(0, 16, 24, 0.22);
  border-top-color: rgba(0, 16, 24, 0.9);
  animation: spin 700ms linear infinite;
}

@media (prefers-reduced-motion: reduce) {
  .start-button {
    transition: none;
  }

  .spinner {
    animation: none;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
