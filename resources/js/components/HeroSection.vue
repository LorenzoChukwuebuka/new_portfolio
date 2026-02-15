<template>
  <section
    class="relative min-h-screen flex items-center pt-20 overflow-hidden"
  >
    <!-- Ambient background gradient -->
    <div class="absolute inset-0 pointer-events-none">
      <div
        class="absolute -top-1/4 -left-1/4 w-3/4 h-3/4 bg-amber-400/5 rounded-full blur-3xl animate-drift"
      />
      <div
        class="absolute top-1/2 right-0 w-1/2 h-1/2 bg-blue-500/5 rounded-full blur-3xl animate-drift-slow"
      />
    </div>

    <!-- Grid overlay -->
    <div
      class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.015)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.015)_1px,transparent_1px)] bg-[size:72px_72px] pointer-events-none"
    />

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10 py-20">
      <div class="max-w-4xl">
        <!-- Status badge -->
        <div
          class="animate-fade-in-up opacity-0 [animation-delay:0.1s] [animation-fill-mode:forwards] mb-8"
        >
          <span
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-800/80 border border-zinc-700/60 text-xs font-mono text-zinc-400"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
            Available for new projects
          </span>
        </div>

        <!-- Label -->
        <p
          class="animate-fade-in-up opacity-0 [animation-delay:0.2s] [animation-fill-mode:forwards] font-mono text-amber-400 text-sm tracking-widest uppercase mb-4"
        >
          Software Engineer
        </p>

        <!-- Hero Title -->
        <h1
          class="animate-fade-in-up opacity-0 [animation-delay:0.35s] [animation-fill-mode:forwards] font-['Playfair_Display',serif] text-5xl sm:text-6xl lg:text-8xl font-black leading-[1.08] tracking-tight text-zinc-100 mb-6"
        >
          I build software<br />
          <span class="text-amber-400">that scales.</span>
        </h1>

        <!-- Description -->
        <p
          class="animate-fade-in-up opacity-0 [animation-delay:0.5s] [animation-fill-mode:forwards] text-lg sm:text-xl text-zinc-400 leading-relaxed max-w-2xl mb-4"
        >
          Full-stack engineer with 5+ years crafting performant APIs,
          distributed systems, and snappy frontend experiences. I care deeply
          about clean abstractions, developer ergonomics, and shipping things
          that actually work in production.
        </p>

        <!-- Tech Stack inline -->
        <p
          class="animate-fade-in-up opacity-0 [animation-delay:0.6s] [animation-fill-mode:forwards] font-mono text-sm text-zinc-500 mb-10"
        >
          <span class="text-zinc-600">// </span>
          <span v-for="(tech, i) in techStack" :key="tech">
            <span class="text-amber-400/80">{{ tech }}</span>
            <span v-if="i < techStack.length - 1" class="text-zinc-600">
              ·
            </span>
          </span>
        </p>

        <!-- CTA Buttons -->
        <div
          class="animate-fade-in-up opacity-0 [animation-delay:0.7s] [animation-fill-mode:forwards] flex flex-wrap gap-4"
        >
          <a
            href="#work"
            class="group inline-flex items-center gap-2 px-7 py-3.5 bg-amber-400 text-zinc-950 font-semibold text-sm hover:bg-amber-300 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_10px_40px_rgba(251,191,36,0.25)]"
          >
            View My Work
            <svg
              class="w-4 h-4 group-hover:translate-x-1 transition-transform"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 8l4 4m0 0l-4 4m4-4H3"
              />
            </svg>
          </a>
          <a
            href="#contact"
            class="inline-flex items-center gap-2 px-7 py-3.5 border border-zinc-700 text-zinc-300 font-semibold text-sm hover:border-amber-400 hover:text-amber-400 transition-all duration-300 hover:-translate-y-1"
          >
            Get In Touch
          </a>
          <a
            href="https://github.com"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-2 px-4 py-3.5 border border-zinc-800 text-zinc-500 font-semibold text-sm hover:border-zinc-600 hover:text-zinc-300 transition-all duration-300"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.295 24 12c0-6.63-5.37-12-12-12"
              />
            </svg>
          </a>
        </div>

        <!-- Stats row -->
        <!-- <div
          class="animate-fade-in-up opacity-0 [animation-delay:0.85s] [animation-fill-mode:forwards] flex flex-wrap gap-10 mt-16 pt-10 border-t border-zinc-800/60"
        >
          <div v-for="stat in stats" :key="stat.label">
            <p
              class="font-['Playfair_Display',serif] text-3xl font-black text-zinc-100"
            >
              {{ stat.value }}
            </p>
            <p class="text-sm text-zinc-500 mt-1">{{ stat.label }}</p>
          </div>
        </div> -->
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const techStack = [
  "TypeScript",
  "Vue 3",
  "SQL",
  "Laravel",
  "Go",
  "PostgreSQL",
  "Docker",
];

const stats = [
  { value: "5+", label: "Years of experience" },
  { value: "40+", label: "Projects shipped" },
  { value: "0", label: "Open source libs" },
  { value: "99.9%", label: "Uptime, avg." },
];
</script>
