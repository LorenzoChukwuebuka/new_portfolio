<template>
  <TransitionRoot :show="open" as="template">
    <Dialog class="relative z-[var(--z-modal)]" @close="finish(false)">
      <TransitionChild as="template" enter="duration-150 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-100 ease-in" leave-from="opacity-100" leave-to="opacity-0"><div class="fixed inset-0 bg-black/50" /></TransitionChild>
      <div class="fixed inset-0 overflow-y-auto p-4"><div class="flex min-h-full items-center justify-center">
        <TransitionChild as="template" enter="duration-150 ease-out" enter-from="opacity-0 translate-y-2" enter-to="opacity-100 translate-y-0" leave="duration-100 ease-in" leave-from="opacity-100 translate-y-0" leave-to="opacity-0 translate-y-2">
          <DialogPanel class="w-full max-w-md rounded-lg border border-adm-line bg-adm-surface p-6 text-adm-ink shadow-adm-card">
            <DialogTitle class="text-lg font-semibold">{{ title }}</DialogTitle><p class="mt-2 text-sm text-adm-ink-muted">{{ message }}</p>
            <div class="mt-6 flex justify-end gap-2"><button class="rounded-md border border-adm-line px-4 py-2 text-sm" @click="finish(false)">Cancel</button><button class="rounded-md bg-adm-danger-bg px-4 py-2 text-sm font-semibold text-adm-danger-ink" @click="finish(true)">Confirm</button></div>
          </DialogPanel>
        </TransitionChild>
      </div></div>
    </Dialog>
  </TransitionRoot>
</template>
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
const open = ref(false)
const title = ref('Please confirm')
const message = ref('')
let resolver: ((answer: boolean) => void) | null = null
function show(event: Event) {
  const detail = (event as CustomEvent<{ title: string; message: string; resolve: (answer: boolean) => void }>).detail
  title.value = detail.title
  message.value = detail.message
  resolver = detail.resolve
  open.value = true
}
function finish(answer: boolean) { open.value = false; resolver?.(answer); resolver = null }
onMounted(() => window.addEventListener('admin:confirm', show))
onBeforeUnmount(() => window.removeEventListener('admin:confirm', show))
</script>
