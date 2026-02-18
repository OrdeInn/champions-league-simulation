<template>
  <article
    data-testid="team-card"
    class="team-card card-surface p-5"
    :style="{ '--stagger-delay': `${index * 100}ms` }"
  >
    <div class="mb-4 flex items-start justify-between gap-3">
      <h3 class="font-display text-2xl uppercase leading-none tracking-[0.04em] text-[var(--text-primary)]">
        {{ team.name }}
      </h3>
      <span class="inline-flex items-center rounded-md border border-[var(--border)] bg-[var(--bg-tertiary)] px-2 py-1 font-mono-broadcast text-xs tracking-[0.08em] text-[var(--text-secondary)]">
        {{ team.short_name }}
      </span>
    </div>

    <div class="space-y-2">
      <div class="flex items-center justify-between text-sm text-[var(--text-secondary)]">
        <span>Power Rating</span>
        <strong class="font-mono-broadcast text-base text-[var(--text-primary)]">{{ team.power }}</strong>
      </div>

      <div class="h-3 overflow-hidden rounded-full border border-[var(--border)] bg-[color:rgba(26,34,54,0.65)]">
        <div data-testid="team-power-fill" class="power-fill h-full" :style="powerStyle"></div>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
  team: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
})

const entered = ref(false)

onMounted(() => {
  entered.value = true
})

const powerStyle = computed(() => ({
  width: entered.value ? `${Math.max(0, Math.min(100, props.team.power))}%` : '0%',
}))
</script>
