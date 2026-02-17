<template>
  <section class="card-surface p-4 md:p-5" data-testid="prediction-panel">
    <header class="mb-4 flex items-center gap-2">
      <span aria-hidden="true">🏆</span>
      <h2 class="font-display text-2xl uppercase tracking-[0.08em] text-[var(--accent-gold)]">Championship Predictions</h2>
    </header>

    <div v-if="!predictions" data-testid="prediction-placeholder" class="rounded-lg border border-dashed border-[var(--border)] bg-[var(--bg-tertiary)]/45 px-4 py-5 text-sm text-[var(--text-secondary)]">
      <p class="inline-flex items-center gap-2">
        <span aria-hidden="true">⏳</span>
        Predictions available from Week 4.
      </p>
    </div>

    <div v-else class="space-y-3">
      <ProbabilityBar
        v-for="item in sortedPredictions"
        :key="item.team.id"
        :team="item.team"
        :probability="item.probability"
      />
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import ProbabilityBar from './ProbabilityBar.vue'

const props = defineProps({
  predictions: {
    type: Array,
    default: null,
  },
})

const sortedPredictions = computed(() => {
  if (!props.predictions) {
    return []
  }

  return [...props.predictions].sort((a, b) => b.probability - a.probability)
})
</script>
