<template>
  <Layout page-title="Projects">
    <div class="space-y-6">
      <!-- Header -->
      <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h3 class="text-lg font-semibold text-slate-950">Project Library</h3>
          <p class="mt-1 text-sm text-slate-500">Manage portfolio case studies and featured work.</p>
        </div>
        <router-link
          to="/admin/projects/create"
          class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
        >
          Add Project
        </router-link>
      </div>
      </div>

      <AdminLoader
        v-if="loading"
        title="Loading projects"
        message="Fetching portfolio projects and media."
        :rows="6"
      />

      <!-- Projects Grid -->
      <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="project in projects"
          :key="project.id"
          class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div
            v-if=" project.media.find((m:any) => m.type === 'thumbnail')?.thumbnail"
            class="h-48 bg-slate-200"
          >
            <!--@ts-ignore -->
            <img
              :src="'/storage/'+project.media.find((m:any) => m.type === 'thumbnail')?.thumbnail"
              :alt="project.title"
              class="w-full h-full object-cover"
            />
          </div>
          <div v-else class="h-48 bg-slate-100 flex items-center justify-center">
            <span class="text-sm font-medium text-slate-400">No thumbnail</span>
          </div>

          <div class="p-6">
            <div class="flex items-start justify-between mb-2">
              <h3 class="text-lg font-semibold text-slate-950">
                {{ project.title }}
              </h3>
              <span
                v-if="project.is_featured"
                class="rounded bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700"
              >
                Featured
              </span>
            </div>

            <p class="text-sm text-slate-500 mb-4 line-clamp-2">
              {{ project.description }}
            </p>

            <div class="flex items-center space-x-2 mb-4">
              <span
                class="rounded px-2 py-1 text-xs font-medium capitalize"
                :class="getStatusColor(project.status)"
              >
                {{ project.status.replace('-', ' ') }}
              </span>
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="tech in project.technologies.slice(0, 3)"
                :key="tech"
                class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700"
              >
                {{ tech }}
              </span>
              <span
                v-if="project.technologies.length > 3"
                class="px-2 py-1 text-xs text-slate-500"
              >
                +{{ project.technologies.length - 3 }}
              </span>
            </div>

            <div
              class="flex justify-between items-center pt-4 border-t border-slate-200"
            >
              <div class="flex space-x-2">
                <a
                  v-if="project.project_link"
                  :href="project.project_link"
                  target="_blank"
                  class="text-sky-700 hover:text-sky-900"
                  title="View Project"
                >
                  <svg
                    class="w-5 h-5"
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
                </a>
                <a
                  v-if="project.github_link"
                  :href="project.github_link"
                  target="_blank"
                  class="text-slate-600 hover:text-slate-900"
                  title="View on GitHub"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                    />
                  </svg>
                </a>
              </div>

              <div class="flex space-x-2">
                <router-link
                  :to="`/admin/projects/${project.slug}/edit`"
                  class="text-sm font-medium text-sky-700 hover:text-sky-900"
                >
                  Edit
                </router-link>
                <button
                  @click="deleteProject(project)"
                  class="text-sm font-medium text-rose-600 hover:text-rose-800"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import Layout from "../components/Layout.vue";
import AdminLoader from "../components/AdminLoader.vue";
import api from "../utils/api";
import type { Project } from "../types";

const projects = ref<Project[]>([]);
const loading = ref(true);

onMounted(async () => {
  await fetchProjects();
});

const fetchProjects = async () => {
  loading.value = true;
  try {
    const { data } = await api.get("/admin/projects");
    projects.value = data.data;
  } finally {
    loading.value = false;
  }
};

const getStatusColor = (status: string) => {
  const colors = {
    completed: "bg-emerald-50 text-emerald-700",
    "in-progress": "bg-sky-50 text-sky-700",
    archived: "bg-slate-100 text-slate-700",
  };
  return colors[status as keyof typeof colors] || "bg-slate-100 text-slate-700";
};

const deleteProject = async (project: Project) => {
  if (!confirm(`Are you sure you want to delete "${project.title}"?`)) return;

  try {
    await api.delete(`/admin/projects/${project.slug}`);
    await fetchProjects();
  } catch (error) {
    alert((error as any).response?.data?.message || "An error occurred");
  }
};
</script>
