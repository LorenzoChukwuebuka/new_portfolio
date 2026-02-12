<template>
  <Layout page-title="CVs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-600">Manage your CV uploads</p>

        <button
          @click="openModal()"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Add CV
        </button>
      </div>

      <!-- CV List -->
      <div class="bg-white rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  Title
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  Version
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  Active
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                >
                  Actions
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
              <tr v-for="cv in cvs" :key="cv.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                  {{ cv.title }}
                </td>

                <td class="px-6 py-4 text-sm text-gray-500">
                  {{ cv.version }}
                </td>

                <td class="px-6 py-4 text-sm">
                  <span
                    class="px-2 py-1 text-xs rounded bg-green-100 text-green-700"
                    v-if="cv.is_active"
                  >
                    Active
                  </span>
                </td>

                <td class="px-6 py-4 text-right text-sm space-x-2">
                  <button
                    @click="openModal(cv)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </button>

                  <button
                    @click="deleteCv(cv)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <TransitionRoot :show="isOpen" as="template">
      <Dialog @close="closeModal" class="relative z-50">
        <TransitionChild
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black bg-opacity-25" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <TransitionChild
              enter="ease-out duration-300"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel
                class="w-full max-w-md bg-white p-6 rounded-lg shadow-xl"
              >
                <DialogTitle class="text-lg font-semibold mb-4">
                  {{ editingCv ? "Edit CV" : "Add CV" }}
                </DialogTitle>

                <form @submit.prevent="saveCv" class="space-y-4">
                  <!-- Title -->
                  <input
                    v-model="form.title"
                    required
                    type="text"
                    placeholder="CV Title"
                    class="w-full px-3 text-gray-900 py-2 border rounded-lg"
                  />

                  <!-- Version -->
                  <input
                    v-model="form.version"
                    type="text"
                    placeholder="Version"
                    class="w-full px-3 text-gray-900 py-2 border rounded-lg"
                  />

                  <!-- Description -->
                  <textarea
                    v-model="form.description"
                    rows="3"
                    placeholder="Description"
                    class="w-full px-3 text-gray-900 py-2 border rounded-lg"
                  />

                  <!-- File -->
                  <input
                    type="file"
                    @change="handleFile"
                    v-if="!editingCv"
                    class="w-full text-gray-900"
                  />

                  <!-- Active -->
                  <label class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.is_active" />
                    <span class="text-sm text-gray-900">Active CV</span>
                  </label>

                  <div class="flex justify-end space-x-3 pt-4">
                    <button
                      type="button"
                      @click="closeModal"
                      class="px-4 py-2 border rounded-lg"
                    >
                      Cancel
                    </button>

                    <button
                      type="submit"
                      :disabled="saving"
                      class="px-4 py-2 bg-blue-600 text-white rounded-lg"
                    >
                      {{ saving ? "Saving..." : "Save" }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </Layout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild,
} from "@headlessui/vue";
import Layout from "../components/Layout.vue";
import api from "../utils/api";

const cvs = ref<any[]>([]);
const isOpen = ref(false);
const editingCv = ref<any | null>(null);
const saving = ref(false);
const file = ref<File | null>(null);

const form = ref<any>({
  title: "",
  version: "",
  description: "",
  is_active: false,
});

onMounted(fetchCvs);

async function fetchCvs() {
  const { data } = await api.get("/admin/cv");
  cvs.value = data.data;
}

function handleFile(e: any) {
  file.value = e.target.files[0];
}

function openModal(cv?: any) {
  if (cv) {
    editingCv.value = cv;
    form.value = { ...cv };
  } else {
    editingCv.value = null;
    form.value = { title: "", version: "", description: "", is_active: false };
  }
  isOpen.value = true;
}

function closeModal() {
  isOpen.value = false;
  file.value = null;
}

async function saveCv() {
  saving.value = true;

  const fd = new FormData();
  Object.entries(form.value).forEach(([k, v]) => {
    if (v !== null && v !== undefined) {
      fd.append(k, String(v));
    }
  });

  if (file.value) fd.append("file", file.value);

  try {
    if (editingCv.value) {
      await api.post(`/admin/cv/${editingCv.value.id}?_method=PUT`, fd, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    } else {
      await api.post("/admin/cv", fd, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    }

    await fetchCvs();
    closeModal();
  } finally {
    saving.value = false;
  }
}

async function deleteCv(cv: any) {
  if (!confirm(`Delete "${cv.title}"?`)) return;
  await api.delete(`/admin/cv/${cv.id}`);
  await fetchCvs();
}
</script>
