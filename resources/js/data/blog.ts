import type { BlogPost } from '../types'

export const blogPosts: BlogPost[] = [
    {
        id: 1,
        slug: 'building-resilient-microservices',
        emoji: '⚙️',
        readTime: '9 min read',
        date: 'Feb 10, 2026',
        title: 'Building Resilient Microservices with Circuit Breakers',
        excerpt:
            'A deep dive into implementing circuit breakers, bulkheads, and retry policies to make your microservices survive failures gracefully.',
        content: '',
        tags: ['Architecture', 'Go', 'Resilience'],
        category: 'Architecture',
    },
    {
        id: 2,
        slug: 'postgres-query-optimization',
        emoji: '🗄️',
        readTime: '7 min read',
        date: 'Feb 3, 2026',
        title: 'PostgreSQL Query Optimization: From Slow to Sub-10ms',
        excerpt:
            'How I reduced a critical reporting query from 4.2 seconds to 8ms using partial indexes, materialized views, and query planner hints.',
        content: '',
        tags: ['PostgreSQL', 'Performance', 'SQL'],
        category: 'Database',
    },
    {
        id: 3,
        slug: 'typescript-strict-mode',
        emoji: '🔷',
        readTime: '5 min read',
        date: 'Jan 26, 2026',
        title: 'Why You Should Enable TypeScript Strict Mode Today',
        excerpt:
            'Strict mode catches entire categories of bugs at compile time. Here\'s a practical guide to enabling it incrementally on a large codebase.',
        content: '',
        tags: ['TypeScript', 'DX', 'Best Practices'],
        category: 'TypeScript',
    },
    {
        id: 4,
        slug: 'laravel-event-sourcing',
        emoji: '📡',
        readTime: '11 min read',
        date: 'Jan 15, 2026',
        title: 'Event Sourcing in Laravel: A Practical Introduction',
        excerpt:
            'Moving from CRUD to event sourcing — the trade-offs, the patterns, and the tooling that makes it viable in a Laravel monolith.',
        content: '',
        tags: ['Laravel', 'Event Sourcing', 'PHP'],
        category: 'Laravel',
    },
    {
        id: 5,
        slug: 'docker-layer-caching',
        emoji: '🐳',
        readTime: '6 min read',
        date: 'Jan 5, 2026',
        title: 'Mastering Docker Layer Caching for Faster CI Builds',
        excerpt:
            'Small Dockerfile changes can destroy your cache and double build times. Here\'s how to structure your images for maximum cache reuse.',
        content: '',
        tags: ['Docker', 'CI/CD', 'DevOps'],
        category: 'DevOps',
    },
    {
        id: 6,
        slug: 'vue3-composables-patterns',
        emoji: '💚',
        readTime: '8 min read',
        date: 'Dec 20, 2025',
        title: 'Vue 3 Composables: Patterns That Actually Scale',
        excerpt:
            'Composition API composables are powerful but easy to misuse. These patterns have helped my teams build maintainable, testable Vue apps.',
        content: '',
        tags: ['Vue 3', 'Composition API', 'Patterns'],
        category: 'Vue.js',
    },
]
