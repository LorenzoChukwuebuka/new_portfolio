<template>
  <div class="min-h-screen bg-zinc-950 text-zinc-100">
    <AppNavbar />
    <main>
            <slot v-if="!initialProjects || !initialPosts" />
      <HeroSection />
      <SkillsSection />
            <ProjectsSection :initial-projects="initialProjects" />
            <BlogSection :initial-posts="initialPosts" />
      <ContactSection />
    </main>
    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import type { ApiProject } from '../types'
import AppNavbar from '../components/AppNavbar.vue'
import HeroSection from '../components/HeroSection.vue'
import SkillsSection from '../components/SkillsSection.vue'
import ProjectsSection from '../components/ProjectsSection.vue'
import BlogSection from '../components/BlogSection.vue'
import ContactSection from '../components/ContactSection.vue'
import AppFooter from '../components/AppFooter.vue'

defineProps<{
    initialProjects?: ApiProject[]
    initialPosts?: unknown[]
}>()
</script>
