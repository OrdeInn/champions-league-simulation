<template>
  <div v-if="match" data-testid="match-result-editor" class="fixed inset-0 z-50 flex items-center justify-center bg-[color:rgba(2,6,23,0.7)] px-4 backdrop-blur-sm" @click.self="close">
    <div class="w-full max-w-md rounded-xl border border-[var(--border)] bg-[var(--bg-secondary)] p-5">
      <h3 class="mb-4 font-display text-2xl uppercase tracking-[0.07em] text-[var(--text-primary)]">Edit Match Result</h3>

      <div class="mb-4 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
        <span class="truncate text-right font-semibold text-[var(--text-primary)]">{{ teamName('home') }}</span>
        <span class="text-sm text-[var(--text-secondary)]">vs</span>
        <span class="truncate font-semibold text-[var(--text-primary)]">{{ teamName('away') }}</span>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-xs uppercase tracking-[0.1em] text-[var(--text-secondary)]" for="home-score">Home</label>
            <div class="flex items-center overflow-hidden rounded-md border border-[var(--border)] bg-[var(--bg-tertiary)] focus-within:border-[var(--accent-primary)]">
              <button type="button" :disabled="form.home_score <= 0" class="stepper-btn" @click="decrement('home_score')">−</button>
              <input id="home-score" data-testid="home-score-input" v-model.number="form.home_score" type="number" min="0" max="20" class="score-input w-full bg-transparent py-2 text-center font-mono-broadcast text-lg text-[var(--text-primary)] focus:outline-none" />
              <button type="button" :disabled="form.home_score >= 20" class="stepper-btn" @click="increment('home_score')">+</button>
            </div>
            <p v-if="form.errors.home_score" class="mt-1 text-xs text-[var(--accent-red)]">{{ form.errors.home_score }}</p>
          </div>

          <div>
            <label class="mb-1 block text-xs uppercase tracking-[0.1em] text-[var(--text-secondary)]" for="away-score">Away</label>
            <div class="flex items-center overflow-hidden rounded-md border border-[var(--border)] bg-[var(--bg-tertiary)] focus-within:border-[var(--accent-primary)]">
              <button type="button" :disabled="form.away_score <= 0" class="stepper-btn" @click="decrement('away_score')">−</button>
              <input id="away-score" data-testid="away-score-input" v-model.number="form.away_score" type="number" min="0" max="20" class="score-input w-full bg-transparent py-2 text-center font-mono-broadcast text-lg text-[var(--text-primary)] focus:outline-none" />
              <button type="button" :disabled="form.away_score >= 20" class="stepper-btn" @click="increment('away_score')">+</button>
            </div>
            <p v-if="form.errors.away_score" class="mt-1 text-xs text-[var(--accent-red)]">{{ form.errors.away_score }}</p>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" data-testid="cancel-edit-button" class="rounded-md border border-[var(--border)] px-3 py-2 text-sm text-[var(--text-secondary)]" @click="close">Cancel</button>
          <button type="submit" data-testid="save-edit-button" class="rounded-md bg-[var(--accent-primary)] px-3 py-2 text-sm font-semibold text-[#001018] disabled:opacity-60" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  match: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close'])

const form = useForm({
  home_score: 0,
  away_score: 0,
})

watch(
  () => props.match,
  match => {
    form.clearErrors()

    if (!match) {
      return
    }

    form.home_score = Number(match.home_score ?? 0)
    form.away_score = Number(match.away_score ?? 0)
  },
  { immediate: true }
)

const teamName = side => {
  if (!props.match) {
    return ''
  }

  if (side === 'home') {
    return props.match.home_team?.name || props.match.homeTeam?.name || 'Home'
  }

  return props.match.away_team?.name || props.match.awayTeam?.name || 'Away'
}

const increment = field => {
  if (form[field] < 20) form[field]++
}

const decrement = field => {
  if (form[field] > 0) form[field]--
}

const close = () => {
  if (form.processing) {
    return
  }

  emit('close')
}

const submit = () => {
  if (!props.match) {
    return
  }

  form.put(route('simulation.update-match', props.match.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('close')
      form.reset()
    },
  })
}
</script>

<style scoped>
.score-input::-webkit-inner-spin-button,
.score-input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.score-input {
  -moz-appearance: textfield;
  appearance: textfield;
}

.stepper-btn {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  height: 2.5rem;
  width: 2.25rem;
  font-size: 1.125rem;
  color: var(--text-muted);
  transition: background-color 120ms ease, color 120ms ease;
}

.stepper-btn:hover:not(:disabled) {
  background-color: var(--bg-secondary);
  color: var(--accent-primary);
}

.stepper-btn:disabled {
  cursor: not-allowed;
  opacity: 0.3;
}
</style>
