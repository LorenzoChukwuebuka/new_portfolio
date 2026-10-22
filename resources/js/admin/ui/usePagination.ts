import { computed, ref } from 'vue'

export function usePagination(initial = 1) {
  const page = ref(initial)
  const perPage = ref(15)
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })
  const canNext = computed(() => page.value < meta.value.last_page)
  const canPrevious = computed(() => page.value > 1)
  function update(data: { current_page?: number; last_page?: number; total?: number; per_page?: number }) {
    meta.value = { current_page: data.current_page || 1, last_page: data.last_page || 1, total: data.total || 0, per_page: data.per_page || perPage.value }
    page.value = meta.value.current_page
  }
  return { page, perPage, meta, canNext, canPrevious, update }
}
