<template>
  <div class="rounded-lg border border-adm-line bg-adm-surface p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-adm-ink-muted">
          {{ title }}
        </p>
        <p class="mt-3 text-3xl font-semibold tracking-tight text-adm-ink">
          {{ value }}
        </p>
        <p class="mt-1 text-sm text-adm-ink-muted">{{ subtitle }}</p>
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
    blue: 'text-adm-info-ink',
    green: 'text-adm-success-ink',
    yellow: 'text-adm-warning-ink',
    purple: 'text-adm-info-ink',
    red: 'text-adm-danger-ink',
  };
  return colors[props.color || 'blue'];
});

const bgColorClass = computed(() => {
  const colors = {
    blue: 'bg-adm-info-bg',
    green: 'bg-adm-success-bg',
    yellow: 'bg-adm-warning-bg',
    purple: 'bg-adm-info-bg',
    red: 'bg-adm-danger-bg',
  };
  return colors[props.color || 'blue'];
});
</script>
