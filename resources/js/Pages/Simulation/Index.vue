<template>
  <section class="space-y-6" data-testid="simulation-page">
    <header>
      <p class="text-xs uppercase tracking-[0.12em] text-[var(--text-secondary)]">Champions League Simulator</p>
      <h1 class="font-display text-4xl uppercase tracking-[0.06em] text-[var(--text-primary)] md:text-5xl">
        Week {{ currentWeek }} of 6
      </h1>
    </header>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-5">
      <div class="xl:col-span-3">
        <LeagueTable :standings="standings" />
      </div>

      <div class="space-y-5 xl:col-span-2">
        <WeeklyMatches
          :fixtures="fixtures"
          :selected-week="selectedWeek"
          @update:selected-week="selectedWeek = $event"
          @edit-match="openEditor"
        />

        <PredictionPanel :predictions="predictions" />
      </div>
    </div>

    <SimulationControls
      :all-weeks-played="allWeeksPlayed"
      :current-week="currentWeek"
      :is-playing="isPlaying"
      :is-resetting="isResetting"
      @play-week="playWeek"
      @play-all="playAll"
      @reset="resetAll"
    />

    <MatchResultEditor :match="editingMatch" @close="editingMatch = null" />
  </section>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import LeagueTable from '../../Components/LeagueTable.vue'
import MatchResultEditor from '../../Components/MatchResultEditor.vue'
import PredictionPanel from '../../Components/PredictionPanel.vue'
import SimulationControls from '../../Components/SimulationControls.vue'
import WeeklyMatches from '../../Components/WeeklyMatches.vue'

const props = defineProps({
  standings: {
    type: Array,
    required: true,
  },
  fixtures: {
    type: Array,
    required: true,
  },
  currentWeek: {
    type: Number,
    required: true,
  },
  predictions: {
    type: Array,
    default: null,
  },
  allWeeksPlayed: {
    type: Boolean,
    required: true,
  },
})

const selectedWeek = ref(props.currentWeek > 0 ? props.currentWeek : 1)
const editingMatch = ref(null)
const isPlaying = ref(false)
const isResetting = ref(false)

watch(
  () => props.currentWeek,
  currentWeek => {
    selectedWeek.value = currentWeek > 0 ? currentWeek : 1
  }
)

const withPlayState = callback => {
  if (isPlaying.value || isResetting.value) {
    return
  }

  isPlaying.value = true

  callback({
    preserveScroll: true,
    onFinish: () => {
      isPlaying.value = false
    },
  })
}

const playWeek = () => {
  withPlayState(options => {
    router.post(route('simulation.play-week'), {}, options)
  })
}

const playAll = () => {
  withPlayState(options => {
    router.post(route('simulation.play-all'), {}, options)
  })
}

const resetAll = () => {
  if (isPlaying.value || isResetting.value) {
    return
  }

  isResetting.value = true

  router.post(route('simulation.reset'), {}, {
    preserveScroll: true,
    onFinish: () => {
      isResetting.value = false
    },
  })
}

const openEditor = match => {
  editingMatch.value = match
}
</script>
