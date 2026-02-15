<template>
  <Layout page-title="Projects">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Manage your portfolio projects</p>
        </div>
        <router-link
          to="/admin/projects/create"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Add Project
        </router-link>
      </div>

      <!-- Projects Grid -->
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="project in projects"
          :key="project.id"
          class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow"
        >
          <div
            v-if=" project.media.find((m:any) => m.type === 'thumbnail')?.thumbnail"
            class="h-48 bg-gray-200"
          >
            <!--@ts-ignore -->
            <img
              :src="'/storage/'+project.media.find((m:any) => m.type === 'thumbnail')?.thumbnail"
              :alt="project.title"
              class="w-full h-full object-cover"
            />
          </div>
          <div v-else class="h-48 bg-gray-100 flex items-center justify-center">
            <span class="text-gray-400">No thumbnail</span>
          </div>

          <div class="p-6">
            <div class="flex items-start justify-between mb-2">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ project.title }}
              </h3>
              <span
                v-if="project.is_featured"
                class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded"
              >
                Featured
              </span>
            </div>

            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
              {{ project.description }}
            </p>

            <div class="flex items-center space-x-2 mb-4">
              <span
                class="px-2 py-1 text-xs font-medium rounded capitalize"
                :class="getStatusColor(project.status)"
              >
                <!-- {{ project.status.replace('-', ' ') }} -->
              </span>
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="tech in project.technologies.slice(0, 3)"
                :key="tech"
                class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded"
              >
                {{ tech }}
              </span>
              <span
                v-if="project.technologies.length > 3"
                class="px-2 py-1 text-xs text-gray-500"
              >
                +{{ project.technologies.length - 3 }}
              </span>
            </div>

            <div
              class="flex justify-between items-center pt-4 border-t border-gray-200"
            >
              <div class="flex space-x-2">
                <a
                  v-if="project.project_link"
                  :href="project.project_link"
                  target="_blank"
                  class="text-blue-600 hover:text-blue-700"
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
                  class="text-gray-600 hover:text-gray-700"
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
                  class="text-sm text-blue-600 hover:text-blue-700"
                >
                  Edit
                </router-link>
                <button
                  @click="deleteProject(project)"
                  class="text-sm text-red-600 hover:text-red-700"
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
import api from "../utils/api";
import type { Project } from "../types";

const projects = ref<Project[]>([]);

onMounted(async () => {
  await fetchProjects();
});

const fetchProjects = async () => {
  const { data } = await api.get("/admin/projects");
  console.log(data.data);
  projects.value = data.data;
};

const getStatusColor = (status: string) => {
  const colors = {
    completed: "bg-green-100 text-green-700",
    "in-progress": "bg-blue-100 text-blue-700",
    archived: "bg-gray-100 text-gray-700",
  };
  return colors[status as keyof typeof colors] || "bg-gray-100 text-gray-700";
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
