<template>
  <div class="fixed right-4 top-4 z-[var(--z-toast)] grid w-[min(24rem,calc(100vw-2rem))] gap-2" aria-live="polite" aria-relevant="additions">
    <TransitionGroup name="toast">
      <div v-for="item in items" :key="item.id" class="rounded-lg border p-4 shadow-adm-card" :class="kindClass(item.kind)" role="status">
        <div class="flex items-start justify-between gap-3"><p class="text-sm font-medium">{{ item.message }}</p><button class="min-h-8 min-w-8" :aria-label="'Dismiss ' + item.kind + ' message'" @click="remove(item.id)">×</button></div>
      </div>
    </TransitionGroup>
  </div>
</template>
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import type { ToastKind, ToastMessage } from './useToast'
const items = ref<ToastMessage[]>([])
let nextId = 0
function add(event: Event) {
  const detail = (event as CustomEvent<{ message: string; kind: ToastKind }>).detail
  const item = { ...detail, id: ++nextId }
  items.value.push(item)
  window.setTimeout(() => remove(item.id), 4500)
}
function remove(id: number) { items.value = items.value.filter((item) => item.id !== id) }
function kindClass(kind: ToastKind) {
  return kind === 'error' ? 'border-adm-danger-ink/30 bg-adm-danger-bg text-adm-danger-ink' : kind === 'info' ? 'border-adm-info-ink/30 bg-adm-info-bg text-adm-info-ink' : 'border-adm-success-ink/30 bg-adm-success-bg text-adm-success-ink'
}
onMounted(() => window.addEventListener('admin:toast', add))
onBeforeUnmount(() => window.removeEventListener('admin:toast', add))
</script>
