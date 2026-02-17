<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full">
      <h1 class="text-3xl font-bold text-gray-900 mb-4">
        {{ title }}
      </h1>
      <p class="text-gray-600 mb-6">
        Your Laravel + Vue 3 + Inertia.js + Tailwind CSS application is ready!
      </p>
      <div class="text-sm text-gray-500 space-y-2">
        <p>✓ Vite HMR is working</p>
        <p>✓ Inertia.js is connected</p>
        <p>✓ Tailwind CSS is configured</p>
        <p>✓ Docker environment is running</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const title = computed(() => 'Champions League Simulation')
</script>
