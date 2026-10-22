<?php

return [
    'name' => 'Lorenzo Chukwuebuka Obi',
    'short_name' => 'Lorenzo Chukwuebuka',
    'role' => 'Laravel, Go & SaaS Engineer',
    'tagline' => 'I build reliable software for serious teams.',
    'summary' => 'I work with founders and teams to build production-ready Laravel, Go, payment, dashboard, and SaaS systems with a focus on clarity, reliability, and long-term maintainability.',
    'location' => 'Lagos, Nigeria',
    'email' => env('PORTFOLIO_EMAIL', 'lawrenceobi2@gmail.com'),
    'phone' => env('PORTFOLIO_PHONE', '(+234) 813-451-4639'),
    'profile_image' => '/storage/images/profile.jpg',
    'since_year' => (int) env('PORTFOLIO_SINCE_YEAR', 2020),
    'social' => [
        'github' => 'https://github.com/LorenzoChukwuebuka',
        'linkedin' => 'https://www.linkedin.com/in/lawrence-chukwuebuka-obi-4375b5191/',
        'twitter' => null,
    ],
    'navigation' => [
        ['label' => 'Work', 'href' => '/#work'],
        ['label' => 'Writing', 'href' => '/#blog'],
        ['label' => 'Contact', 'href' => '/#contact'],
    ],
    'skills' => [
        ['category' => 'Languages', 'items' => ['TypeScript', 'PHP', 'Go', 'Python', 'SQL']],
        ['category' => 'Frontend', 'items' => ['Vue 3', 'React', 'Tailwind CSS', 'Inertia.js', 'Vite']],
        ['category' => 'Backend', 'items' => ['Laravel', 'Node.js', 'FastAPI', 'GraphQL', 'REST']],
        ['category' => 'Infrastructure', 'items' => ['Docker', 'Kubernetes', 'AWS', 'PostgreSQL', 'Redis']],
    ],
];
