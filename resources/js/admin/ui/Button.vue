<template><button :type="type" :disabled="disabled || loading" class="inline-flex items-center justify-center gap-2 rounded-md font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-adm-focus disabled:cursor-not-allowed disabled:opacity-55" :class="[variants[variant], sizes[size]]"><span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true"/><slot/></button></template>
<script setup lang="ts">
withDefaults(defineProps<{ type?: 'button' | 'submit' | 'reset'; variant?: 'primary' | 'secondary' | 'ghost' | 'danger'; size?: 'sm' | 'md'; disabled?: boolean; loading?: boolean }>(), { type: 'button', variant: 'primary', size: 'md' })
const variants = { primary: 'bg-adm-accent-bg text-adm-accent-ink hover:brightness-95', secondary: 'border border-adm-line bg-adm-surface text-adm-ink hover:bg-adm-surface-2', ghost: 'text-adm-ink-muted hover:bg-adm-surface-2 hover:text-adm-ink', danger: 'bg-adm-danger-bg text-adm-danger-ink hover:brightness-95' }
const sizes = { sm: 'min-h-9 px-3 py-1.5 text-xs', md: 'min-h-11 px-4 py-2 text-sm' }
</script>
