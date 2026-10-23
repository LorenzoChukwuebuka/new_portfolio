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
  const toggleLabel = toggle?.querySelector<HTMLElement>('[data-menu-toggle-label]')
  if (!toggle || !drawer || !toggleLabel) return

  const desktopNavigation = window.matchMedia('(min-width: 1025px)')
  let isOpen = false
  let closeTimer = 0
  let scrollY = 0
  let savedHtmlOverflow = ''
  let savedBodyStyles: Record<'position' | 'top' | 'left' | 'right' | 'width' | 'overflow', string> | null = null
  let backgroundState: { element: HTMLElement; inert: boolean; ariaHidden: string | null }[] = []
  const focusable = () => [toggle, ...Array.from(drawer.querySelectorAll<HTMLElement>(
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
  ))].filter((element) => !element.hidden && element.tabIndex >= 0)

  const setBackgroundInert = (inert: boolean) => {
    if (inert) {
      backgroundState = Array.from(document.querySelectorAll<HTMLElement>('main, footer')).map((element) => ({
        element,
        inert: element.inert,
        ariaHidden: element.getAttribute('aria-hidden'),
      }))
      backgroundState.forEach(({ element }) => {
        element.inert = true
        element.setAttribute('aria-hidden', 'true')
      })
      return
    }

    backgroundState.forEach(({ element, inert: wasInert, ariaHidden }) => {
      element.inert = wasInert
      if (ariaHidden === null) element.removeAttribute('aria-hidden')
      else element.setAttribute('aria-hidden', ariaHidden)
    })
    backgroundState = []
  }

  const lockScroll = () => {
    scrollY = window.scrollY
    savedHtmlOverflow = document.documentElement.style.overflow
    savedBodyStyles = {
      position: document.body.style.position,
      top: document.body.style.top,
      left: document.body.style.left,
      right: document.body.style.right,
      width: document.body.style.width,
      overflow: document.body.style.overflow,
    }
    document.documentElement.style.overflow = 'hidden'
    document.body.style.position = 'fixed'
    document.body.style.top = `-${scrollY}px`
    document.body.style.left = '0'
    document.body.style.right = '0'
    document.body.style.width = '100%'
    document.body.style.overflow = 'hidden'
  }

  const unlockScroll = () => {
    if (!savedBodyStyles) return
    document.documentElement.style.overflow = savedHtmlOverflow
    Object.assign(document.body.style, savedBodyStyles)
    savedBodyStyles = null
    window.scrollTo(0, scrollY)
  }

  const finishClose = () => {
    if (!isOpen) drawer.hidden = true
  }

  const close = (restoreToggleFocus = true) => {
    if (!isOpen) return
    isOpen = false
    drawer.dataset.open = 'false'
    drawer.inert = true
    drawer.setAttribute('aria-hidden', 'true')
    toggle.setAttribute('aria-expanded', 'false')
    toggle.setAttribute('aria-label', 'Open menu')
    toggleLabel.textContent = 'Menu'
    unlockScroll()
    setBackgroundInert(false)

    if (restoreToggleFocus && !desktopNavigation.matches) toggle.focus({ preventScroll: true })
    else if (desktopNavigation.matches) document.querySelector<HTMLAnchorElement>('.site-nav__links a')?.focus({ preventScroll: true })

    window.clearTimeout(closeTimer)
    closeTimer = window.setTimeout(finishClose, 300)
  }

  const open = () => {
    if (isOpen || desktopNavigation.matches) return
    isOpen = true
    window.clearTimeout(closeTimer)
    drawer.hidden = false
    drawer.inert = false
    drawer.removeAttribute('aria-hidden')
    toggle.setAttribute('aria-expanded', 'true')
    toggle.setAttribute('aria-label', 'Close menu')
    toggleLabel.textContent = 'Close ×'
    lockScroll()
    setBackgroundInert(true)
    window.requestAnimationFrame(() => {
      if (isOpen) drawer.dataset.open = 'true'
    })
    drawer.querySelector<HTMLElement>('a[href], button:not([disabled])')?.focus({ preventScroll: true })
  }

  toggle.addEventListener('click', () => isOpen ? close() : open())
  drawer.querySelectorAll<HTMLAnchorElement>('a[href]').forEach((link) => link.addEventListener('click', () => {
    close()
    window.setTimeout(() => {
      if (!isOpen && !desktopNavigation.matches) toggle.focus({ preventScroll: true })
    }, 0)
  }))
  drawer.addEventListener('transitionend', (event) => {
    if (event.target === drawer && event.propertyName === 'opacity' && !isOpen) finishClose()
  })

  document.addEventListener('keydown', (event) => {
    if (!isOpen) return
    if (event.key === 'Escape') {
      event.preventDefault()
      close()
      return
    }
    if (event.key !== 'Tab') return
    const items = focusable()
    const last = items[items.length - 1]
    if (!last) return
    if (event.shiftKey && document.activeElement === toggle) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      toggle.focus()
    }
  })

  desktopNavigation.addEventListener('change', (event) => {
    if (event.matches) close(false)
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
