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

const routes = [
    {
        path: '/admin/dashboard',
        name: 'Dashboard',
        component: Dashboard,
    },
    {
        path: "/admin",
        name: 'Login',
        component: Login
    },
    {
        path: '/admin/categories',
        name: 'Categories',
        component: Categories,
    },
    {
        path: '/admin/posts',
        name: 'Posts',
        component: Posts,
    },
    {
        path: '/admin/posts/create',
        name: 'CreatePost',
        component: PostForm,
    },
    {
        path: '/admin/posts/:slug/edit',
        name: 'EditPost',
        component: PostForm,
    },
    {
        path: '/admin/projects',
        name: 'Projects',
        component: Projects,
    },
    {
        path: '/admin/projects/create',
        name: 'CreateProject',
        component: ProjectForm,
    },
    {
        path: '/admin/projects/:id/edit',
        name: 'EditProject',
        component: ProjectForm,
    },
    {
        path: "/admin/tags",
        name: "tags",
        component: Tags
    }, 
    {
        path: "/admin/cv",
        name: "cv",
        component: CV
    },
    {
        path:"/admin/contacts",
        name:"contacts",
        component:Contacts
    }
];

// const router = createRouter({
//   history: createWebHistory(),
//   routes,
// });

export default routes;
