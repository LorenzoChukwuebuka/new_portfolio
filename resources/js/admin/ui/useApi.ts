import { onBeforeUnmount, ref } from 'vue'
import { apiErrorMessage } from '../utils/api'

export function useApi<T>() {
  const data = ref<T>()
  const loading = ref(false)
  const error = ref('')
  let controller: AbortController | undefined
  async function run(request: (signal: AbortSignal) => Promise<T>) {
    controller?.abort()
    controller = new AbortController()
    loading.value = true
    error.value = ''
    try { data.value = await request(controller.signal); return data.value }
    catch (cause) { if (!controller.signal.aborted) error.value = apiErrorMessage(cause); throw cause }
    finally { if (!controller.signal.aborted) loading.value = false }
  }
  onBeforeUnmount(() => controller?.abort())
  return { data, loading, error, run }
}
