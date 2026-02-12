import type { Project } from '../types'

export const projects: Project[] = [
  {
    id: 1,
    slug: 'distributed-task-queue',
    number: '01',
    title: 'Distributed Task Queue',
    description:
      'A high-throughput, fault-tolerant task processing system built with Go and Redis Streams, handling over 2M jobs per day with sub-100ms latency.',
    longDescription:
      'Built a production-grade distributed task queue from the ground up to replace a legacy cron-based system. The new architecture handles priority queues, retries with exponential backoff, dead-letter queues, and real-time monitoring. Reduced infrastructure costs by 40% while tripling throughput.',
    tags: ['Go', 'Redis', 'Docker', 'Kubernetes'],
    techStack: ['Go', 'Redis Streams', 'Docker', 'Kubernetes', 'Prometheus', 'Grafana'],
    githubUrl: 'https://github.com',
    liveUrl: 'https://example.com',
    featured: true,
    year: '2025',
  },
  {
    id: 2,
    slug: 'realtime-collab-editor',
    number: '02',
    title: 'Real-time Collaborative Editor',
    description:
      'A conflict-free, real-time document editor powered by CRDTs (Yjs), WebSockets, and Vue 3 — supporting 500+ concurrent users per document.',
    longDescription:
      'Engineered a collaborative text editor using Conflict-free Replicated Data Types (CRDTs) to ensure eventual consistency without server-side conflict resolution. Implemented operational transformation fallbacks and cursor presence awareness using WebSockets. The system maintains document integrity across network partitions.',
    tags: ['Vue 3', 'TypeScript', 'WebSockets', 'CRDT'],
    techStack: ['Vue 3', 'TypeScript', 'Yjs', 'WebSockets', 'Laravel', 'PostgreSQL'],
    githubUrl: 'https://github.com',
    liveUrl: 'https://example.com',
    featured: true,
    year: '2025',
  },
  {
    id: 3,
    slug: 'api-gateway',
    number: '03',
    title: 'API Gateway & Auth Service',
    description:
      'A zero-trust API gateway with JWT/OAuth2 authentication, rate limiting, request validation, and observability built on top of Laravel Octane.',
    longDescription:
      'Designed and implemented a centralized API gateway that handles authentication, authorization, rate limiting, and request routing for a microservices architecture. Supports multiple auth strategies (JWT, API Keys, OAuth2), per-route throttling, and emits structured logs/traces to a Loki + Jaeger stack.',
    tags: ['Laravel', 'PHP', 'OAuth2', 'Redis'],
    techStack: ['Laravel Octane', 'PHP 8.3', 'Redis', 'JWT', 'OAuth2', 'Swoole'],
    githubUrl: 'https://github.com',
    featured: false,
    year: '2024',
  },
  {
    id: 4,
    slug: 'ci-cd-platform',
    number: '04',
    title: 'Internal CI/CD Platform',
    description:
      'A self-hosted CI/CD platform with pipeline-as-code, container sandboxing, artifact caching, and Slack/GitHub integrations — replacing GitHub Actions for cost savings.',
    longDescription:
      'Built an internal CI/CD platform to give the engineering team full control over their build infrastructure. Supports YAML-defined pipelines, Docker-in-Docker sandboxing, S3-backed artifact caching, and parallel job execution. Cut CI costs by 65% compared to GitHub Actions while improving average build times by 2x.',
    tags: ['Python', 'Docker', 'PostgreSQL', 'Vue 3'],
    techStack: ['Python', 'FastAPI', 'Docker', 'PostgreSQL', 'Vue 3', 'Celery', 'S3'],
    githubUrl: 'https://github.com',
    liveUrl: 'https://example.com',
    featured: false,
    year: '2024',
  },
]
