<template>
  <section class="space-y-8" data-testid="teams-page">
    <header class="space-y-3">
      <p class="inline-flex items-center gap-2 rounded-full border border-[var(--border)] bg-[var(--bg-tertiary)] px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--accent-gold)]">
        <span aria-hidden="true">★</span>
        Draw Ceremony
      </p>

      <div>
        <h1 class="font-display text-4xl uppercase tracking-[0.06em] text-[var(--text-primary)] md:text-5xl">
          Tournament Teams
        </h1>
        <p class="mt-2 text-base text-[var(--text-secondary)] md:text-lg">
          Champions League Group Stage - Season 2025/26
        </p>
      </div>
    </header>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-5">
      <TeamCard v-for="(team, index) in teams" :key="team.id" :team="team" :index="index" />
    </div>

    <button
      type="button"
      data-testid="generate-fixture-button"
      class="cta-primary w-full rounded-xl px-5 py-4 font-display text-lg uppercase tracking-[0.14em] text-[#001018] disabled:cursor-not-allowed disabled:opacity-70"
      :disabled="isGenerating"
      @click="generateFixtures"
    >
      <span class="inline-flex items-center gap-2">
        <span v-if="isGenerating" class="loading-spinner" aria-hidden="true"></span>
        {{ isGenerating ? 'Generating...' : 'Generate Fixture' }}
      </span>
    </button>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import TeamCard from '../../Components/TeamCard.vue'

defineProps({
  teams: {
    type: Array,
    required: true,
  },
})

const isGenerating = ref(false)

const generateFixtures = () => {
  if (isGenerating.value) {
    return
  }

  isGenerating.value = true

  router.post(route('fixtures.generate'), {}, {
    preserveScroll: true,
    onFinish: () => {
      isGenerating.value = false
    },
  })
}
</script>
