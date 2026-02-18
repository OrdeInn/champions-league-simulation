<template>
  <article data-testid="week-card" class="week-card card-surface p-4" :style="{ '--week-delay': `${index * 150}ms` }">
    <header class="mb-3 pb-2">
      <h3 class="font-display text-2xl uppercase tracking-[0.08em] text-[var(--accent-primary)]">
        Week {{ fixture.week }}
      </h3>
      <div class="mt-1 h-[2px] w-16 rounded bg-[linear-gradient(90deg,var(--accent-primary)_0%,transparent_100%)]"></div>
    </header>

    <div class="space-y-2.5">
      <MatchPairing
        v-for="(match, matchIndex) in fixture.matches"
        :key="match.id"
        :match="match"
        :animation-delay="index * 150 + matchIndex * 100"
      />
    </div>
  </article>
</template>

<script setup>
import MatchPairing from './MatchPairing.vue'

defineProps({
  fixture: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
})
</script>
