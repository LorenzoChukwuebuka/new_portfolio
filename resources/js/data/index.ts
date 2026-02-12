import type { Skill, NavLink, ContactInfo } from '../types'

export const skills: Skill[] = [
    {
        category: 'Languages',
        items: ['TypeScript', 'PHP', 'Go', 'Python', 'SQL'],
    },
    {
        category: 'Frontend',
        items: ['Vue 3', 'React', 'Tailwind CSS', 'Inertia.js', 'Vite'],
    },
    {
        category: 'Backend',
        items: ['Laravel', 'Node.js', 'FastAPI', 'GraphQL', 'REST'],
    },
    {
        category: 'Infrastructure',
        items: ['Docker', 'Kubernetes', 'AWS', 'PostgreSQL', 'Redis'],
    },
]

export const navLinks: NavLink[] = [
    { label: 'Work', href: '/#work' },
    { label: 'Blog', href: '/#blog' },
    { label: 'Contact', href: '/#contact' },
]

export const contactInfo: ContactInfo = {
    email: 'lawrenceobi2@gmail.com',
    phone: '(+234)813-451-4639',
    location: 'Lagos, Nigera',
    github: 'https://github.com/LorenzoChukwuebuka',
    linkedin: 'https://www.linkedin.com/in/lawrence-chukwuebuka-obi-4375b5191/',
    twitter: 'https://twitter.com',
}
