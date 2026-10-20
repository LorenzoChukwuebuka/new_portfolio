const routes = [
    {
        path: '/admin',
        redirect: '/admin/dashboard',
    },
    {
        path: '/admin/dashboard',
        name: 'Dashboard',
        component: () => import('./views/Dashboard.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: "/admin/login",
        name: 'Login',
        component: () => import('./views/Login.vue'),
        meta: { requiresGuest: true },
    },
    {
        path: '/admin/categories',
        name: 'Categories',
        component: () => import('./views/Categories.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/posts',
        name: 'Posts',
        component: () => import('./views/Posts.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/posts/create',
        name: 'CreatePost',
        component: () => import('./views/PostForm.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/posts/:slug/edit',
        name: 'EditPost',
        component: () => import('./views/PostForm.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/projects',
        name: 'Projects',
        component: () => import('./views/Projects.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/projects/create',
        name: 'CreateProject',
        component: () => import('./views/ProjectForm.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/projects/:id/edit',
        name: 'EditProject',
        component: () => import('./views/ProjectForm.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: "/admin/tags",
        name: "tags",
        component: () => import('./views/Tags.vue'),
        meta: { requiresAuth: true },
    }, 
    {
        path: "/admin/cv",
        name: "cv",
        component: () => import('./views/CV.vue'),
        meta: { requiresAuth: true },
    },
    {
        path:"/admin/contacts",
        name:"contacts",
        component: () => import('./views/Contacts.vue'),
        meta: { requiresAuth: true },
    },
    {
        path:"/admin/comments",
        name:"comments",
        component: () => import('./views/Comments.vue'),
        meta: { requiresAuth: true },
    }
];

// const router = createRouter({
//   history: createWebHistory(),
//   routes,
// });

export default routes;
