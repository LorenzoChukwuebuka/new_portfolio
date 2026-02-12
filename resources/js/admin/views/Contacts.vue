<template>
  <Layout page-title="Contact Messages">
    <div class="space-y-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="text-sm text-gray-600">Total Messages</div>
          <div class="text-2xl font-semibold text-gray-900">
            {{ stats.total }}
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="text-sm text-gray-600">Unread</div>
          <div class="text-2xl font-semibold text-orange-600">
            {{ stats.unread }}
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="text-sm text-gray-600">Today</div>
          <div class="text-2xl font-semibold text-blue-600">
            {{ stats.today }}
          </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
          <div class="text-sm text-gray-600">This Week</div>
          <div class="text-2xl font-semibold text-green-600">
            {{ stats.this_week }}
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col md:flex-row gap-4">
          <!-- Search -->
          <div class="flex-1">
            <input
              v-model="filters.search"
              @input="debouncedSearch"
              type="text"
              placeholder="Search by name, email, subject, or message..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Status Filter -->
          <div class="w-full md:w-48">
            <select
              v-model="filters.status"
              @change="fetchMessages"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Status</option>
              <option value="unread">Unread</option>
              <option value="read">Read</option>
              <option value="replied">Replied</option>
            </select>
          </div>
        </div>

        <!-- Bulk Actions -->
        <div
          v-if="selectedMessages.length > 0"
          class="mt-4 flex items-center gap-3 pt-4 border-t border-gray-200"
        >
          <span class="text-sm text-gray-600">
            {{ selectedMessages.length }} selected
          </span>
          <button
            @click="bulkMarkAsRead"
            class="px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100"
          >
            Mark as Read
          </button>
          <button
            @click="bulkDelete"
            class="px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100"
          >
            Delete
          </button>
          <button
            @click="selectedMessages = []"
            class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100"
          >
            Clear Selection
          </button>
        </div>
      </div>

      <!-- Messages Table -->
      <div class="bg-white rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left">
                  <input
                    type="checkbox"
                    @change="toggleSelectAll"
                    :checked="
                      selectedMessages.length === messages.data?.length &&
                      messages.data?.length > 0
                    "
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Status
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Name
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Email
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Subject
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Date
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="message in messages.data"
                :key="message.id"
                :class="[
                  'hover:bg-gray-50 cursor-pointer',
                  message.status === 'unread' ? 'bg-blue-50' : '',
                ]"
                @click="viewMessage(message)"
              >
                <td class="px-6 py-4" @click.stop>
                  <input
                    type="checkbox"
                    :value="message.id"
                    v-model="selectedMessages"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      message.status === 'unread'
                        ? 'bg-orange-100 text-orange-800'
                        : message.status === 'read'
                        ? 'bg-blue-100 text-blue-800'
                        : 'bg-green-100 text-green-800',
                    ]"
                  >
                    {{ message.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div
                    :class="[
                      'text-sm',
                      message.status === 'unread'
                        ? 'font-semibold text-gray-900'
                        : 'font-medium text-gray-900',
                    ]"
                  >
                    {{ message.name }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500">{{ message.email }}</div>
                </td>
                <td class="px-6 py-4">
                  <div
                    :class="[
                      'text-sm max-w-md truncate',
                      message.status === 'unread'
                        ? 'font-medium text-gray-900'
                        : 'text-gray-500',
                    ]"
                  >
                    {{ message.subject }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500">
                    {{ formatDate(message.created_at) }}
                  </div>
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                  @click.stop
                >
                  <button
                    @click="viewMessage(message)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    View
                  </button>
                  <button
                    v-if="message.status === 'unread'"
                    @click="markAsRead(message)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Mark Read
                  </button>
                  <button
                    v-if="message.status === 'read'"
                    @click="markAsReplied(message)"
                    class="text-purple-600 hover:text-purple-900"
                  >
                    Mark Replied
                  </button>
                  <button
                    @click="deleteMessage(message)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </td>
              </tr>

              <tr v-if="!messages.data || messages.data.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                  No messages found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="messages.data && messages.data.length > 0"
          class="px-6 py-4 border-t border-gray-200 flex items-center justify-between"
        >
          <div class="text-sm text-gray-700">
            Showing {{ messages.from }} to {{ messages.to }} of
            {{ messages.total }} results
          </div>
          <div class="flex gap-2">
            <button
              @click="changePage(messages.current_page - 1)"
              :disabled="!messages.prev_page_url"
              class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="changePage(messages.current_page + 1)"
              :disabled="!messages.next_page_url"
              class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Message Detail Modal -->
    <TransitionRoot :show="isModalOpen" as="template">
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
                class="w-full max-w-2xl transform overflow-hidden rounded-lg bg-white p-6 shadow-xl transition-all"
              >
                <div v-if="selectedMessage" class="space-y-4">
                  <!-- Header -->
                  <div class="flex items-start justify-between">
                    <DialogTitle
                      class="text-xl font-semibold text-gray-900 flex-1"
                    >
                      {{ selectedMessage.subject }}
                    </DialogTitle>
                    <span
                      :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-3',
                        selectedMessage.status === 'unread'
                          ? 'bg-orange-100 text-orange-800'
                          : selectedMessage.status === 'read'
                          ? 'bg-blue-100 text-blue-800'
                          : 'bg-green-100 text-green-800',
                      ]"
                    >
                      {{ selectedMessage.status }}
                    </span>
                  </div>

                  <!-- Sender Info -->
                  <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div class="flex items-center justify-between">
                      <div>
                        <div class="text-sm font-medium text-gray-900">
                          {{ selectedMessage.name }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ selectedMessage.email }}
                        </div>
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ formatDate(selectedMessage.created_at) }}
                      </div>
                    </div>
                    <div
                      v-if="selectedMessage.phone"
                      class="text-sm text-gray-600"
                    >
                      Phone: {{ selectedMessage.phone }}
                    </div>
                  </div>

                  <!-- Message Content -->
                  <div class="border-t border-gray-200 pt-4">
                    <div class="text-sm text-gray-900 whitespace-pre-wrap">
                      {{ selectedMessage.message }}
                    </div>
                  </div>

                  <!-- Read/Replied Info -->
                  <div
                    v-if="selectedMessage.read_at || selectedMessage.replied_at"
                    class="border-t border-gray-200 pt-4 text-xs text-gray-500 space-y-1"
                  >
                    <div v-if="selectedMessage.read_at">
                      Read at: {{ formatDate(selectedMessage.read_at) }}
                    </div>
                    <div v-if="selectedMessage.replied_at">
                      Replied at: {{ formatDate(selectedMessage.replied_at) }}
                    </div>
                  </div>

                  <!-- Actions -->
                  <div
                    class="flex justify-between items-center pt-4 border-t border-gray-200"
                  >
                    <a
                      :href="`mailto:${selectedMessage.email}?subject=Re: ${selectedMessage.subject}`"
                      class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                      target="_blank"
                    >
                      Reply via Email
                    </a>

                    <div class="flex gap-2">
                      <button
                        v-if="selectedMessage.status !== 'replied'"
                        @click="markAsReplied(selectedMessage)"
                        class="px-4 py-2 text-sm font-medium text-purple-600 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100"
                      >
                        Mark as Replied
                      </button>
                      <button
                        @click="deleteMessage(selectedMessage)"
                        class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100"
                      >
                        Delete
                      </button>
                      <button
                        @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                      >
                        Close
                      </button>
                    </div>
                  </div>
                </div>
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

