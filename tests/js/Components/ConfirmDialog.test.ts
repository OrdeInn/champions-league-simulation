import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'
import ConfirmDialog from '../../../resources/js/Components/ConfirmDialog.vue'

describe('ConfirmDialog', () => {
  const defaultProps = {
    visible: true,
    title: 'Test Title',
    message: 'Test message',
  }

  const mountedWrappers: ReturnType<typeof mount>[] = []

  const mountDialog = (props = {}) => {
    const wrapper = mount(ConfirmDialog, {
      props: { ...defaultProps, ...props },
      global: { stubs: { teleport: true } },
    })
    mountedWrappers.push(wrapper)
    return wrapper
  }

  afterEach(() => {
    mountedWrappers.forEach(w => w.unmount())
    mountedWrappers.length = 0
  })

  it('renders when visible is true', () => {
    const wrapper = mountDialog({ visible: true })
    expect(wrapper.find('[data-testid="confirm-dialog-confirm"]').exists()).toBe(true)
  })

  it('hidden when visible is false', () => {
    const wrapper = mountDialog({ visible: false })
    expect(wrapper.find('[data-testid="confirm-dialog-confirm"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="confirm-dialog-cancel"]').exists()).toBe(false)
  })

  it('displays title and message', () => {
    const wrapper = mountDialog({ title: 'Confirm Delete', message: 'Are you sure?' })
    expect(wrapper.text()).toContain('Confirm Delete')
    expect(wrapper.text()).toContain('Are you sure?')
  })

  it('displays custom button labels', () => {
    const wrapper = mountDialog({ confirmLabel: 'Yes, Delete', cancelLabel: 'No, Keep' })
    expect(wrapper.get('[data-testid="confirm-dialog-confirm"]').text()).toBe('Yes, Delete')
    expect(wrapper.get('[data-testid="confirm-dialog-cancel"]').text()).toBe('No, Keep')
  })

  it('emits cancel on cancel button click', async () => {
    const wrapper = mountDialog()
    await wrapper.get('[data-testid="confirm-dialog-cancel"]').trigger('click')
    expect(wrapper.emitted('cancel')).toBeTruthy()
  })

  it('emits confirm on confirm button click', async () => {
    const wrapper = mountDialog()
    await wrapper.get('[data-testid="confirm-dialog-confirm"]').trigger('click')
    expect(wrapper.emitted('confirm')).toBeTruthy()
  })

  it('emits cancel on overlay click', async () => {
    const wrapper = mountDialog()
    await wrapper.get('[aria-modal="true"]').trigger('click')
    expect(wrapper.emitted('cancel')).toBeTruthy()
  })

  it('uses destructive variant styling', () => {
    const wrapper = mountDialog({ variant: 'destructive' })
    const confirmButton = wrapper.get('[data-testid="confirm-dialog-confirm"]')
    expect(confirmButton.classes()).toContain('confirm-destructive')
    expect(confirmButton.classes()).toContain('bg-[var(--accent-red)]')
  })

  it('global Enter keydown does not emit confirm', async () => {
    const wrapper = mountDialog()
    await nextTick()
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }))
    await nextTick()
    expect(wrapper.emitted('confirm')).toBeFalsy()
  })

  it('click emits confirm exactly once', async () => {
    const wrapper = mountDialog()
    await wrapper.get('[data-testid="confirm-dialog-confirm"]').trigger('click')
    expect(wrapper.emitted('confirm')).toHaveLength(1)
  })

  it('has correct ARIA attributes for destructive variant', () => {
    const wrapper = mountDialog({ variant: 'destructive' })
    const overlay = wrapper.get('[aria-modal="true"]')
    expect(overlay.attributes('role')).toBe('alertdialog')
    expect(overlay.attributes('aria-modal')).toBe('true')
    expect(overlay.attributes('aria-labelledby')).toBe('confirm-dialog-title')
    expect(overlay.attributes('aria-describedby')).toBe('confirm-dialog-message')
  })

  it('has correct ARIA attributes for default variant', () => {
    const wrapper = mountDialog({ variant: 'default' })
    const overlay = wrapper.get('[aria-modal="true"]')
    expect(overlay.attributes('role')).toBe('dialog')
    expect(overlay.attributes('aria-modal')).toBe('true')
    expect(overlay.attributes('aria-labelledby')).toBe('confirm-dialog-title')
    expect(overlay.attributes('aria-describedby')).toBe('confirm-dialog-message')
  })

  it('both buttons have type=button', () => {
    const wrapper = mountDialog()
    expect(wrapper.get('[data-testid="confirm-dialog-confirm"]').attributes('type')).toBe('button')
    expect(wrapper.get('[data-testid="confirm-dialog-cancel"]').attributes('type')).toBe('button')
  })
})
