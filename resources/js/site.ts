import type { Component } from 'vue'
import '../css/app.css'

const islandLoaders: Record<string, () => Promise<{ default: Component }>> = {
  ContactForm: () => import('./islands/ContactForm.vue'),
  CommentForm: () => import('./islands/CommentForm.vue'),
  CopyLink: () => import('./islands/CopyLink.vue'),
}

async function mountIsland(element: HTMLElement) {
  if (element.dataset.mounted === 'true') return
  const name = element.dataset.island || ''
  const load = islandLoaders[name]
  if (!load) return

  element.dataset.mounted = 'true'
  try {
    const props = JSON.parse(element.dataset.props || '{}') as Record<string, unknown>
    const [{ mountIsland }, { default: component }] = await Promise.all([import('./islands/mount'), load()])
    mountIsland(element, component, props)
  } catch (error) {
    element.dataset.mounted = 'false'
    console.error(`Could not load the ${name} island.`, error)
  }
}

function mountIslands() {
  const islands = document.querySelectorAll<HTMLElement>('[data-island]')
  if (!('IntersectionObserver' in window)) {
    islands.forEach((element) => { void mountIsland(element) })
    return
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return
      observer.unobserve(entry.target)
      void mountIsland(entry.target as HTMLElement)
    })
  }, { rootMargin: '400px 0px' })
  islands.forEach((element) => observer.observe(element))
}

function initMobileNavigation() {
  const toggle = document.querySelector<HTMLButtonElement>('[data-menu-toggle]')
  const drawer = document.querySelector<HTMLElement>('#site-mobile-nav')
  const closeButton = drawer?.querySelector<HTMLButtonElement>('[data-menu-close]')
  if (!toggle || !drawer || !closeButton) return

  let returnFocus: HTMLElement = toggle
  const focusable = () => Array.from(drawer.querySelectorAll<HTMLElement>(
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
  ))

  const close = () => {
    if (drawer.hidden) return
    drawer.hidden = true
    toggle.setAttribute('aria-expanded', 'false')
    toggle.setAttribute('aria-label', 'Open navigation menu')
    document.body.classList.remove('menu-open')
    document.querySelectorAll<HTMLElement>('main, footer').forEach((element) => { element.inert = false })
    returnFocus.focus()
  }

  const open = () => {
    returnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : toggle
    drawer.hidden = false
    toggle.setAttribute('aria-expanded', 'true')
    toggle.setAttribute('aria-label', 'Close navigation menu')
    document.body.classList.add('menu-open')
    document.querySelectorAll<HTMLElement>('main, footer').forEach((element) => { element.inert = true })
    closeButton.focus()
  }

  toggle.addEventListener('click', () => drawer.hidden ? open() : close())
  closeButton.addEventListener('click', close)
  drawer.querySelectorAll('[data-menu-link]').forEach((link) => link.addEventListener('click', close))

  document.addEventListener('keydown', (event) => {
    if (drawer.hidden) return
    if (event.key === 'Escape') {
      event.preventDefault()
      close()
      return
    }
    if (event.key !== 'Tab') return
    const items = focusable()
    const first = items[0]
    const last = items[items.length - 1]
    if (!first || !last) return
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      first.focus()
    }
  })
}

function initReveal() {
  const selector = '[data-reveal]'
  const revealAll = () => document.querySelectorAll<HTMLElement>(selector).forEach((element) => element.classList.add('is-revealed'))
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

  if (reduceMotion || !('IntersectionObserver' in window)) {
    revealAll()
    return
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return
      const element = entry.target as HTMLElement
      element.classList.add('is-revealed')
      observer.unobserve(element)
    })
  }, { threshold: 0, rootMargin: '0px 0px -10% 0px' })

  const refreshReveal = () => {
    document.querySelectorAll<HTMLElement>(`${selector}:not(.is-revealed)`).forEach((element) => {
      const delay = Number(element.dataset.revealDelay || 0)
      if (Number.isFinite(delay) && delay > 0) element.style.setProperty('--reveal-delay', `${Math.min(delay, 1000)}ms`)
      observer.observe(element)
    })
  }

  refreshReveal()
  const mutations = new MutationObserver(refreshReveal)
  mutations.observe(document.body, { childList: true, subtree: true })
  window.setTimeout(revealAll, 2500)
  window.refreshReveal = refreshReveal
}

function initArticleTools() {
  const progress = document.querySelector<HTMLElement>('.article-progress > span')
  let scheduled = false
  const updateProgress = () => {
    if (!progress) return
    const page = document.documentElement
    const available = page.scrollHeight - window.innerHeight
    const amount = available > 0 ? Math.min(1, Math.max(0, window.scrollY / available)) : 0
    progress.style.transform = `scaleX(${amount})`
    scheduled = false
  }
  if (progress) {
    window.addEventListener('scroll', () => {
      if (scheduled) return
      scheduled = true
      window.requestAnimationFrame(updateProgress)
    }, { passive: true })
    window.addEventListener('resize', updateProgress, { passive: true })
    updateProgress()
  }

  document.querySelectorAll<HTMLElement>('.prose-article pre').forEach((block) => {
    const button = document.createElement('button')
    button.className = 'code-copy'
    button.type = 'button'
    button.textContent = 'Copy code'
    button.setAttribute('aria-label', 'Copy code block')
    button.addEventListener('click', async () => {
      const text = block.querySelector('code')?.textContent || block.textContent || ''
      try {
        await navigator.clipboard.writeText(text)
        button.textContent = 'Copied'
      } catch {
        button.textContent = 'Select code to copy'
      }
      window.setTimeout(() => { button.textContent = 'Copy code' }, 2200)
    })
    block.append(button)
  })

  const headings = Array.from(document.querySelectorAll<HTMLElement>('.prose-article h2[id], .prose-article h3[id]'))
  const tocLinks = new Map<string, HTMLAnchorElement[]>()
  document.querySelectorAll<HTMLAnchorElement>('.article-toc a[href^="#"]').forEach((link) => {
    const id = decodeURIComponent(link.hash.slice(1))
    tocLinks.set(id, [...(tocLinks.get(id) || []), link])
  })
  if ('IntersectionObserver' in window && headings.length) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        const links = tocLinks.get((entry.target as HTMLElement).id) || []
        links.forEach((link) => {
          if (entry.isIntersecting) link.setAttribute('aria-current', 'location')
          else link.removeAttribute('aria-current')
        })
      })
    }, { rootMargin: '-12% 0px -78% 0px', threshold: 0 })
    headings.forEach((heading) => observer.observe(heading))
  }
}

declare global {
  interface Window {
    refreshReveal?: () => void
  }
}

initMobileNavigation()
initReveal()
initArticleTools()
mountIslands()
