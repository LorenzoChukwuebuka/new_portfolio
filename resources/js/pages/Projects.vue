<template>
  <section id="work" class="py-32 relative">
    <!-- Subtle separator line -->
    <div
      class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-zinc-700/50 to-transparent"
    />

    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <!-- Section Header -->
      <div class="flex items-end justify-between mb-16">
        <div class="fade-in">
          <p
            class="font-mono text-amber-400 text-xs tracking-widest uppercase mb-3"
          >
            Selected Work
          </p>
          <h2
            class="font-['Playfair_Display',serif] text-4xl sm:text-5xl font-black text-zinc-100 leading-tight"
          >
            Projects I've Built
          </h2>
        </div>
        <!-- <a
          href="/projects"
          class="hidden md:inline-flex items-center gap-2 text-sm font-mono text-zinc-500 hover:text-amber-400 transition-colors group"
        >
          All projects
          <svg
            class="w-4 h-4 group-hover:translate-x-1 transition-transform"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"
            />
          </svg>
        </a> -->
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div
          v-for="n in 2"
          :key="n"
          class="bg-zinc-900 border border-zinc-800 overflow-hidden animate-pulse"
        >
          <div class="w-full h-56 bg-zinc-800" />
          <div class="p-7 space-y-3">
            <div class="h-6 bg-zinc-800 rounded w-2/3" />
            <div class="h-4 bg-zinc-800 rounded w-full" />
            <div class="h-4 bg-zinc-800 rounded w-4/5" />
          </div>
        </div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="text-center py-16">
        <p class="font-mono text-sm text-zinc-500">Failed to load projects.</p>
      </div>

      <!-- Featured Projects Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 ">
        <ProjectCard
          v-for="project in projects"
          :key="project.id"
          :project="project"
        />
      </div>

      <!-- Mobile "All projects" -->
      <div class="mt-10 flex justify-center md:hidden fade-in">
        <a
          href="/projects"
          class="inline-flex items-center gap-2 px-6 py-3 border border-zinc-700 text-zinc-300 text-sm font-semibold hover:border-amber-400 hover:text-amber-400 transition-all duration-300"
        >
          View All Projects
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
              d="M17 8l4 4m0 0l-4 4m4-4H3"
            />
          </svg>
        </a>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from "vue";
import ProjectCard from "./../components/ProjectCard.vue";
import type { Project } from "./../types";
import api from "../admin/utils/api";
import { normalizeProject } from "../utils/helper";

const projects = ref<Project[]>([]);
const loading = ref(true);
const error = ref(false);

const featuredProjects = computed(() =>
  projects.value.filter((p) => p.featured)
);

const fetchProjects = async () => {
  loading.value = true;
  error.value = false;
  try {
    const { data } = await api.get("/projects");
    projects.value = data.data.map(normalizeProject);
  } catch (e) {
    console.log((e as any).message)
    error.value = true;
  } finally {
    loading.value = false;
  }
};

onMounted(fetchProjects);
</script>
