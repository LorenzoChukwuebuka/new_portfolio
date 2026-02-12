export interface Category {
  id?: number;
  name: string;
  slug?: string;
  description?: string;
  created_at?: string;
  updated_at?: string;
}

export interface Tag {
  id: number;
  name: string;
}

export interface Project {
  id?: number;
  title: string;
  slug?: string;
  description: string;
  full_description?: string;
  project_link: string;
  github_link?: string;
  technologies: string[];
  status: 'completed' | 'in-progress' | 'archived';
  completed_at?: string;
  order?: number;
  is_featured: boolean;
  tags?: number[];
  media:any
  thumbnail?: File | string;
  main_image?: File | string;
  created_at?: string;
  updated_at?: string;
}

 


export interface Post {
  id?: number;
  category_id?: number;
  category?: Category;
  title: string;
  slug?: string;
  excerpt: string;
  content: string;
  author?: string;
  read_time?: number;
  status: 'draft' | 'published' | 'archived';
  published_at?: string;
  meta_data?: Record<string, any>;
  tags?: number[];
  featured_image?: File | string;
  views_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface DashboardStats {
  posts: {
    total: number;
    published: number;
    draft: number;
    archived: number;
    total_views: number;
  };
  projects: {
    total: number;
    completed: number;
    in_progress: number;
    featured: number;
  };
  contacts: {
    total: number;
    unread: number;
    read: number;
    replied: number;
    today: number;
  };
  cvs: {
    total: number;
    active: number;
    total_downloads: number;
  };
  categories: number;
  tags: number;
}

export interface Activity {
  type: 'post' | 'project' | 'contact';
  title: string;
  status: string;
  date: string;
  url: string;
}

export interface AnalyticsData {
  month?: string;
  date?: string;
  published?: number;
  total?: number;
  count?: number;
}
