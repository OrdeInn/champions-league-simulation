<template>
  <div data-testid="match-pairing" class="match-pairing flex items-center gap-3 rounded-lg border border-[var(--border)] bg-[linear-gradient(90deg,rgba(0,229,255,0.06)_0%,rgba(255,215,0,0.06)_100%)] px-3 py-2" :style="{ '--pair-delay': `${animationDelay}ms` }">
    <span class="min-w-0 flex-1 truncate font-display text-base uppercase tracking-[0.04em] text-[var(--text-primary)]" :title="match.home_team?.name || match.homeTeam?.name">
      {{ match.home_team?.name || match.homeTeam?.name }}
    </span>

    <span class="vs-badge inline-flex shrink-0 items-center rounded-md border border-[var(--accent-primary)]/40 bg-[color:rgba(0,229,255,0.16)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--accent-primary)]">
      VS
    </span>

    <span class="min-w-0 flex-1 truncate text-right font-display text-base uppercase tracking-[0.04em] text-[var(--text-primary)]" :title="match.away_team?.name || match.awayTeam?.name">
      {{ match.away_team?.name || match.awayTeam?.name }}
    </span>
  </div>
</template>

<script setup>
defineProps({
  match: {
    type: Object,
    required: true,
  },
  animationDelay: {
    type: Number,
    default: 0,
  },
})
</script>

<style scoped>
.match-pairing {
  opacity: 0;
  transform: translate3d(0, 10px, 0);
}

.vs-badge {
  box-shadow: 0 0 12px var(--glow);
}

@media (prefers-reduced-motion: no-preference) {
  .match-pairing {
    animation: pairing-enter 320ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
    animation-delay: var(--pair-delay);
  }

  .vs-badge {
    animation: vs-pulse 620ms ease-out;
    animation-delay: var(--pair-delay);
  }
}

@media (prefers-reduced-motion: reduce) {
  .match-pairing {
    opacity: 1;
    transform: none;
    animation: none;
  }

  .vs-badge {
    animation: none;
  }
}

@keyframes pairing-enter {
  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@keyframes vs-pulse {
  0% {
    box-shadow: 0 0 0 rgba(0, 229, 255, 0);
  }

  45% {
    box-shadow: 0 0 18px rgba(0, 229, 255, 0.35);
  }

  100% {
    box-shadow: 0 0 12px var(--glow);
  }
}
</style>
