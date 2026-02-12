<template>
  <section id="contact" class="py-32 relative">
    <div
      class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-zinc-700/50 to-transparent"
    />

    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-16 fade-in">
        <p
          class="font-mono text-amber-400 text-xs tracking-widest uppercase mb-3"
        >
          Get In Touch
        </p>
        <h2
          class="font-['Playfair_Display',serif] text-4xl sm:text-5xl font-black text-zinc-100 leading-tight"
        >
          Let's Build Something
        </h2>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-12 lg:gap-16">
        <!-- Left Info -->
        <div class="lg:col-span-2 fade-in">
          <p class="text-zinc-400 leading-relaxed mb-8">
            I'm currently open to new opportunities — whether that's a full-time
            role, a consulting project, or an interesting open-source
            collaboration. Drop me a message and I'll get back within 24 hours.
          </p>

          <div class="space-y-4 mb-10">
            <a
              :href="`mailto:${contact.email}`"
              class="flex items-center gap-3 text-zinc-300 hover:text-amber-400 transition-colors group"
            >
              <span
                class="w-8 h-8 flex items-center justify-center bg-zinc-800 border border-zinc-700 group-hover:border-amber-400/50 transition-colors rounded-sm flex-shrink-0"
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
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                  />
                </svg>
              </span>
              <span class="text-sm font-mono">{{ contact.email }}</span>
            </a>
            <div class="flex items-center gap-3 text-zinc-500">
              <span
                class="w-8 h-8 flex items-center justify-center bg-zinc-800 border border-zinc-700 rounded-sm flex-shrink-0"
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
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                </svg>
              </span>
              <span class="text-sm font-mono">{{ contact.location }}</span>
            </div>
          </div>

          <!-- Social Links -->
          <div class="flex gap-3">
            <a
              v-for="social in socials"
              :key="social.label"
              :href="social.href"
              target="_blank"
              rel="noopener"
              class="w-9 h-9 flex items-center justify-center bg-zinc-800 border border-zinc-700 text-zinc-500 hover:text-zinc-200 hover:border-zinc-500 transition-all duration-300 rounded-sm"
              :aria-label="social.label"
            >
              <component :is="social.icon" class="w-4 h-4" />
            </a>
          </div>
        </div>

        <!-- Contact Form -->
        <div class="lg:col-span-3 fade-in">
          <div class="bg-zinc-900 border border-zinc-800 p-8">
            <form @submit.prevent="handleSubmit" class="space-y-5">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                  <label
                    for="name"
                    class="block text-xs font-mono text-zinc-500 uppercase tracking-widest mb-2"
                    >Name</label
                  >
                  <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="Jane Smith"
                    required
                    class="w-full bg-zinc-950 border border-zinc-800 text-zinc-200 text-sm px-4 py-3 placeholder-zinc-600 focus:outline-none focus:border-amber-400/60 focus:ring-1 focus:ring-amber-400/20 transition-all duration-300"
                  />
                </div>
                <div>
                  <label
                    for="email"
                    class="block text-xs font-mono text-zinc-500 uppercase tracking-widest mb-2"
                    >Email</label
                  >
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="jane@company.com"
                    required
                    class="w-full bg-zinc-950 border border-zinc-800 text-zinc-200 text-sm px-4 py-3 placeholder-zinc-600 focus:outline-none focus:border-amber-400/60 focus:ring-1 focus:ring-amber-400/20 transition-all duration-300"
                  />
                </div>
              </div>
              <div>
                <label
                  for="subject"
                  class="block text-xs font-mono text-zinc-500 uppercase tracking-widest mb-2"
                  >Subject</label
                >
                <input
                  id="subject"
                  v-model="form.subject"
                  type="text"
                  placeholder="Project inquiry / consulting / etc."
                  required
                  class="w-full bg-zinc-950 border border-zinc-800 text-zinc-200 text-sm px-4 py-3 placeholder-zinc-600 focus:outline-none focus:border-amber-400/60 focus:ring-1 focus:ring-amber-400/20 transition-all duration-300"
                />
              </div>
              <div>
                <label
                  for="message"
                  class="block text-xs font-mono text-zinc-500 uppercase tracking-widest mb-2"
                  >Message</label
                >
                <textarea
                  id="message"
                  v-model="form.message"
                  rows="5"
                  placeholder="Tell me about your project or what you have in mind..."
                  required
                  class="w-full bg-zinc-950 border border-zinc-800 text-zinc-200 text-sm px-4 py-3 placeholder-zinc-600 focus:outline-none focus:border-amber-400/60 focus:ring-1 focus:ring-amber-400/20 transition-all duration-300 resize-y min-h-[120px]"
                />
              </div>
              <button
                type="submit"
                :disabled="submitting"
                class="w-full px-6 py-4 bg-amber-400 text-zinc-950 font-semibold text-sm hover:bg-amber-300 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_8px_30px_rgba(251,191,36,0.2)] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
              >
                <span v-if="!submitting">Send Message →</span>
                <span v-else class="flex items-center justify-center gap-2">
                  <svg
                    class="animate-spin w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    />
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                    />
                  </svg>
                  Sending...
                </span>
              </button>

              <!-- Success Message (Headless UI Transition) -->
              <TransitionRoot
                :show="submitted"
                enter="transition ease-out duration-300"
                enter-from="opacity-0 translate-y-2"
                enter-to="opacity-100 translate-y-0"
                leave="transition ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
              >
                <div
                  class="flex items-center gap-3 p-4 bg-emerald-900/30 border border-emerald-700/50 text-emerald-400 text-sm rounded-sm"
                >
                  <svg
                    class="w-5 h-5 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 13l4 4L19 7"
                    />
                  </svg>
                  Message sent! I'll get back to you within 24 hours.
                </div>
              </TransitionRoot>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { reactive, ref, h } from "vue";
import { TransitionRoot } from "@headlessui/vue";
import { contactInfo } from "../data";
import api from "../admin/utils/api";

const contact = contactInfo;

const form = reactive({
  name: "",
  email: "",
  subject: "",
  message: "",
});

const submitting = ref(false);
const submitted = ref(false);
const errorvalue = ref("");

async function handleSubmit() {
  submitting.value = true;
  try {
    await api.post("/contact", {
      name: form.name,
      email: form.email,
      subject: form.subject,
      message: form.message,
    });
    submitted.value = true;
    Object.assign(form, { name: "", email: "", subject: "", message: "" });
    setTimeout(() => (submitted.value = false), 6000);
  } catch (e) {
    errorvalue.value = (e as any).message();
  } finally {
    submitting.value = false;
  }
}

// Simple inline SVG components for social icons
const GithubIcon = () =>
  h("svg", { fill: "currentColor", viewBox: "0 0 24 24" }, [
    h("path", {
      d:
        "M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12",
    }),
  ]);

const LinkedinIcon = () =>
  h("svg", { fill: "currentColor", viewBox: "0 0 24 24" }, [
    h("path", {
      d:
        "M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z",
    }),
  ]);

const TwitterIcon = () =>
  h("svg", { fill: "currentColor", viewBox: "0 0 24 24" }, [
    h("path", {
      d:
        "M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.736-8.857L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z",
    }),
  ]);

const socials = [
  { label: "GitHub", href: contact.github, icon: GithubIcon },
  { label: "LinkedIn", href: contact.linkedin, icon: LinkedinIcon },
  { label: "Twitter", href: contact.twitter, icon: TwitterIcon },
];
</script>
