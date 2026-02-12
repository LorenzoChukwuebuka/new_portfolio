<template>
  <div class="min-h-screen bg-zinc-950 text-zinc-100">
    <AppNavbar />

    <main class="max-w-5xl mx-auto px-6 lg:px-8 pt-32 pb-24">
      <!-- Breadcrumb -->
      <nav
        class="flex items-center gap-2 text-xs font-mono text-zinc-600 mb-12"
      >
        <a href="/" class="hover:text-zinc-400 transition-colors">home</a>
        <span>/</span>
        <a href="/projects" class="hover:text-zinc-400 transition-colors"
          >projects</a
        >
        <span>/</span>
        <span class="text-amber-400">{{ project?.slug ?? "..." }}</span>
      </nav>

      <!-- Loading skeleton -->
      <div v-if="loading" class="animate-pulse space-y-8">
        <div class="h-24 w-24 bg-zinc-800 rounded" />
        <div class="h-10 bg-zinc-800 rounded w-2/3" />
        <div class="h-5 bg-zinc-800 rounded w-full" />
        <div class="h-5 bg-zinc-800 rounded w-4/5" />
        <div
          class="grid grid-cols-4 gap-6 p-6 bg-zinc-900 border border-zinc-800"
        >
          <div v-for="n in 4" :key="n" class="h-10 bg-zinc-800 rounded" />
        </div>
      </div>

      <!-- Error / 404 -->
      <div v-else-if="error || !project" class="text-center py-32">
        <p
          class="font-['Playfair_Display',serif] text-6xl font-black text-zinc-800 mb-4"
        >
          404
        </p>
        <p class="text-zinc-600 font-mono text-sm mb-6">Project not found.</p>
        <a
          href="/projects"
          class="text-amber-400 text-sm font-mono hover:text-amber-300 transition-colors"
        >
          ← Back to projects
        </a>
      </div>

      <template v-else>
        <!-- Project Number + Title -->
        <div class="mb-10">
          <span
            class="font-['Playfair_Display',serif] text-8xl font-black text-amber-400/15 block mb-2 leading-none select-none"
          >
            {{ project.number }}
          </span>
          <p
            class="font-mono text-amber-400 text-xs tracking-widest uppercase mb-3"
          >
            {{ project.year }} — {{ project.tags[0] }}
          </p>
          <h1
            class="font-['Playfair_Display',serif] text-4xl sm:text-5xl font-black text-zinc-100 leading-tight mb-5"
          >
            {{ project.title }}
          </h1>
          <p class="text-zinc-400 text-lg leading-relaxed max-w-2xl">
            {{ project.description }}
          </p>
        </div>

        <!-- Metadata Row -->
        <div
          class="grid grid-cols-2 sm:grid-cols-4 gap-6 mb-12 p-6 bg-zinc-900 border border-zinc-800"
        >
          <div>
            <p
              class="text-xs font-mono text-zinc-600 uppercase tracking-widest mb-2"
            >
              Year
            </p>
            <p class="text-sm text-zinc-300 font-mono">{{ project.year }}</p>
          </div>
          <div>
            <p
              class="text-xs font-mono text-zinc-600 uppercase tracking-widest mb-2"
            >
              Status
            </p>
            <p
              class="text-sm text-emerald-400 font-mono flex items-center gap-1.5"
            >
              <span
                class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"
              />
              Live
            </p>
          </div>
          <div>
            <p
              class="text-xs font-mono text-zinc-600 uppercase tracking-widest mb-2"
            >
              Type
            </p>
            <p class="text-sm text-zinc-300 font-mono">
              {{ project.featured ? "Featured" : "Side Project" }}
            </p>
          </div>
          <div>
            <p
              class="text-xs font-mono text-zinc-600 uppercase tracking-widest mb-2"
            >
              Stack
            </p>
            <p class="text-sm text-zinc-300 font-mono truncate">
              {{ project.tags.slice(0, 2).join(", ") }}
            </p>
          </div>
        </div>

        <!-- Tech Stack -->
        <div class="mb-12">
          <h2
            class="font-mono text-xs text-zinc-600 uppercase tracking-widest mb-4"
          >
            Full Tech Stack
          </h2>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="tech in project.tags"
              :key="tech"
              class="px-3 py-1.5 text-xs font-mono font-medium text-amber-400/80 bg-amber-400/10 border border-amber-400/20"
            >
              {{ tech }}
            </span>
          </div>
        </div>

        <!-- Long Description -->
        <div class="mb-12">
          <h2
            class="font-['Playfair_Display',serif] text-2xl font-bold text-zinc-100 mb-5"
          >
            About This Project
          </h2>
          <div class="prose prose-invert prose-amber max-w-none">
            <p class="text-zinc-400 leading-loose text-base">
              {{ project.longDescription }}
            </p>
          </div>
        </div>

        <!-- CTA Links -->
        <div class="flex flex-wrap gap-4 pt-8 border-t border-zinc-800">
          <a
            v-if="project.liveUrl"
            :href="project.liveUrl"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-2 px-6 py-3 bg-amber-400 text-zinc-950 text-sm font-semibold hover:bg-amber-300 transition-all duration-300 hover:-translate-y-0.5"
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
          <a
            v-if="project.githubUrl"
            :href="project.githubUrl"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-2 px-6 py-3 border border-zinc-700 text-zinc-300 text-sm font-semibold hover:border-zinc-500 hover:text-zinc-100 transition-all duration-300"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12"
              />
            </svg>
            View Source
          </a>
          <a
            href="/projects"
            class="inline-flex items-center gap-2 px-6 py-3 text-zinc-500 text-sm font-mono hover:text-zinc-300 transition-colors ml-auto"
          >
            ← All Projects
          </a>
        </div>

        <!-- Next/Prev Navigation -->
        <div
          v-if="prevProject || nextProject"
          class="grid grid-cols-2 gap-4 mt-16 pt-8 border-t border-zinc-800"
        >
          <a
            v-if="prevProject"
            :href="`/projects/${prevProject.slug}`"
            class="group p-5 bg-zinc-900 border border-zinc-800 hover:border-zinc-600 transition-colors"
          >
            <p class="text-xs font-mono text-zinc-600 mb-2">← Previous</p>
            <p
              class="text-sm text-zinc-300 group-hover:text-zinc-100 transition-colors font-medium"
            >
              {{ prevProject.title }}
            </p>
          </a>
          <a
            v-if="nextProject"
            :href="`/projects/${nextProject.slug}`"
            class="group p-5 bg-zinc-900 border border-zinc-800 hover:border-zinc-600 transition-colors text-right col-start-2"
          >
            <p class="text-xs font-mono text-zinc-600 mb-2">Next →</p>
            <p
              class="text-sm text-zinc-300 group-hover:text-zinc-100 transition-colors font-medium"
            >
              {{ nextProject.title }}
            </p>
          </a>
        </div>
      </template>
    </main>

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import AppNavbar from "../components/AppNavbar.vue";
import AppFooter from "../components/AppFooter.vue";
import type { Project, ApiProject } from "../types";
import api from "../admin/utils/api";
import { normalizeProject } from "../utils/helper";

const props = defineProps<{
  slug?: string;
}>();

const project = ref<Project | null>(null);
const prevProject = ref<{ slug: string; title: string } | null>(null);
const nextProject = ref<{ slug: string; title: string } | null>(null);
const loading = ref(true);
const error = ref(false);

onMounted(async () => {
  const slug = props.slug ?? window.location.pathname.split("/").pop();

  console.log(slug,"sluuuuugggg")

  try {
    const { data } = await api.get(`/projects/${slug}`);
    project.value = normalizeProject(data);
    prevProject.value = data.prev ?? null;
    nextProject.value = data.next ?? null;
  } catch (e) {
    error.value = true;
  } finally {
    loading.value = false;
  }
});
</script>
