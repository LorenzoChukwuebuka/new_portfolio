<template><button class="inline-flex min-h-10 items-center gap-2 rounded-md border border-adm-line px-3 text-sm text-adm-ink hover:bg-adm-surface-2" type="button" @click="toggle"><span aria-hidden="true">{{ theme === 'dark' ? '☼' : '☾' }}</span><span>{{ theme === 'dark' ? 'Light theme' : 'Dark theme' }}</span></button></template>
<script setup lang="ts">
import { onMounted, ref } from 'vue'
const theme = ref<'light' | 'dark'>('light')
onMounted(() => {
  const saved = localStorage.getItem('admin_theme')
  theme.value = saved === 'dark' || saved === 'light' ? saved : (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
  document.documentElement.dataset.theme = theme.value
})
function toggle() { theme.value = theme.value === 'dark' ? 'light' : 'dark'; document.documentElement.dataset.theme = theme.value; localStorage.setItem('admin_theme', theme.value) }
</script>
