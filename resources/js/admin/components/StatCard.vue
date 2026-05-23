<template>
  <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
          {{ title }}
        </p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">
          {{ value }}
        </p>
        <p class="mt-1 text-sm text-slate-500">{{ subtitle }}</p>
      </div>
      <div class="rounded-md p-3" :class="bgColorClass">
        <div class="h-6 w-6" :class="colorClass">
          <slot name="icon" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  title: string;
  value: number | string;
  subtitle?: string;
  color?: 'blue' | 'green' | 'yellow' | 'purple' | 'red';
}>();

const colorClass = computed(() => {
  const colors = {
    blue: 'text-sky-600',
    green: 'text-emerald-600',
    yellow: 'text-amber-600',
    purple: 'text-violet-600',
    red: 'text-rose-600',
  };
  return colors[props.color || 'blue'];
});

const bgColorClass = computed(() => {
  const colors = {
    blue: 'bg-sky-50',
    green: 'bg-emerald-50',
    yellow: 'bg-amber-50',
    purple: 'bg-violet-50',
    red: 'bg-rose-50',
  };
  return colors[props.color || 'blue'];
});
</script>
