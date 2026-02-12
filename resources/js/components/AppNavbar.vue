<template>
  <nav
    class="fixed top-0 w-full z-50 bg-zinc-950/80 backdrop-blur-xl border-b border-zinc-800/60 animate-slide-down"
  >
    <div
      class="max-w-7xl mx-auto px-6 lg:px-8 py-4 flex justify-between items-center"
    >
      <!-- Logo -->
      <a
        href="/"
        class="font-mono text-amber-400 font-bold text-xl tracking-tight hover:text-amber-300 transition-colors"
      >
        Lorenzo<span class="text-zinc-500">.</span>Chukwuebuka
      </a>

      <!-- Desktop Nav -->
      <ul class="hidden md:flex gap-10 list-none">
        <li v-for="link in navLinks" :key="link.href">
          <a
            :href="link.href"
            class="text-zinc-300 hover:text-amber-400 font-medium text-sm tracking-wide relative group transition-colors"
          >
            {{ link.label }}
            <span
              class="absolute -bottom-1 left-0 w-0 h-0.5 bg-amber-400 group-hover:w-full transition-all duration-300"
            />
          </a>
        </li>
      </ul>

      <!-- CTA -->
      <a
        href="#contact"
        class="hidden md:inline-flex items-center gap-2 px-4 py-2 bg-amber-400 text-zinc-950 text-sm font-semibold hover:bg-amber-300 transition-all duration-300 hover:-translate-y-0.5"
      >
        Hire Me
      </a>

      <!-- Mobile Menu Button -->
      <button
        class="md:hidden text-zinc-300 hover:text-amber-400 transition-colors"
        @click="mobileMenuOpen = !mobileMenuOpen"
        :aria-expanded="mobileMenuOpen"
        aria-label="Toggle menu"
      >
        <svg
          v-if="!mobileMenuOpen"
          xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"
          />
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>

    <!-- Mobile Menu (Headless UI Dialog equivalent via transition) -->
    <TransitionRoot
      :show="mobileMenuOpen"
      enter="transition ease-out duration-200"
      enter-from="opacity-0 -translate-y-2"
      enter-to="opacity-100 translate-y-0"
      leave="transition ease-in duration-150"
      leave-from="opacity-100 translate-y-0"
      leave-to="opacity-0 -translate-y-2"
      as="div"
    >
      <div class="md:hidden bg-zinc-900 border-t border-zinc-800 px-6 py-6">
        <ul class="flex flex-col gap-6 list-none">
          <li v-for="link in navLinks" :key="link.href">
            <a
              :href="link.href"
              class="text-zinc-300 hover:text-amber-400 font-medium text-base transition-colors"
              @click="mobileMenuOpen = false"
            >
              {{ link.label }}
            </a>
          </li>
          <li>
            <a
              href="#contact"
              class="inline-block px-5 py-2.5 bg-amber-400 text-zinc-950 text-sm font-semibold hover:bg-amber-300 transition-colors"
              @click="mobileMenuOpen = false"
            >
              Hire Me
            </a>
          </li>
        </ul>
      </div>
    </TransitionRoot>
  </nav>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { TransitionRoot } from "@headlessui/vue";
import { navLinks } from "../data";
const mobileMenuOpen = ref(false);
</script>
