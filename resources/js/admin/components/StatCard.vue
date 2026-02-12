<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="mt-2 text-3xl font-bold" :class="colorClass">{{ value }}</p>
        <p class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
      </div>
      <div class="p-3 rounded-full" :class="bgColorClass">
        <div class="w-8 h-8" :class="colorClass">
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
    blue: 'text-blue-600',
    green: 'text-green-600',
    yellow: 'text-yellow-600',
    purple: 'text-purple-600',
    red: 'text-red-600',
  };
  return colors[props.color || 'blue'];
});

const bgColorClass = computed(() => {
  const colors = {
    blue: 'bg-blue-100',
    green: 'bg-green-100',
    yellow: 'bg-yellow-100',
    purple: 'bg-purple-100',
    red: 'bg-red-100',
  };
  return colors[props.color || 'blue'];
});
</script>
