<template><span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold" :class="variants[variant]"><slot/></span></template>
<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'neutral' | 'success' | 'warning' | 'danger' | 'info' }>(), { variant: 'neutral' })
const variants = { neutral: 'bg-adm-surface-2 text-adm-ink-muted', success: 'bg-adm-success-bg text-adm-success-ink', warning: 'bg-adm-warning-bg text-adm-warning-ink', danger: 'bg-adm-danger-bg text-adm-danger-ink', info: 'bg-adm-info-bg text-adm-info-ink' }
</script>
