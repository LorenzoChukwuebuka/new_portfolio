<template><div class="animate-pulse rounded-md bg-adm-surface-2" :class="sizeClass" :aria-label="label" role="status"><span class="sr-only">{{ label }}</span></div></template>
<script setup lang="ts">withDefaults(defineProps<{sizeClass?:string;label?:string}>(),{sizeClass:'h-5 w-full',label:'Loading'})</script>
