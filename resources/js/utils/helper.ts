import type { ApiProject, Project,BlogPost } from './../types'

 

function mediaUrl(path: string): string {
    return `/storage/${path}`
    //  return path.startsWith('http') ? path : `/storage/${path}`
}

export function normalizeProject(p: ApiProject): Project {

    const thumbnail = p.media.find(m => m.type === 'thumbnail')
    const image = p.media.find(m => m.type === 'image')

    const year = p.completed_at
        ? new Date(p.completed_at).getFullYear().toString()
        : new Date(p.created_at).getFullYear().toString()

    return {
        id: p.id,
        slug: p.slug,
        number: String(p.order).padStart(2, '0'),
        title: p.title,
        description: p.description,
        longDescription: p.full_description,
        tags: p.technologies.length ? p.technologies : p.tags,
        githubUrl: p.github_link ?? null,
        liveUrl: p.project_link ?? null,
        featured: p.is_featured,
        year,
        thumbnailUrl: thumbnail ? mediaUrl(thumbnail.url) : null,
        imageUrl: image ? mediaUrl(image.url) : null,
    }
}


export function normalizeBlogPost(p: any): BlogPost {
  const featured = p.media?.find((m: any) => m.type === 'featured')

  const date = p.published_at
    ? new Date(p.published_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })
    : new Date(p.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      })

  return {
    id: p.id,
    title: p.title,
    slug: p.slug,
    excerpt: p.excerpt,
    content: p.content,
    author: p.author,
    category: p.category?.name ?? 'Uncategorized',
    tags: p.tags?.map((t: any) => t.name) ?? [],
    readTime: `${p.read_time} min read`,
    date,
    status: p.status,
    featuredUrl: featured ? mediaUrl(featured.url) : null,
    emoji: '📝', // fallback since API has no emoji field
  }
}