<template>
  <Transition :name="name" mode="out-in" appear>
    <slot />
  </Transition>
</template>

<script setup>
defineProps({
  name: {
    type: String,
    default: 'fade-slide',
  },
})
</script>
