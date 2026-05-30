<?php

return [
    'meta' => [
        'title' => 'Privacy Policy',
        'updated' => 'Last updated: May 2026',
    ],

    'hero' => [
        'eyebrow' => 'Privacy, in plain language',
        'headline' => 'Your flock data is yours.',
        'headline_accent' => 'We just keep it safe.',
        'sub' => "ChickenCare needs an account and a database to do its job — sync your eggs, feed, and expenses across your devices. So we can't honestly claim \"we collect nothing.\" What we can tell you is exactly what we store, why, and everything we deliberately chose not to do.",
    ],

    // Approachable "what we don't do" cards
    'pledges' => [
        'heading' => "What we don't do",
        'sub' => 'No asterisks. These are choices baked into how the app is built.',
        'items' => [
            [
                'title' => 'No tracking',
                'body' => 'No Google Analytics, no Facebook Pixel, no ad trackers, no session recorders. We do not follow you around the web. You can check the page source — there are no tracking scripts.',
            ],
            [
                'title' => 'No selling your data',
                'body' => 'We have never sold your data and never will. It is not shared with advertisers, data brokers, or marketing partners. There is no business model here that depends on your information.',
            ],
            [
                'title' => 'No ads',
                'body' => 'ChickenCare has no advertising — not banners, not "sponsored" tips, not retargeting. The app is paid for by the people who use it, not by selling attention.',
            ],
            [
                'title' => 'No profiling',
                'body' => 'We store the farm data you enter because that is literally the product. We do not analyse it to build a profile of you or guess things about your life.',
            ],
            [
                'title' => 'No surprise third parties',
                'body' => 'The only outside services we use are the ones that make the app work: optional Google/Facebook sign-in, an email provider for password resets, and our hosting. That is the whole list.',
            ],
            [
                'title' => 'No lock-in on your data',
                'body' => 'Close your account and everything tied to it is permanently deleted. Want a copy or a deletion before then? Email us and we will sort it out.',
            ],
        ],
    ],

    // Formal, legal section
    'formal' => [
        'heading' => 'The formal version',
        'intro' => 'The same thing, written the way a lawyer would like it. Kept as short as honesty allows.',
        'sections' => [
            [
                'title' => 'Who we are',
                'paragraphs' => [
                    'ChickenCare is a web app that helps poultry keepers track eggs, feed, flock health, and expenses. When this policy says "we" or "the app", it means ChickenCare and the people who run it.',
                ],
            ],
            [
                'title' => 'What we collect',
                'paragraphs' => [
                    'We only collect what the app needs to work. That falls into a few buckets:',
                ],
                'items' => [
                    'Account details — your name, email address, and a securely hashed password (we never see or store your plain password).',
                    'Social sign-in details — if you log in with Google or Facebook, we receive your name, email address, and profile picture URL from that provider. Nothing else.',
                    'The data you enter — your flock and batch records, egg counts, feed inventory, expenses, savings goals, and (on premium) customer and sales records you choose to add.',
                    'Account preferences — your language, theme, currency, and goal settings.',
                    'Technical data — your IP address, browser type, and session activity. We keep this to sign you in securely and protect your account, not to track you.',
                ],
            ],
            [
                'title' => 'Data about other people',
                'paragraphs' => [
                    'If you use the CRM to store customer names, phone numbers, or notes, you are adding other people\'s information. You are responsible for having a good reason to keep it. We only process it on your behalf so the feature works, and we never use it for anything else.',
                ],
            ],
            [
                'title' => 'How we use your data',
                'paragraphs' => [
                    'We use your information to:',
                ],
                'items' => [
                    'Run the features you signed up for and keep your data in sync.',
                    'Sign you in and keep your account secure.',
                    'Send essential account emails, such as password resets and important service notices. We do not send marketing emails.',
                ],
            ],
            [
                'title' => 'Cookies',
                'paragraphs' => [
                    'We use a small number of cookies, and none of them track you:',
                ],
                'items' => [
                    'A session cookie that keeps you logged in. The app cannot work without it.',
                    'A preference cookie that remembers your language and theme choice.',
                ],
            ],
            [
                'title' => 'Third parties we rely on',
                'paragraphs' => [
                    'We keep this list as short as possible. Each of these only receives the minimum needed to do its job:',
                ],
                'items' => [
                    'Google and Facebook — only if you choose to sign in with them, and only to confirm who you are.',
                    'Our email provider — to deliver account emails like password resets.',
                    'Our hosting provider — which stores the database your data lives in.',
                ],
            ],
            [
                'title' => 'How we protect your data',
                'paragraphs' => [
                    'Passwords are hashed with bcrypt, traffic is served over HTTPS, and sessions use standard protections against cross-site request forgery. No system is perfect, but we do not cut corners on the basics.',
                ],
            ],
            [
                'title' => 'Keeping and deleting data',
                'paragraphs' => [
                    'We keep your data for as long as your account is active. When you close your account, every record linked to it — flock, eggs, expenses, customers, and the rest — is permanently deleted along with it. You can also email us at any time to request a copy of your data or ask us to delete it.',
                ],
            ],
            [
                'title' => 'Your rights',
                'paragraphs' => [
                    'Depending on where you live, you may have the right to access, correct, export, or delete your personal data, and to object to how it is used. You can do most of this from your account settings, and for anything else, just email us. We will not charge you or make it difficult.',
                ],
            ],
            [
                'title' => "Children's privacy",
                'paragraphs' => [
                    'ChickenCare is not intended for children under 16, and we do not knowingly collect their data. If you believe a child has created an account, contact us and we will remove it.',
                ],
            ],
            [
                'title' => 'Changes to this policy',
                'paragraphs' => [
                    'If we change this policy, we will update the date at the top of the page. If the change is significant, we will do our best to let you know directly.',
                ],
            ],
        ],
    ],

    'contact' => [
        'heading' => 'Questions?',
        'body' => 'If anything here is unclear, or you want to access or delete your data, email us. A real person reads it.',
        'email' => 'hello@chickencare.app',
    ],
];
