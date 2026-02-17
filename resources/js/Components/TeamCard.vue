<template>
  <article
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
        <div class="power-fill h-full" :style="powerStyle"></div>
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

<style scoped>
.team-card {
  opacity: 0;
  transform: translate3d(0, 14px, 0);
}

.power-fill {
  background: linear-gradient(90deg, var(--accent-primary) 0%, #7dd3fc 56%, var(--accent-gold) 100%);
  box-shadow: 0 0 16px rgba(0, 229, 255, 0.35);
  transition: width 500ms cubic-bezier(0.2, 1, 0.3, 1);
}

@media (prefers-reduced-motion: no-preference) {
  .team-card {
    animation: card-enter 380ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
    animation-delay: var(--stagger-delay);
  }
}

@media (prefers-reduced-motion: reduce) {
  .team-card {
    opacity: 1;
    transform: none;
    animation: none;
  }

  .power-fill {
    transition: width 80ms linear;
  }
}

@keyframes card-enter {
  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}
</style>