interface ContactMessage {
  id: number;
  name: string;
  email: string;
  phone?: string;
  subject: string;
  message: string;
  status: "unread" | "read" | "replied";
  read_at?: string;
  replied_at?: string;
  created_at: string;
  updated_at: string;
}

interface PaginatedResponse {
  data: ContactMessage[];
  current_page: number;
  from: number;
  to: number;
  total: number;
  per_page: number;
  last_page: number;
  prev_page_url: string | null;
  next_page_url: string | null;
}

interface Stats {
  total: number;
  unread: number;
  read: number;
  replied: number;
  today: number;
  this_week: number;
}

const messages = ref<PaginatedResponse>({
  data: [],
  current_page: 1,
  from: 0,
  to: 0,
  total: 0,
  per_page: 15,
  last_page: 1,
  prev_page_url: null,
  next_page_url: null,
});

const stats = ref<Stats>({
  total: 0,
  unread: 0,
  read: 0,
  replied: 0,
  today: 0,
  this_week: 0,
});

const filters = ref({
  search: "",
  status: "all",
  page: 1,
});

const selectedMessages = ref<number[]>([]);
const isModalOpen = ref(false);
const selectedMessage = ref<ContactMessage | null>(null);
let searchTimeout: ReturnType<typeof setTimeout>;

