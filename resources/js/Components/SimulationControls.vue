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
