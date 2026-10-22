import { ref } from 'vue'

export function useFormErrors() {
  const errors = ref<Record<string, string>>({})
  const clear = (field?: string) => field ? delete errors.value[field] : errors.value = {}
  const capture = (error: unknown) => {
    const response = (error as { response?: { data?: { errors?: Record<string, string[]> } } })?.response
    errors.value = Object.fromEntries(Object.entries(response?.data?.errors || {}).map(([key, messages]) => [key, messages[0] || 'Invalid value.']))
    return errors.value
  }
  return { errors, clear, capture }
}
