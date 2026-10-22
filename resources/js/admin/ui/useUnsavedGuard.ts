import { onBeforeUnmount, onMounted, ref, watch, type Ref } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import { useConfirm } from './useConfirm'

export function useUnsavedGuard(values: Ref<unknown>) {
  const dirty = ref(false)
  let initial = JSON.stringify(values.value)
  watch(values, (next) => { dirty.value = JSON.stringify(next) !== initial }, { deep: true })
  const beforeUnload = (event: BeforeUnloadEvent) => {
    if (!dirty.value) return
    event.preventDefault()
    event.returnValue = ''
  }
  onMounted(() => window.addEventListener('beforeunload', beforeUnload))
  onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload))
  const askConfirm = useConfirm()
  onBeforeRouteLeave(() => !dirty.value || askConfirm('You have unsaved changes. Leave this page?', 'Discard changes?'))
  const reset = () => { initial = JSON.stringify(values.value); dirty.value = false }
  return { dirty, reset }
}
