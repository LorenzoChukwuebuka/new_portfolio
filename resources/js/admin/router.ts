import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './views/Dashboard.vue';
import Categories from './views/Categories.vue';
import Posts from './views/Posts.vue';
import PostForm from './views/PostForm.vue';
import Projects from './views/Projects.vue';
import ProjectForm from './views/ProjectForm.vue';
import Login from './views/Login.vue';
import Tags from './views/Tags.vue';
import CV from './views/CV.vue';
import Contacts from './views/Contacts.vue';
import Comments from './views/Comments.vue';

const routes = [
    {
        path: '/admin',
        redirect: '/admin/dashboard',
    },
    {
        path: '/admin/dashboard',
        name: 'Dashboard',
        component: Dashboard,
        meta: { requiresAuth: true },
    },
    {
        path: "/admin/login",
        name: 'Login',
        component: Login,
        meta: { requiresGuest: true },
    },
    {
        path: '/admin/categories',
        name: 'Categories',
        component: Categories,
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/posts',
        name: 'Posts',
        component: Posts,
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/posts/create',
        name: 'CreatePost',
        component: PostForm,
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/posts/:slug/edit',
        name: 'EditPost',
        component: PostForm,
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/projects',
        name: 'Projects',
        component: Projects,
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/projects/create',
        name: 'CreateProject',
        component: ProjectForm,
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/projects/:id/edit',
        name: 'EditProject',
        component: ProjectForm,
        meta: { requiresAuth: true },
    },
    {
        path: "/admin/tags",
        name: "tags",
        component: Tags,
        meta: { requiresAuth: true },
    }, 
    {
        path: "/admin/cv",
        name: "cv",
        component: CV,
        meta: { requiresAuth: true },
    },
    {
        path:"/admin/contacts",
        name:"contacts",
        component:Contacts,
        meta: { requiresAuth: true },
    },
    {
        path:"/admin/comments",
        name:"comments",
        component:Comments,
        meta: { requiresAuth: true },
    }
];

// const router = createRouter({
//   history: createWebHistory(),
//   routes,
// });

export default routes;
