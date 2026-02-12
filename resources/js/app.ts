import { createApp } from 'vue'
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import '../css/app.css'

// Pages
import Home from './pages/Home.vue'
import Projects from './pages/Projects.vue'
import ProjectDetail from './pages/ProjectDetail.vue'
import Blog from './pages/Blog.vue'
import BlogPost from './pages/BlogPost.vue'
import Test from './test.vue'
import App from './app.vue'
import AdminRoute from "./admin/router"
import api from './admin/utils/api'

// ─── Routes ──────────────────────────────────────────────────────────────────
const routes: RouteRecordRaw[] = [

    ...AdminRoute
    // {
    //     path: '/',
    //     name: 'home',
    //     component: Home,
    //     meta: { title: 'Alex Johnson — Software Engineer' },
    // },
    // {
    //     path: '/projects',
    //     name: 'projects',
    //     component: Projects,
    //     meta: { title: 'Projects — Alex Johnson' },
    // },
    // {
    //     path: '/projects/:slug',
    //     name: 'project-detail',
    //     component: ProjectDetail,
    //     props: true,
    //     meta: { title: 'Project — Alex Johnson' },
    // },
    // {
    //     path: '/blog',
    //     name: 'blog',
    //     component: Blog,
    //     meta: { title: 'Blog — Alex Johnson' },
    // },
    // {
    //     path: '/blog/:slug',
    //     name: 'blog-post',
    //     component: BlogPost,
    //     props: true,
    //     meta: { title: 'Blog Post — Alex Johnson' },
    // },
]

// ─── Router ──────────────────────────────────────────────────────────────────
const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, _from, savedPosition) {
        if (savedPosition) return savedPosition
        if (to.hash) {
            return {
                el: to.hash,
                behavior: 'smooth',
                top: 80, // offset for fixed navbar
            }
        }
        return { top: 0, behavior: 'smooth' }
    },
})


// Navigation guards
router.beforeEach((to, from, next) => {
    const isAuthenticated = api.isAuthenticated();

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/admin/login');
    } else if (to.meta.requiresGuest && isAuthenticated) {
        next('/admin/dashboard');
    } else {
        next();
    }
});

// Update document title
// router.afterEach(to => {
//     document.title = (to.meta.title as string) ?? 'Alex Johnson'
// })

// ─── Scroll Observer (fade-in elements) ──────────────────────────────────────
function initScrollObserver() {
    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible')
                }
            })
        },
        { threshold: 0.08, rootMargin: '0px 0px -80px 0px' }
    )

    const observe = () => {
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el))
    }

    // Re-observe on route change
    router.afterEach(() => {
        // Wait for DOM update
        setTimeout(observe, 100)
    })
    observe()
}

// ─── Mount ───────────────────────────────────────────────────────────────────
const app = createApp({
    // template: '<RouterView />',
})

app.component('test-component', Test)
app.component('app', App)
app.component('home-component', Home)
app.component('blog-component', Blog)
app.component('blogpost-component', BlogPost)
app.component('projects-component', Projects)
app.component('projectdetail-component', ProjectDetail)
app.component('projectdetails-component', ProjectDetail)
    

app.use(router)
app.mount('#app')

// Init after mount
initScrollObserver()
