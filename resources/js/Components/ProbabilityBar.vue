<template>
  <div class="space-y-1.5" data-testid="probability-bar">
    <div class="flex items-center justify-between gap-3">
      <span class="min-w-0 truncate font-semibold text-[var(--text-primary)]">{{ team.name }}</span>
      <span class="font-mono-broadcast text-sm text-[var(--text-secondary)]">{{ formattedProbability }}%</span>
    </div>

    <div class="h-2.5 overflow-hidden rounded-full bg-[var(--bg-tertiary)]">
      <div class="probability-fill h-full" :style="fillStyle"></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  team: {
    type: Object,
    required: true,
  },
  probability: {
    type: Number,
    required: true,
  },
})

const safeProbability = computed(() => Math.max(0, Math.min(100, Number(props.probability) || 0)))

const formattedProbability = computed(() => safeProbability.value.toFixed(1))

const fillColor = computed(() => {
  if (safeProbability.value >= 50) {
    return 'linear-gradient(90deg, #fde047 0%, var(--accent-gold) 100%)'
  }

  if (safeProbability.value <= 5) {
    return 'linear-gradient(90deg, #f87171 0%, var(--accent-red) 100%)'
  }

  return 'linear-gradient(90deg, var(--accent-primary) 0%, #7dd3fc 55%, var(--accent-gold) 100%)'
})

const fillStyle = computed(() => ({
  width: `${safeProbability.value}%`,
  background: fillColor.value,
}))
</script>
