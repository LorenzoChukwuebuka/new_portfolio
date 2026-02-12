<template>
  <section class="py-24 relative">
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-zinc-700/50 to-transparent" />
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="fade-in">
        <p class="font-mono text-amber-400 text-xs tracking-widest uppercase mb-12 text-center">
          Tech Stack
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-8">
          <div v-for="skill in skills" :key="skill.category" class="group">
            <h3 class="font-mono text-xs text-zinc-600 uppercase tracking-widest mb-4">
              {{ skill.category }}
            </h3>
            <ul class="space-y-2">
              <li
                v-for="item in skill.items"
                :key="item"
                class="flex items-center gap-2 text-sm text-zinc-400 group-hover:text-zinc-300 transition-colors"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400/40 group-hover:bg-amber-400 transition-colors flex-shrink-0" />
                {{ item }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { skills } from '../data'
</script>
