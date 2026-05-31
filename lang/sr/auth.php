<?php

return [
    'failed' => 'Ovi podaci za prijavu se ne poklapaju sa našom evidencijom.',
    'password' => 'Uneta lozinka nije ispravna.',
    'throttle' => 'Previše pokušaja prijave. Pokušajte ponovo za :seconds sekundi.',
    'guest' => [
        'default_title' => 'Dobro dosli',
    ],
    'fields' => [
        'name' => 'Ime',
        'email' => 'E-posta',
        'password' => 'Lozinka',
        'password_confirmation' => 'Potvrdite lozinku',
    ],
    'pages' => [
        'login' => [
            'title' => 'Dobrodosli nazad',
            'remember' => 'Zapamti me',
            'forgot_password' => 'Zaboravili ste lozinku?',
            'submit' => 'Prijavite se',
        ],
        'register' => [
            'title' => 'Napravite nalog',
            'already_registered' => 'Vec ste registrovani?',
            'submit' => 'Registrujte se',
        ],
        'forgot_password' => [
            'title' => 'Zaboravljena lozinka',
            'description' => 'Zaboravili ste lozinku? Nema problema. Posaljite nam svoju adresu e-poste i poslacemo vam link za resetovanje lozinke kako biste mogli da odaberete novu.',
            'submit' => 'Posalji link za resetovanje lozinke',
        ],
        'reset_password' => [
            'title' => 'Resetuj lozinku',
            'submit' => 'Resetuj lozinku',
        ],
        'confirm_password' => [
            'title' => 'Potvrdite lozinku',
            'description' => 'Ovo je zasticeni deo aplikacije. Potvrdite lozinku pre nego sto nastavite.',
            'submit' => 'Potvrdi',
        ],
        'verify_email' => [
            'title' => 'Verifikujte e-postu',
            'description' => 'Hvala na registraciji! Pre nego sto pocnete, potvrdite adresu e-poste klikom na link koji smo upravo poslali. Ako niste dobili poruku, rado cemo poslati novu.',
            'status' => 'Novi link za verifikaciju je poslat na adresu e-poste koju ste naveli prilikom registracije.',
            'resend' => 'Posalji ponovo verifikacioni imejl',
            'logout' => 'Odjavite se',
        ],
    ],
    'social' => [
        'aria_label' => 'Opcije prijave putem drustvenih mreza',
        'continue_with' => 'Nastavite putem :provider',
        'sign_up_with' => 'Registrujte se putem :provider',
        'or_continue_with_email' => 'ili nastavite e-postom',
        'or_sign_up_with_email' => 'ili se registrujte e-postom',
        'providers' => [
            'google' => 'Google',
            'facebook' => 'Facebook',
        ],
        'errors' => [
            'not_configured' => 'Prijava preko :provider jos nije podesena.',
            'unable_to_authenticate' => 'Nije moguce potvrditi identitet preko :provider. Pokusajte ponovo.',
            'email_missing' => ':provider nije vratio adresu e-poste. Registrujte se putem e-poste i lozinke.',
        ],
    ],
];
