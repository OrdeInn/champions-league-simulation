<template>
  <div class="app-shell min-h-screen bg-[var(--bg-primary)] text-[var(--text-primary)]">
    <div class="starfield" aria-hidden="true"></div>
    <AppHeader />

    <main class="relative mx-auto w-full max-w-6xl px-4 py-8 md:py-10">
      <PageTransition>
        <div :key="page.component">
          <slot />
        </div>
      </PageTransition>
    </main>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import AppHeader from '../Components/AppHeader.vue'
import PageTransition from '../Components/PageTransition.vue'

const page = usePage()
</script>
