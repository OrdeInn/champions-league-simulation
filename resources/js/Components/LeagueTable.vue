<template>
  <section class="card-surface overflow-hidden" data-testid="league-table">
    <header class="border-b border-[var(--border)] bg-[var(--bg-tertiary)] px-4 py-3">
      <h2 class="font-display text-2xl uppercase tracking-[0.08em] text-[var(--text-primary)]">League Table</h2>
    </header>

    <div class="overflow-x-auto">
      <table class="w-full min-w-[680px] text-sm">
        <thead class="bg-[var(--bg-tertiary)]/70 text-[var(--text-secondary)]">
          <tr class="font-display uppercase tracking-[0.08em]">
            <th class="px-3 py-2 text-left">#</th>
            <th class="px-3 py-2 text-left">Team</th>
            <th class="px-2 py-2 text-center">P</th>
            <th class="px-2 py-2 text-center">W</th>
            <th class="px-2 py-2 text-center">D</th>
            <th class="px-2 py-2 text-center">L</th>
            <th class="px-2 py-2 text-center">GF</th>
            <th class="px-2 py-2 text-center">GA</th>
            <th class="px-2 py-2 text-center">GD</th>
            <th class="px-3 py-2 text-right">PTS</th>
          </tr>
        </thead>

        <TransitionGroup tag="tbody" name="table-row">
          <tr
            v-for="(row, rowIndex) in standings"
            :key="row.team.id"
            data-testid="standing-row"
            class="border-t border-[var(--border)]/70 transition-colors hover:bg-[var(--bg-tertiary)]/45"
            :class="rowClasses(rowIndex)"
          >
            <td class="px-3 py-2 font-mono-broadcast">{{ row.position }}</td>
            <td class="px-3 py-2 font-semibold">{{ row.team.name }}</td>
            <td class="px-2 py-2 text-center" :class="statClass(row.played)">{{ row.played }}</td>
            <td class="px-2 py-2 text-center" :class="statClass(row.won)">{{ row.won }}</td>
            <td class="px-2 py-2 text-center" :class="statClass(row.drawn)">{{ row.drawn }}</td>
            <td class="px-2 py-2 text-center" :class="statClass(row.lost)">{{ row.lost }}</td>
            <td class="px-2 py-2 text-center" :class="statClass(row.goals_for)">{{ row.goals_for }}</td>
            <td class="px-2 py-2 text-center" :class="statClass(row.goals_against)">{{ row.goals_against }}</td>
            <td class="px-2 py-2 text-center" :class="goalDiffClass(row.goal_difference)">{{ formatGoalDiff(row.goal_difference) }}</td>
            <td class="px-3 py-2 text-right font-mono-broadcast text-base font-bold text-[var(--accent-primary)]">
              <span class="inline-flex items-center gap-1">
                <span v-if="isSeasonComplete && rowIndex === 0" aria-hidden="true">🏆</span>
                {{ row.points }}
              </span>
            </td>
          </tr>
        </TransitionGroup>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  standings: {
    type: Array,
    required: true,
  },
})

const isSeasonComplete = computed(() => props.standings.length > 0 && props.standings[0].played >= 6)

const rowClasses = rowIndex => {
  if (rowIndex === 0) {
    return 'border-l-[3px] border-l-[var(--accent-gold)]'
  }

  if (rowIndex === props.standings.length - 1) {
    return 'border-l-[3px] border-l-[var(--accent-red)]'
  }

  return ''
}

const statClass = value => (value === 0 ? 'text-[var(--text-muted)]' : 'text-[var(--text-primary)]')

const goalDiffClass = value => {
  if (value > 0) {
    return 'text-[var(--accent-green)]'
  }

  if (value < 0) {
    return 'text-[var(--accent-red)]'
  }

  return 'text-[var(--text-muted)]'
}

const formatGoalDiff = value => (value > 0 ? `+${value}` : `${value}`)
</script>

<style scoped>
.table-row-move,
.table-row-enter-active,
.table-row-leave-active {
  transition: transform 300ms ease, opacity 300ms ease;
}

.table-row-enter-from,
.table-row-leave-to {
  opacity: 0;
  transform: translate3d(0, 8px, 0);
}

@media (prefers-reduced-motion: reduce) {
  .table-row-move,
  .table-row-enter-active,
  .table-row-leave-active {
    transition: opacity 80ms linear;
  }

  .table-row-enter-from,
  .table-row-leave-to {
    transform: none;
  }
}
</style>
