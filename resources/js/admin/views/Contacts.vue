<template>
  <Layout page-title="Contact Messages">
    <div class="space-y-6">
      <PageHeader title="Contact messages" description="A searchable inbox for messages sent through the portfolio contact form." />
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-adm-surface rounded-lg border border-adm-line p-4">
          <div class="text-sm text-adm-ink-muted">Total Messages</div>
          <div class="text-2xl font-semibold text-adm-ink">
            {{ stats.total }}
          </div>
        </div>
        <div class="bg-adm-surface rounded-lg border border-adm-line p-4">
          <div class="text-sm text-adm-ink-muted">Unread</div>
          <div class="text-2xl font-semibold text-adm-warning-ink">
            {{ stats.unread }}
          </div>
        </div>
        <div class="bg-adm-surface rounded-lg border border-adm-line p-4">
          <div class="text-sm text-adm-ink-muted">Today</div>
          <div class="text-2xl font-semibold text-adm-info-ink">
            {{ stats.today }}
          </div>
        </div>
        <div class="bg-adm-surface rounded-lg border border-adm-line p-4">
          <div class="text-sm text-adm-ink-muted">This Week</div>
          <div class="text-2xl font-semibold text-adm-success-ink">
            {{ stats.this_week }}
          </div>
        </div>
      </div>

      <ErrorBanner v-if="error" :message="error" :retry="fetchMessages" />

      <!-- Filters and Search -->
      <div class="bg-adm-surface rounded-lg border border-adm-line p-4">
        <div class="flex flex-col md:flex-row gap-4">
          <!-- Search -->
          <div class="flex-1">
            <input
              v-model="filters.search"
              @input="debouncedSearch"
              type="text"
              placeholder="Search by name, email, subject, or message..."
              class="w-full px-4 py-2 border border-adm-line rounded-lg text-adm-ink focus:ring-2 focus:ring-adm-focus focus:border-transparent"
            />
          </div>

          <!-- Status Filter -->
          <div class="w-full md:w-48">
            <select
              v-model="filters.status"
              @change="changeStatusFilter"
              class="w-full px-4 py-2 border border-adm-line rounded-lg text-adm-ink focus:ring-2 focus:ring-adm-focus focus:border-transparent"
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
          class="mt-4 flex items-center gap-3 pt-4 border-t border-adm-line"
        >
          <span class="text-sm text-adm-ink-muted">
            {{ selectedMessages.length }} selected
          </span>
          <button
            @click="bulkMarkAsRead"
            class="px-3 py-1.5 text-sm font-medium text-adm-info-ink bg-adm-info-bg border border-adm-line rounded-lg hover:bg-adm-surface-2"
          >
            Mark as Read
          </button>
          <button
            @click="bulkDelete"
            class="px-3 py-1.5 text-sm font-medium text-adm-danger-ink bg-adm-danger-bg border border-adm-line rounded-lg hover:bg-adm-danger-bg"
          >
            Delete
          </button>
          <button
            @click="selectedMessages = []"
            class="px-3 py-1.5 text-sm font-medium text-adm-ink-muted bg-adm-surface-2 border border-adm-line rounded-lg hover:bg-adm-surface-2"
          >
            Clear Selection
          </button>
        </div>
      </div>

      <AdminLoader
        v-if="loading"
        title="Loading messages"
        message="Fetching contact messages and inbox stats."
        :rows="6"
      />

      <!-- Messages Table -->
      <div v-else class="xl:grid xl:grid-cols-[minmax(0,1fr)_minmax(20rem,0.7fr)] xl:items-start xl:gap-4">
       <div class="min-w-0 rounded-lg border border-adm-line bg-adm-surface">
        <div class="hidden overflow-x-auto md:block">
          <table class="min-w-full divide-y divide-adm-line">
            <thead class="bg-adm-surface-2">
              <tr>
                <th class="px-6 py-3 text-left">
                  <input
                    type="checkbox"
                    @change="toggleSelectAll"
                    :checked="
                      selectedMessages.length === messages.data?.length &&
                      messages.data?.length > 0
                    "
                    class="rounded border-adm-line text-adm-info-ink focus:ring-adm-focus"
                  />
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-adm-ink-muted uppercase tracking-wider"
                >
                  Status
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-adm-ink-muted uppercase tracking-wider"
                >
                  Name
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-adm-ink-muted uppercase tracking-wider"
                >
                  Email
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-adm-ink-muted uppercase tracking-wider"
                >
                  Subject
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-adm-ink-muted uppercase tracking-wider"
                >
                  Date
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-adm-ink-muted uppercase tracking-wider"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-adm-surface divide-y divide-adm-line">
              <tr
                v-for="message in messages.data"
                :key="message.id"
                :class="[
                  'hover:bg-adm-surface-2 cursor-pointer',
                  message.status === 'unread' ? 'bg-adm-info-bg' : '',
                ]"
                @click="viewMessage(message)"
              >
                <td class="px-6 py-4" @click.stop>
                  <input
                    type="checkbox"
                    :value="message.id"
                    v-model="selectedMessages"
                    class="rounded border-adm-line text-adm-info-ink focus:ring-adm-focus"
                  />
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      message.status === 'unread'
                        ? 'bg-adm-warning-bg text-adm-warning-ink'
                        : message.status === 'read'
                        ? 'bg-adm-info-bg text-adm-info-ink'
                        : 'bg-adm-success-bg text-adm-success-ink',
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
                        ? 'font-semibold text-adm-ink'
                        : 'font-medium text-adm-ink',
                    ]"
                  >
                    {{ message.name }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-adm-ink-muted">{{ message.email }}</div>
                </td>
                <td class="px-6 py-4">
                  <div
                    :class="[
                      'text-sm max-w-md truncate',
                      message.status === 'unread'
                        ? 'font-medium text-adm-ink'
                        : 'text-adm-ink-muted',
                    ]"
                  >
                    {{ message.subject }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-adm-ink-muted">
                    {{ formatDate(message.created_at) }}
                  </div>
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                  @click.stop
                >
                  <button
                    @click="viewMessage(message)"
                    class="text-adm-info-ink hover:text-adm-accent"
                  >
                    View
                  </button>
                  <button
                    v-if="message.status === 'unread'"
                    @click="markAsRead(message)"
                    class="text-adm-success-ink hover:underline"
                  >
                    Mark Read
                  </button>
                  <button
                    v-if="message.status === 'read'"
                    @click="markAsReplied(message)"
                    class="text-adm-info-ink hover:text-adm-accent"
                  >
                    Mark Replied
                  </button>
                  <button
                    @click="deleteMessage(message)"
                    class="text-adm-danger-ink hover:text-adm-danger-ink"
                  >
                    Delete
                  </button>
                </td>
              </tr>

              <tr v-if="!messages.data || messages.data.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-adm-ink-muted">
                  No messages found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="divide-y divide-adm-line md:hidden">
          <article v-for="message in messages.data" :key="`mobile-${message.id}`" class="space-y-3 p-4" :class="message.status === 'unread' ? 'bg-adm-info-bg/40' : ''">
            <div class="flex items-start gap-3">
              <input v-model="selectedMessages" type="checkbox" :value="message.id" :aria-label="`Select message from ${message.name}`" class="mt-1 size-4 shrink-0 rounded border-adm-line text-adm-accent focus:ring-adm-focus" />
              <button type="button" class="min-w-0 flex-1 text-left" @click="viewMessage(message)">
                <span class="flex flex-wrap items-center gap-2"><strong class="text-sm text-adm-ink">{{ message.name }}</strong><span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="message.status === 'unread' ? 'bg-adm-warning-bg text-adm-warning-ink' : message.status === 'read' ? 'bg-adm-info-bg text-adm-info-ink' : 'bg-adm-success-bg text-adm-success-ink'">{{ message.status }}</span></span>
                <span class="mt-1 block truncate text-sm font-medium text-adm-ink">{{ message.subject || 'No subject' }}</span>
                <span class="mt-1 block truncate text-xs text-adm-ink-muted">{{ message.email }} · {{ formatDate(message.created_at) }}</span>
              </button>
            </div>
            <div class="flex flex-wrap gap-2 pl-7">
              <button type="button" class="min-h-10 rounded-md px-3 text-sm font-medium text-adm-info-ink hover:bg-adm-surface" @click="viewMessage(message)">View message</button>
              <button v-if="message.status === 'unread'" type="button" class="min-h-10 rounded-md px-3 text-sm font-medium text-adm-success-ink hover:bg-adm-surface" @click="markAsRead(message)">Mark read</button>
              <button v-if="message.status === 'read'" type="button" class="min-h-10 rounded-md px-3 text-sm font-medium text-adm-info-ink hover:bg-adm-surface" @click="markAsReplied(message)">Mark replied</button>
              <button type="button" class="min-h-10 rounded-md px-3 text-sm font-medium text-adm-danger-ink hover:bg-adm-danger-bg" @click="deleteMessage(message)">Delete</button>
            </div>
          </article>
          <p v-if="!messages.data?.length" class="p-8 text-center text-sm text-adm-ink-muted md:hidden">No messages found</p>
        </div>

        <!-- Pagination -->
        <div
          v-if="messages.data && messages.data.length > 0"
          class="px-6 py-4 border-t border-adm-line flex items-center justify-between"
        >
          <div class="text-sm text-adm-ink">
            Showing {{ messages.from }} to {{ messages.to }} of
            {{ messages.total }} results
          </div>
          <div class="flex gap-2">
            <button
              @click="changePage(messages.current_page - 1)"
              :disabled="!messages.prev_page_url"
              class="px-3 py-1.5 text-sm font-medium text-adm-ink bg-adm-surface border border-adm-line rounded-lg hover:bg-adm-surface-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="changePage(messages.current_page + 1)"
              :disabled="!messages.next_page_url"
              class="px-3 py-1.5 text-sm font-medium text-adm-ink bg-adm-surface border border-adm-line rounded-lg hover:bg-adm-surface-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      </div>
       <aside v-if="selectedMessage && !isModalOpen" class="sticky top-24 mt-4 hidden max-h-[calc(100dvh-7rem)] overflow-y-auto rounded-lg border border-adm-line bg-adm-surface p-5 xl:mt-0 xl:block" aria-label="Selected contact message details">
          <div class="flex items-start justify-between gap-3"><div><h2 class="text-lg font-semibold text-adm-ink">{{ selectedMessage.subject || 'No subject' }}</h2><p class="mt-1 text-xs text-adm-ink-muted">{{ formatDate(selectedMessage.created_at) }}</p></div><button type="button" class="min-h-10 min-w-10 rounded-md text-adm-ink-muted hover:bg-adm-surface-2" aria-label="Clear selected message" @click="selectedMessage = null">×</button></div>
          <div class="mt-4 rounded-lg bg-adm-surface-2 p-4"><p class="font-medium text-adm-ink">{{ selectedMessage.name }}</p><a class="break-all text-sm text-adm-info-ink underline-offset-2 hover:underline" :href="`mailto:${selectedMessage.email}?subject=${encodeURIComponent(`Re: ${selectedMessage.subject || 'Your message'}`)}`">{{ selectedMessage.email }}</a><p v-if="selectedMessage.phone" class="mt-1 text-sm text-adm-ink-muted">{{ selectedMessage.phone }}</p></div>
          <p class="mt-4 whitespace-pre-wrap break-words text-sm leading-6 text-adm-ink">{{ selectedMessage.message }}</p>
          <div class="mt-5 flex flex-wrap gap-2 border-t border-adm-line pt-4"><a class="btn btn-primary min-h-10" :href="`mailto:${selectedMessage.email}?subject=${encodeURIComponent(`Re: ${selectedMessage.subject || 'Your message'}`)}`">Reply by email</a><button v-if="selectedMessage.status === 'unread'" class="min-h-10 rounded-md px-3 text-sm font-medium text-adm-info-ink hover:bg-adm-info-bg" @click="markAsRead(selectedMessage)">Mark read</button><button v-if="selectedMessage.status !== 'replied'" class="min-h-10 rounded-md px-3 text-sm font-medium text-adm-info-ink hover:bg-adm-info-bg" @click="markAsReplied(selectedMessage)">Mark replied</button></div>
        </aside>
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
                class="w-full max-w-2xl transform overflow-hidden rounded-lg bg-adm-surface p-6 shadow-xl transition-all"
              >
                <div v-if="selectedMessage" class="space-y-4">
                  <!-- Header -->
                  <div class="flex items-start justify-between">
                    <DialogTitle
                      class="text-xl font-semibold text-adm-ink flex-1"
                    >
                      {{ selectedMessage.subject }}
                    </DialogTitle>
                    <span
                      :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-3',
                        selectedMessage.status === 'unread'
                          ? 'bg-adm-warning-bg text-adm-warning-ink'
                          : selectedMessage.status === 'read'
                          ? 'bg-adm-info-bg text-adm-info-ink'
                          : 'bg-adm-success-bg text-adm-success-ink',
                      ]"
                    >
                      {{ selectedMessage.status }}
                    </span>
                  </div>

                  <!-- Sender Info -->
                  <div class="bg-adm-surface-2 rounded-lg p-4 space-y-2">
                    <div class="flex items-center justify-between">
                      <div>
                        <div class="text-sm font-medium text-adm-ink">
                          {{ selectedMessage.name }}
                        </div>
                        <div class="text-sm text-adm-ink-muted">
                          {{ selectedMessage.email }}
                        </div>
                      </div>
                      <div class="text-sm text-adm-ink-muted">
                        {{ formatDate(selectedMessage.created_at) }}
                      </div>
                    </div>
                    <div
                      v-if="selectedMessage.phone"
                      class="text-sm text-adm-ink-muted"
                    >
                      Phone: {{ selectedMessage.phone }}
                    </div>
                  </div>

                  <!-- Message Content -->
                  <div class="border-t border-adm-line pt-4">
                    <div class="text-sm text-adm-ink whitespace-pre-wrap">
                      {{ selectedMessage.message }}
                    </div>
                  </div>

                  <!-- Read/Replied Info -->
                  <div
                    v-if="selectedMessage.read_at || selectedMessage.replied_at"
                    class="border-t border-adm-line pt-4 text-xs text-adm-ink-muted space-y-1"
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
                    class="flex justify-between items-center pt-4 border-t border-adm-line"
                  >
                    <a
                      :href="`mailto:${selectedMessage.email}?subject=Re: ${selectedMessage.subject}`"
                      class="px-4 py-2 text-sm font-medium text-adm-accent-ink bg-adm-accent-bg rounded-lg hover:brightness-95"
                      target="_blank"
                    >
                      Reply via Email
                    </a>

                    <div class="flex gap-2">
                      <button
                        v-if="selectedMessage.status !== 'replied'"
                        @click="markAsReplied(selectedMessage)"
                        class="px-4 py-2 text-sm font-medium text-adm-info-ink bg-adm-info-bg border border-adm-line rounded-lg hover:bg-adm-surface-2"
                      >
                        Mark as Replied
                      </button>
                      <button
                        @click="deleteMessage(selectedMessage)"
                        class="px-4 py-2 text-sm font-medium text-adm-danger-ink bg-adm-danger-bg border border-adm-line rounded-lg hover:bg-adm-danger-bg"
                      >
                        Delete
                      </button>
                      <button
                        @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-adm-ink bg-adm-surface border border-adm-line rounded-lg hover:bg-adm-surface-2"
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
import { confirmAction, showToast } from '../ui/feedback'
import { ref, onMounted, watch } from "vue";
import { useRoute, useRouter } from 'vue-router'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild,
} from "@headlessui/vue";
import Layout from "../components/Layout.vue";
import AdminLoader from "../components/AdminLoader.vue";
import api, { apiErrorMessage } from "../utils/api";
import PageHeader from '../ui/PageHeader.vue'
import ErrorBanner from '../ui/ErrorBanner.vue'

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
  from: number | null;
  to: number | null;
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

