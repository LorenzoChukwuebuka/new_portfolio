<template><section class="min-w-0 rounded-lg border border-adm-line bg-adm-surface shadow-adm-card" :class="padding ? 'p-4 sm:p-5' : ''"><slot/></section></template>
<script setup lang="ts">withDefaults(defineProps<{ padding?: boolean }>(), { padding: true })</script>
