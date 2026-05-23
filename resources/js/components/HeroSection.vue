<template>
  <section
    class="relative min-h-screen flex items-center pt-20 overflow-hidden border-b border-zinc-900"
  >
    <!-- Grid overlay -->
    <div
      class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.018)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.018)_1px,transparent_1px)] bg-[size:72px_72px] pointer-events-none"
    />

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10 py-20">
      <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_420px] gap-14 lg:gap-16 items-center">
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
          Laravel, Go & SaaS Engineer
        </p>

        <!-- Hero Title -->
        <h1
          class="animate-fade-in-up opacity-0 [animation-delay:0.35s] [animation-fill-mode:forwards] font-['Playfair_Display',serif] text-5xl sm:text-6xl lg:text-7xl font-black leading-[1.08] tracking-tight text-zinc-100 mb-6"
        >
          I build reliable software<br />
          <span class="text-amber-400">for serious teams.</span>
        </h1>

        <!-- Description -->
        <p
          class="animate-fade-in-up opacity-0 [animation-delay:0.5s] [animation-fill-mode:forwards] text-lg sm:text-xl text-zinc-400 leading-relaxed max-w-2xl mb-4"
        >
          I help founders and teams turn messy product ideas into production-ready
          Laravel, Go, payment, dashboard, and SaaS systems that are maintainable
          after launch.
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
            View Projects
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
            href="/blog"
            class="inline-flex items-center gap-2 px-7 py-3.5 border border-zinc-700 text-zinc-300 font-semibold text-sm hover:border-amber-400 hover:text-amber-400 transition-all duration-300 hover:-translate-y-1"
          >
            Read Blog
          </a>
          <a
            href="#contact"
            class="inline-flex items-center gap-2 px-7 py-3.5 border border-zinc-800 text-zinc-500 font-semibold text-sm hover:border-zinc-600 hover:text-zinc-300 transition-all duration-300"
          >
            Contact Me
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

      <div
        class="animate-fade-in-up opacity-0 [animation-delay:0.45s] [animation-fill-mode:forwards] lg:justify-self-end"
      >
        <figure class="relative">
          <div class="absolute -inset-3 border border-amber-400/20" />
          <div class="relative overflow-hidden border border-zinc-800 bg-zinc-900">
            <img
              src="/storage/images/profile.jpg"
              alt="Portrait of Lorenzo Chukwuebuka Obi"
              class="aspect-[4/5] w-full max-w-[420px] object-cover object-center grayscale-[20%]"
            />
          </div>
          <figcaption
            class="mt-4 flex items-center justify-between gap-4 border-t border-zinc-800 pt-4 font-mono text-xs text-zinc-500"
          >
            <span>Lorenzo Chukwuebuka Obi</span>
            <span class="text-amber-400">Nigeria</span>
          </figcaption>
        </figure>
      </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const techStack = [
  "Laravel",
  "Go",
  "Vue 3",
  "TypeScript",
  "SQL",
  "PostgreSQL",
  "Payments",
  "Dashboards",
];
</script>