onMounted(async () => {
  await fetchStats();
  await fetchMessages();
});

const fetchStats = async () => {
  try {
    const { data } = await api.get("/admin/contact-messages/stats");
    stats.value = data;
  } catch (error) {
    console.error("Failed to fetch stats:", error);
  }
};

const fetchMessages = async () => {
  try {
    const params: any = {
      page: filters.value.page,
    };

    if (filters.value.status !== "all") {
      params.status = filters.value.status;
    }

    if (filters.value.search) {
      params.search = filters.value.search;
    }

    const { data } = await api.get("/admin/contact-messages", { params });
    messages.value = data;
  } catch (error) {
    console.error("Failed to fetch messages:", error);
  }
};

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.value.page = 1;
    fetchMessages();
  }, 300);
};

const changePage = (page: number) => {
  filters.value.page = page;
  fetchMessages();
};

const viewMessage = async (message: ContactMessage) => {
  try {
    const { data } = await api.get(`/admin/contact-messages/${message.id}`);
    selectedMessage.value = data;
    isModalOpen.value = true;

    // Refresh messages and stats to update unread count
    await fetchMessages();
    await fetchStats();
  } catch (error) {
    console.error("Failed to fetch message:", error);
  }
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedMessage.value = null;
};

const markAsRead = async (message: ContactMessage) => {
  try {
    await api.post(`/admin/contact-messages/${message.id}/mark-as-read`);
    await fetchMessages();
    await fetchStats();
  } catch (error) {
    alert(error.response?.data?.message || "Failed to mark as read");
  }
};

const markAsReplied = async (message: ContactMessage) => {
  try {
    await api.post(`/admin/contact-messages/${message.id}/mark-as-replied`);
    if (selectedMessage.value?.id === message.id) {
      closeModal();
    }
    await fetchMessages();
    await fetchStats();
  } catch (error) {
    alert(error.response?.data?.message || "Failed to mark as replied");
  }
};

const deleteMessage = async (message: ContactMessage) => {
  if (
    !confirm(
      `Are you sure you want to delete this message from ${message.name}?`
    )
  ) {
    return;
  }

  try {
    await api.delete(`/admin/contact-messages/${message.id}`);
    if (selectedMessage.value?.id === message.id) {
      closeModal();
    }
    await fetchMessages();
    await fetchStats();
  } catch (error) {
    alert(error.response?.data?.message || "Failed to delete message");
  }
};

const toggleSelectAll = () => {
  if (selectedMessages.value.length === messages.value.data.length) {
    selectedMessages.value = [];
  } else {
    selectedMessages.value = messages.value.data.map((m) => m.id);
  }
};

const bulkMarkAsRead = async () => {
  if (selectedMessages.value.length === 0) return;

  try {
    await api.post("/admin/contact-messages/bulk-mark-as-read", {
      ids: selectedMessages.value,
    });
    selectedMessages.value = [];
    await fetchMessages();
    await fetchStats();
  } catch (error) {
    alert(error.response?.data?.message || "Failed to mark messages as read");
  }
};

const bulkDelete = async () => {
  if (selectedMessages.value.length === 0) return;

  if (
    !confirm(
      `Are you sure you want to delete ${selectedMessages.value.length} message(s)?`
    )
  ) {
    return;
  }

  try {
    await api.post("/admin/contact-messages/bulk-delete", {
      ids: selectedMessages.value,
    });
    selectedMessages.value = [];
    await fetchMessages();
    await fetchStats();
  } catch (error) {
    alert(error.response?.data?.message || "Failed to delete messages");
  }
};

const formatDate = (dateString: string) => {
  if (!dateString) return "";
  const date = new Date(dateString);
  return date.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};
</script>
