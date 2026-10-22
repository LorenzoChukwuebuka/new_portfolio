<template>
  <div class="rounded-lg border border-adm-line bg-adm-surface p-6 shadow-sm">
    <div class="flex items-center gap-3">
      <span
        class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-adm-sidebar-bg text-adm-sidebar-ink"
        aria-hidden="true"
      >
        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="opacity-80"
            fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
          />
        </svg>
      </span>
      <div>
        <p class="text-sm font-semibold text-adm-ink">{{ title }}</p>
        <p class="mt-0.5 text-sm text-adm-ink-muted">{{ message }}</p>
      </div>
    </div>

    <div v-if="rows > 0" class="mt-6 space-y-3">
      <div
        v-for="row in rows"
        :key="row"
        class="h-12 animate-pulse rounded-md bg-adm-surface-2"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string;
    message?: string;
    rows?: number;
  }>(),
  {
    title: "Loading content",
    message: "Fetching the latest data from the database.",
    rows: 4,
  },
);
</script>