const route = useRoute()
const router = useRouter()
const filters = ref({
  search: typeof route.query.search === 'string' ? route.query.search : "",
  status: typeof route.query.status === 'string' ? route.query.status : "all",
  page: Math.max(1, Number(route.query.page) || 1),
});

const selectedMessages = ref<number[]>([]);
const isModalOpen = ref(false);
const selectedMessage = ref<ContactMessage | null>(null);
const loading = ref(true);
const error = ref('')
let searchTimeout: ReturnType<typeof setTimeout>;

watch(filters, value => {
  void router.replace({ query: { ...route.query, search: value.search || undefined, status: value.status === 'all' ? undefined : value.status, page: value.page > 1 ? String(value.page) : undefined } })
}, { deep: true })

onMounted(async () => {
  try {
    await Promise.all([fetchStats(), fetchMessages()]);
  } finally {
    loading.value = false;
  }
});

const fetchStats = async () => {
  try {
    const { data } = await api.get("/admin/contact-messages/stats");
    stats.value = data;
  } catch (reason) {
    showToast(apiErrorMessage(reason), 'error')
  }
};

const fetchMessages = async () => {
  error.value = ''
  try {
    const params: any = {
      page: filters.value.page,
      per_page: 20,
    };

    if (filters.value.status !== "all") {
      params.status = filters.value.status;
    }

    if (filters.value.search) {
      params.search = filters.value.search;
    }

    const { data } = await api.get("/admin/contact-messages", { params });
    messages.value = data;
  } catch (reason) {
    error.value = apiErrorMessage(reason)
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

const changeStatusFilter = () => {
  filters.value.page = 1
  fetchMessages()
}

const viewMessage = async (message: ContactMessage) => {
  try {
    const { data } = await api.get(`/admin/contact-messages/${message.id}`);
    selectedMessage.value = data;
    isModalOpen.value = window.innerWidth < 1280;

    // Refresh messages and stats to update unread count
    await fetchMessages();
    await fetchStats();
  } catch (error) {
    showToast(apiErrorMessage(error), 'error')
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
    showToast((error as any).response?.data?.message || "Failed to mark as read");
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
    showToast(
      (error as any).response?.data?.message || "Failed to mark as replied"
    );
  }
};

const deleteMessage = async (message: ContactMessage) => {
  if (
    !await confirmAction(
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
    showToast((error as any).response?.data?.message || "Failed to delete message");
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
    showToast(
      (error as any).response?.data?.message ||
        "Failed to mark messages as read"
    );
  }
};

const bulkDelete = async () => {
  if (selectedMessages.value.length === 0) return;

  if (
    !await confirmAction(
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
    showToast(
      (error as any).response?.data?.message || "Failed to delete messages"
    );
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
