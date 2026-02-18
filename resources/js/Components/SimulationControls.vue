<template>
  <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
    <button
      type="button"
      data-testid="play-week-button"
      class="control control-next"
      :disabled="isBusy || allWeeksPlayed"
      @click="$emit('playWeek')"
    >
      <span v-if="isPlaying">Playing...</span>
      <span v-else>Play Week {{ nextWeek }}</span>
    </button>

    <button
      type="button"
      data-testid="play-all-button"
      class="control control-all"
      :disabled="isBusy || allWeeksPlayed"
      @click="$emit('playAll')"
    >
      <span v-if="isPlaying">Playing...</span>
      <span v-else>Play All Weeks</span>
    </button>

    <button
      type="button"
      data-testid="reset-button"
      class="control control-reset"
      :disabled="isBusy"
      @click="confirmReset"
    >
      <span v-if="isResetting">Resetting...</span>
      <span v-else>Reset</span>
    </button>

    <ConfirmDialog
      :visible="showConfirmDialog"
      title="Reset Simulation"
      message="This will clear all match results, standings, and predictions. Are you sure?"
      confirm-label="Reset All"
      variant="destructive"
      @confirm="onConfirmReset"
      @cancel="onCancelReset"
    />
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import ConfirmDialog from './ConfirmDialog.vue'

const props = defineProps({
  allWeeksPlayed: {
    type: Boolean,
    required: true,
  },
  currentWeek: {
    type: Number,
    required: true,
  },
  isPlaying: {
    type: Boolean,
    required: true,
  },
  isResetting: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['playWeek', 'playAll', 'reset'])

const nextWeek = computed(() => Math.min(6, props.currentWeek + 1))
const isBusy = computed(() => props.isPlaying || props.isResetting)

const showConfirmDialog = ref(false)

const confirmReset = () => {
  if (isBusy.value || showConfirmDialog.value) {
    return
  }

  showConfirmDialog.value = true
}

const onConfirmReset = () => {
  showConfirmDialog.value = false
  emit('reset')
}

const onCancelReset = () => {
  showConfirmDialog.value = false
}
</script>

<style scoped>
.control {
  border-radius: 0.65rem;
  padding: 0.85rem 1rem;
  font-family: var(--font-display);
  font-size: 1rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  transition: transform 140ms ease, filter 140ms ease, box-shadow 140ms ease;
}

.control:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.control-next {
  background: linear-gradient(90deg, var(--accent-primary) 0%, #22d3ee 100%);
  color: #001018;
  box-shadow: 0 0 18px var(--glow);
}

.control-all {
  background: linear-gradient(90deg, #fde68a 0%, var(--accent-gold) 100%);
  color: #231800;
}

.control-reset {
  background: transparent;
  border: 1px solid var(--accent-red);
  color: #fca5a5;
}

.control:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(1.03);
}

.control-reset:hover:not(:disabled) {
  box-shadow: 0 0 14px rgba(239, 68, 68, 0.24);
}

@media (prefers-reduced-motion: reduce) {
  .control {
    transition: none;
  }
}
</style>
