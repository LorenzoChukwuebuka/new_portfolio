export interface ProjectMedia {
  id: number
  mediable_type: string
  mediable_id: number
  url: string
  type: 'thumbnail' | 'image'
  thumbnail: string
  encoded: boolean
  created_at: string
  updated_at: string
  deleted_at: string | null
}

// Raw shape from the API
export interface ApiProject {
  id: number
  title: string
  slug: string
  description: string
  full_description: string
  project_link: string | null
  github_link: string | null
  technologies: string[]
  status: string
  completed_at: string | null
  order: number
  is_featured: boolean
  created_at: string
  updated_at: string
  tags: string[]
  media: ProjectMedia[]
}

// Normalized shape used by components
export interface Project {
  id: number
  slug: string
  number: string
  title: string
  description: string
  longDescription: string
  tags: string[]
  githubUrl: string | null
  liveUrl: string | null
  featured: boolean
  year: string
  thumbnailUrl: string | null
  imageUrl: string | null
}

export interface BlogPost {
  id: number
  slug: string
  emoji: string
  readTime: string
  date: string
  title: string
  author?:string
  featuredUrl?:any
  status?:string
  excerpt: string
  content: string
  tags: string[]
  category: string
}

export interface NavLink {
  label: string
  href: string
  external?: boolean
}

export interface ContactInfo {
  email: string
  phone: string
  location: string
  github: string
  linkedin: string
  twitter: string
}

export interface Skill {
  category: string
  items: string[]
}
