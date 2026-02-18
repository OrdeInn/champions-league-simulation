<template>
  <Teleport to="body">
    <Transition name="fade-slide">
      <div
        v-if="visible"
        :role="variant === 'destructive' ? 'alertdialog' : 'dialog'"
        aria-modal="true"
        aria-labelledby="confirm-dialog-title"
        aria-describedby="confirm-dialog-message"
        class="fixed inset-0 z-50 flex items-center justify-center bg-[color:rgba(2,6,23,0.7)] px-4 backdrop-blur-sm"
        @click.self="emit('cancel')"
      >
        <div
          class="w-full max-w-sm rounded-xl border border-[var(--border)] bg-[var(--bg-secondary)] p-5"
          :class="variant === 'destructive' ? 'border-t-2 border-t-[var(--accent-red)]' : 'border-t-2 border-t-[var(--accent-primary)]'"
        >
          <div class="mb-4 flex justify-center">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-full"
              :class="variant === 'destructive' ? 'bg-[rgba(239,68,68,0.1)]' : 'bg-[rgba(0,229,255,0.1)]'"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-5 w-5"
                :class="variant === 'destructive' ? 'text-[var(--accent-red)]' : 'text-[var(--accent-primary)]'"
              >
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                <line x1="12" y1="9" x2="12" y2="13" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
              </svg>
            </div>
          </div>

          <h3 id="confirm-dialog-title" class="mb-2 text-center font-display text-xl uppercase tracking-[0.07em] text-[var(--text-primary)]">
            {{ title }}
          </h3>

          <p id="confirm-dialog-message" class="mb-5 text-center text-sm text-[var(--text-secondary)]">
            {{ message }}
          </p>

          <div class="flex justify-end gap-2">
            <button
              ref="cancelButtonRef"
              type="button"
              data-testid="confirm-dialog-cancel"
              class="rounded-md border border-[var(--border)] px-3 py-2 text-sm text-[var(--text-secondary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--border)]"
              @click="emit('cancel')"
            >
              {{ cancelLabel }}
            </button>

            <button
              ref="confirmButtonRef"
              type="button"
              data-testid="confirm-dialog-confirm"
              class="rounded-md px-3 py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
              :class="variant === 'destructive'
                ? 'bg-[var(--accent-red)] text-white confirm-destructive focus-visible:outline-[var(--accent-red)]'
                : 'bg-[var(--accent-primary)] text-[#001018] focus-visible:outline-[var(--accent-primary)]'"
              @click="emit('confirm')"
            >
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'

const props = defineProps({
  visible: {
    type: Boolean,
    required: true,
  },
  title: {
    type: String,
    default: 'Confirm',
  },
  message: {
    type: String,
    default: '',
  },
  confirmLabel: {
    type: String,
    default: 'Confirm',
  },
  cancelLabel: {
    type: String,
    default: 'Cancel',
  },
  variant: {
    type: String,
    default: 'destructive',
    validator: (value) => ['destructive', 'default'].includes(value),
  },
})

const emit = defineEmits(['confirm', 'cancel'])

const cancelButtonRef = ref(null)
const confirmButtonRef = ref(null)

watch(
  () => props.visible,
  async (isVisible, _oldValue, onCleanup) => {
    if (!isVisible) {
      return
    }

    const previouslyFocusedElement = document.activeElement
    const originalBodyOverflow = document.body.style.overflow

    document.body.style.overflow = 'hidden'
    document.addEventListener('keydown', handleKeydown)

    let invalidated = false
    onCleanup(() => {
      invalidated = true
      document.body.style.overflow = originalBodyOverflow
      document.removeEventListener('keydown', handleKeydown)
      if (previouslyFocusedElement instanceof HTMLElement && previouslyFocusedElement.isConnected) {
        previouslyFocusedElement.focus()
      }
    })

    await nextTick()
    if (!invalidated) {
      cancelButtonRef.value?.focus()
    }
  },
  { immediate: true }
)

function handleKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault()
    emit('cancel')
  } else if (event.key === 'Tab') {
    event.preventDefault()
    if (document.activeElement === cancelButtonRef.value) {
      confirmButtonRef.value?.focus()
    } else {
      cancelButtonRef.value?.focus()
    }
  }
}
</script>
