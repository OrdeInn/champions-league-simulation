<template>
  <section class="card-surface p-4 md:p-5" data-testid="weekly-matches">
    <header class="mb-4 flex items-center justify-between">
      <button
        type="button"
        data-testid="week-prev"
        class="week-nav"
        :disabled="selectedWeek <= 1"
        @click="$emit('update:selectedWeek', selectedWeek - 1)"
      >
        ‹
      </button>

      <h2 class="font-display text-2xl uppercase tracking-[0.08em] text-[var(--text-primary)]">
        Week {{ selectedWeek }} Matches
      </h2>

      <button
        type="button"
        data-testid="week-next"
        class="week-nav"
        :disabled="selectedWeek >= maxWeek"
        @click="$emit('update:selectedWeek', selectedWeek + 1)"
      >
        ›
      </button>
    </header>

    <p v-if="selectedWeek === 1 && !hasPlayedMatches" class="mb-3 text-sm text-[var(--text-secondary)]">
      Play your first week to reveal results.
    </p>

    <div class="space-y-2.5">
      <template v-for="match in weekMatches" :key="match.id">
        <div
          v-if="match.is_played"
          :data-testid="`played-match-${match.id}`"
          class="match-row flex w-full items-center gap-3 rounded-lg border border-[var(--border)] bg-[var(--bg-tertiary)]/55 px-3 py-2"
        >
          <span class="min-w-0 flex-1 truncate text-right font-semibold text-[var(--text-primary)]" :title="teamName(match, 'home')">
            {{ teamName(match, 'home') }}
          </span>

          <span class="inline-flex min-w-[80px] items-center justify-center gap-1 font-mono-broadcast text-base text-[var(--accent-primary)]">
            <span>{{ animatedScore(match, 'home') }}</span>
            <span class="text-[var(--text-secondary)]">-</span>
            <span>{{ animatedScore(match, 'away') }}</span>
          </span>

          <span class="min-w-0 flex-1 truncate font-semibold text-[var(--text-primary)]" :title="teamName(match, 'away')">
            {{ teamName(match, 'away') }}
          </span>

          <button
            type="button"
            :data-testid="`edit-match-${match.id}`"
            class="edit-match-btn"
            :aria-label="`Edit match result: ${teamName(match, 'home')} vs ${teamName(match, 'away')}`"
            title="Edit result"
            @click="onMatchClick(match)"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M12 20h9" />
              <path d="m16.5 3.5 4 4L7 21H3v-4z" />
            </svg>
          </button>
        </div>

        <div
          v-else
          :data-testid="`unplayed-match-${match.id}`"
          class="match-row flex items-center gap-3 rounded-lg border border-[var(--border)] bg-[var(--bg-tertiary)]/20 px-3 py-2 opacity-80"
        >
          <span class="min-w-0 flex-1 truncate text-right font-semibold text-[var(--text-primary)]" :title="teamName(match, 'home')">
            {{ teamName(match, 'home') }}
          </span>

          <span class="min-w-[80px] text-center text-sm italic text-[var(--text-muted)]">vs</span>

          <span class="min-w-0 flex-1 truncate font-semibold text-[var(--text-primary)]" :title="teamName(match, 'away')">
            {{ teamName(match, 'away') }}
          </span>
        </div>
      </template>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, watch } from 'vue'

const props = defineProps({
  fixtures: {
    type: Array,
    required: true,
  },
  selectedWeek: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['update:selectedWeek', 'editMatch'])

const animatedScores = reactive({})
const activeTimers = Object.create(null)
const maxWeek = computed(() => props.fixtures.length || 6)

const selectedFixture = computed(() => {
  return props.fixtures.find(fixture => fixture.week === props.selectedWeek) || null
})

const weekMatches = computed(() => selectedFixture.value?.matches || [])

const hasPlayedMatches = computed(() => weekMatches.value.some(match => match.is_played))

const teamName = (match, side) => {
  if (side === 'home') {
    return match.home_team?.name || match.homeTeam?.name || 'Home'
  }

  return match.away_team?.name || match.awayTeam?.name || 'Away'
}

const scoreKey = (matchId, side) => `${matchId}-${side}`

const animateScoreTo = (key, target) => {
  const safeTarget = Math.max(0, Number(target) || 0)
  clearInterval(activeTimers[key])
  delete activeTimers[key]
  animatedScores[key] = safeTarget
}

watch(
  () => props.fixtures,
  () => {
    weekMatches.value.forEach(match => {
      if (!match.is_played) {
        return
      }

      animateScoreTo(scoreKey(match.id, 'home'), match.home_score)
      animateScoreTo(scoreKey(match.id, 'away'), match.away_score)
    })
  },
  { deep: true }
)

const animatedScore = (match, side) => {
  const key = scoreKey(match.id, side)
  if (Object.prototype.hasOwnProperty.call(animatedScores, key)) {
    return animatedScores[key]
  }

  if (side === 'home') {
    return match.home_score ?? 0
  }

  return match.away_score ?? 0
}

onBeforeUnmount(() => {
  Object.values(activeTimers).forEach(timer => clearInterval(timer))
})

const onMatchClick = match => {
  if (!match.is_played) {
    return
  }

  emit('editMatch', match)
}
</script>

<style scoped>
.week-nav {
  display: inline-flex;
  height: 2rem;
  width: 2rem;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  border: 1px solid var(--border);
  color: var(--text-secondary);
  transition: color 140ms ease, border-color 140ms ease, box-shadow 140ms ease;
}

.week-nav:hover:not(:disabled) {
  color: var(--accent-primary);
  border-color: var(--accent-primary);
  box-shadow: 0 0 12px var(--glow);
}

.week-nav:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.edit-match-btn {
  display: inline-flex;
  height: 1.75rem;
  width: 1.75rem;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 0.375rem;
  border: 1px solid var(--border);
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  transition: color 140ms ease, border-color 140ms ease, box-shadow 140ms ease, background 140ms ease, transform 140ms ease;
}

.edit-match-btn:hover {
  color: var(--accent-primary);
  border-color: var(--accent-primary);
  background: rgba(0, 229, 255, 0.08);
  background: color-mix(in srgb, var(--accent-primary) 8%, transparent);
  box-shadow: 0 0 12px var(--glow);
}

.edit-match-btn:focus-visible {
  color: var(--accent-primary);
  border-color: var(--accent-primary);
  background: rgba(0, 229, 255, 0.08);
  background: color-mix(in srgb, var(--accent-primary) 8%, transparent);
  box-shadow: 0 0 12px var(--glow);
  outline: 2px solid var(--accent-primary);
  outline-offset: 2px;
}

.edit-match-btn:active {
  transform: scale(0.95);
}

@media (prefers-reduced-motion: reduce) {
  .week-nav,
  .edit-match-btn {
    transition: none;
  }

  .edit-match-btn:active {
    transform: none !important;
  }
}
</style>
