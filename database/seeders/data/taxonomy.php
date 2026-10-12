<?php

// Starting taxonomy. Admins can extend it from Admin → Catalogue → Skills.
return [
    ['Software engineering', 'code', 'Core programming languages and engineering practice.', [
        'JavaScript' => 'js,ecmascript,es6', 'TypeScript' => 'ts', 'Python' => 'py', 'Java' => '', 'C#' => 'csharp,dotnet,.net', 'Go' => 'golang', 'PHP' => '',
    ]],
    ['Frontend', 'layout-template', 'Interfaces people actually enjoy using.', [
        'React' => 'reactjs,react.js,next.js,nextjs', 'Vue' => 'vuejs,nuxt', 'Angular' => '', 'HTML & CSS' => 'html,css,tailwind,sass', 'Web accessibility' => 'a11y,wcag',
    ]],
    ['Backend & APIs', 'server', 'Services, data and the plumbing behind products.', [
        'Node.js' => 'node,express,nestjs', 'Laravel' => 'laravel,php framework', 'Django' => 'python web', 'REST API design' => 'rest,api', 'GraphQL' => 'gql',
    ]],
    ['Mobile', 'smartphone', 'Native and cross-platform apps.', [
        'Flutter' => 'dart', 'React Native' => 'expo', 'Kotlin (Android)' => 'android,kotlin', 'Swift (iOS)' => 'ios,swiftui',
    ]],
    ['Data & AI', 'brain-circuit', 'Data pipelines, analytics and AI-powered products.', [
        'SQL' => 'mysql,postgres,postgresql,database', 'Data engineering' => 'etl,airflow,spark', 'Machine learning' => 'ml,scikit,pytorch,tensorflow', 'AI application development' => 'llm,genai,rag,ai engineer',
    ]],
    ['Cloud & DevOps', 'cloud', 'Shipping and running software reliably.', [
        'AWS' => 'amazon web services', 'Azure' => 'microsoft cloud', 'Google Cloud' => 'gcp', 'Docker' => 'containers', 'Kubernetes' => 'k8s', 'CI/CD' => 'github actions,gitlab ci,jenkins,devops',
    ]],
    ['Cybersecurity', 'shield-check', 'Defending systems, code and people.', [
        'Application security' => 'appsec,owasp,secure code', 'Security operations' => 'soc,siem', 'Incident response' => 'dfir', 'Penetration testing (knowledge)' => 'pentest,ethical hacking',
    ]],
    ['QA & testing', 'bug', 'Quality as a habit, not an afterthought.', [
        'Manual QA' => 'testing,qa', 'Test automation' => 'selenium,cypress,playwright', 'Performance testing' => 'jmeter,k6,load testing',
    ]],
    ['Infrastructure & networking', 'network', 'The networks and servers everything runs on.', [
        'Linux administration' => 'linux,bash,sysadmin', 'Networking' => 'routing,switching,ccna,tcp/ip', 'Windows Server' => 'active directory',
    ]],
    ['Design & product', 'pen-tool', 'Designing the right thing, the right way.', [
        'UI/UX design' => 'ux,ui,product design,figma', 'Product management' => 'pm,product manager', 'Business analysis' => 'ba,requirements',
    ]],
    ['Project & service management', 'kanban', 'Getting teams to done.', [
        'Agile delivery' => 'agile,scrum,kanban,scrum master', 'IT service management' => 'itil,itsm,service desk',
    ]],
];
