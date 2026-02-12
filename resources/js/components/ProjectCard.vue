<template>
  <article
    class="group bg-zinc-900 border border-zinc-800 overflow-hidden hover:border-amber-400/60 transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_20px_60px_rgba(0,0,0,0.6)] cursor-pointer"
    @click="navigate"
  >
    <!-- Image / Number area -->
    <div
      class="relative w-full h-56 overflow-hidden bg-gradient-to-br from-zinc-800 to-zinc-900"
    >
      <!-- Thumbnail if available -->
      <img
        v-if="project.thumbnailUrl"
        :src="project.thumbnailUrl"
        :alt="project.title"
        class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:opacity-80 group-hover:scale-105 transition-all duration-500"
      />

      <!-- Fallback number -->
      <span
        class="absolute inset-0 flex items-center justify-center font-['Playfair_Display',serif] text-7xl font-black text-amber-400/20 group-hover:text-amber-400/40 group-hover:scale-110 transition-all duration-500 select-none"
        :class="{ 'opacity-0': project.thumbnailUrl }"
      >
        {{ project.number }}
      </span>

      <!-- Hover overlay -->
      <div
        class="absolute inset-0 bg-amber-400/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500"
      />

      <!-- Year badge -->
      <span
        class="absolute top-4 right-4 font-mono text-xs text-zinc-400 bg-zinc-950/70 px-2 py-1 rounded"
      >
        {{ project.year }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-7">
      <h3
        class="font-['Playfair_Display',serif] text-2xl font-bold text-zinc-100 mb-3 group-hover:text-amber-400 transition-colors duration-300"
      >
        {{ project.title }}
      </h3>
      <p class="text-zinc-400 text-sm leading-relaxed mb-5">
        {{ project.description }}
      </p>

      <!-- Tags (technologies) -->
      <div class="flex flex-wrap gap-2 mb-5">
        <span
          v-for="tag in project.tags"
          :key="tag"
          class="px-2.5 py-1 text-xs font-mono font-medium text-amber-400/80 bg-amber-400/10 border border-amber-400/20 rounded-sm"
        >
          {{ tag }}
        </span>
      </div>

      <!-- Links -->
      <div class="flex items-center gap-4 pt-4 border-t border-zinc-800">
        <a
          v-if="project.githubUrl"
          :href="project.githubUrl"
          target="_blank"
          rel="noopener"
          class="flex items-center gap-1.5 text-xs text-zinc-500 hover:text-zinc-300 transition-colors font-mono"
          @click.stop
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12"
            />
          </svg>
          Source
        </a>
        <a
          v-if="project.liveUrl"
          :href="project.liveUrl"
          target="_blank"
          rel="noopener"
          class="flex items-center gap-1.5 text-xs text-zinc-500 hover:text-amber-400 transition-colors font-mono"
          @click.stop
        >
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
            />
          </svg>
          Live Demo
        </a>
        <span
          class="ml-auto text-xs text-amber-400/60 font-mono group-hover:text-amber-400 transition-colors"
        >
          View Details →
        </span>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import type { Project } from "./../types";

const props = defineProps<{
  project: Project;
}>();

function navigate() {
  window.location.href = `/projects/${props.project.slug}`;
}
</script>
