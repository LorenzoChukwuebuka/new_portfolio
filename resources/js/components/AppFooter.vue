<template>
  <footer class="border-t border-zinc-800/60 py-12 mt-16">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-6">
        <!-- Logo + tagline -->
        <div class="flex items-center gap-4">
          <span class="font-mono text-amber-400 font-bold">alex<span class="text-zinc-600">.</span>dev</span>
          <span class="hidden sm:block w-px h-4 bg-zinc-800" />
          <span class="text-xs text-zinc-600 font-mono">Building reliable software since 2018</span>
        </div>

        <!-- Social Links -->
        <div class="flex items-center gap-6">
          <a
            v-for="link in socialLinks"
            :key="link.label"
            :href="link.href"
            target="_blank"
            rel="noopener"
            class="text-zinc-600 hover:text-zinc-300 text-sm font-mono transition-colors"
          >
            {{ link.label }}
          </a>
        </div>
      </div>

      <!-- Copyright -->
      <div class="mt-8 pt-6 border-t border-zinc-900 text-center">
        <p class="text-xs text-zinc-700 font-mono">
          © {{ currentYear }} Lorenzo Chukwuebuka Obi. Built with Vue 3 + Tailwind CSS + Laravel.
          <span class="text-zinc-800"> — </span>
          <a href="https://github.com/LorenzoChukwuebuka/new_portfolio" target="_blank" class="hover:text-zinc-500 transition-colors">View source</a>
        </p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
const currentYear = new Date().getFullYear()

const socialLinks = [
  { label: 'GitHub', href: 'https://github.com' },
  { label: 'LinkedIn', href: 'https://linkedin.com' },
  { label: 'Twitter', href: 'https://twitter.com' },
]
</script>
