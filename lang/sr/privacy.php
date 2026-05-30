<?php

return [
    'meta' => [
        'title' => 'Politika privatnosti',
        'updated' => 'Poslednje ažuriranje: maj 2026.',
    ],

    'hero' => [
        'eyebrow' => 'Privatnost, jednostavnim rečima',
        'headline' => 'Podaci o vašem jatu su vaši.',
        'headline_accent' => 'Mi ih samo čuvamo na sigurnom.',
        'sub' => 'ChickenCare-u su potrebni nalog i baza podataka da bi radio — da sinhronizuje vaša jaja, hranu i troškove na svim uređajima. Zato ne možemo iskreno da tvrdimo „ne prikupljamo ništa“. Ono što možemo da vam kažemo jeste tačno šta čuvamo, zašto, i sve ono što smo namerno odlučili da ne radimo.',
    ],

    'pledges' => [
        'heading' => 'Šta ne radimo',
        'sub' => 'Bez zvezdica. Ovo su odluke ugrađene u sam način na koji je aplikacija napravljena.',
        'items' => [
            [
                'title' => 'Bez praćenja',
                'body' => 'Nema Google Analytics-a, nema Facebook Pixel-a, nema reklamnih trekera ni snimača sesija. Ne pratimo vas po internetu. Možete proveriti izvorni kod stranice — nema skripti za praćenje.',
            ],
            [
                'title' => 'Ne prodajemo vaše podatke',
                'body' => 'Nikada nismo prodali vaše podatke i nikada nećemo. Ne delimo ih sa oglašivačima, posrednicima u prodaji podataka ni marketinškim partnerima. Ovde ne postoji poslovni model koji zavisi od vaših informacija.',
            ],
            [
                'title' => 'Bez reklama',
                'body' => 'ChickenCare nema oglašavanje — ni banere, ni „sponzorisane“ savete, ni retargeting. Aplikaciju plaćaju ljudi koji je koriste, a ne prodaja vaše pažnje.',
            ],
            [
                'title' => 'Bez profilisanja',
                'body' => 'Čuvamo podatke o farmi koje unesete jer je to bukvalno sam proizvod. Ne analiziramo ih da bismo izgradili vaš profil ili pretpostavljali stvari o vašem životu.',
            ],
            [
                'title' => 'Bez iznenadnih trećih strana',
                'body' => 'Jedine spoljne usluge koje koristimo su one zbog kojih aplikacija radi: opcionalna Google/Facebook prijava, usluga za slanje imejlova za resetovanje lozinke i naš hosting. To je cela lista.',
            ],
            [
                'title' => 'Bez zaključavanja vaših podataka',
                'body' => 'Zatvorite nalog i sve što je vezano za njega trajno se briše. Želite kopiju ili brisanje pre toga? Pošaljite nam imejl i rešićemo to.',
            ],
        ],
    ],

    'formal' => [
        'heading' => 'Formalna verzija',
        'intro' => 'Isto to, napisano onako kako bi pravnik voleo. Kratko koliko iskrenost dozvoljava.',
        'sections' => [
            [
                'title' => 'Ko smo mi',
                'paragraphs' => [
                    'ChickenCare je veb aplikacija koja pomaže uzgajivačima živine da prate jaja, hranu, zdravlje jata i troškove. Kada u ovoj politici stoji „mi“ ili „aplikacija“, to označava ChickenCare i ljude koji ga vode.',
                ],
            ],
            [
                'title' => 'Šta prikupljamo',
                'paragraphs' => [
                    'Prikupljamo samo ono što je aplikaciji potrebno da radi. To se svodi na nekoliko grupa:',
                ],
                'items' => [
                    'Podaci o nalogu — vaše ime, imejl adresa i bezbedno heširana lozinka (nikada ne vidimo niti čuvamo vašu lozinku u čitljivom obliku).',
                    'Podaci za prijavu putem društvenih mreža — ako se prijavite preko Google-a ili Facebook-a, od tog provajdera dobijamo vaše ime, imejl adresu i URL profilne slike. Ništa više.',
                    'Podaci koje unesete — evidencija o jatu i turama, broj jaja, zalihe hrane, troškovi, ciljevi štednje i (na premium nalogu) podaci o kupcima i prodaji koje odlučite da dodate.',
                    'Podešavanja naloga — vaš jezik, tema, valuta i ciljevi.',
                    'Tehnički podaci — vaša IP adresa, tip pregledača i aktivnost sesije. Ovo čuvamo da bismo vas bezbedno prijavili i zaštitili vaš nalog, a ne da bismo vas pratili.',
                ],
            ],
            [
                'title' => 'Podaci o drugim osobama',
                'paragraphs' => [
                    'Ako koristite CRM da sačuvate imena kupaca, brojeve telefona ili beleške, vi unosite informacije o drugim ljudima. Vi ste odgovorni za to da imate valjan razlog da ih čuvate. Mi ih obrađujemo samo u vaše ime da bi funkcija radila i nikada ih ne koristimo ni za šta drugo.',
                ],
            ],
            [
                'title' => 'Kako koristimo vaše podatke',
                'paragraphs' => [
                    'Vaše informacije koristimo da bismo:',
                ],
                'items' => [
                    'Pokretali funkcije za koje ste se prijavili i držali vaše podatke sinhronizovanim.',
                    'Prijavili vas i održavali vaš nalog bezbednim.',
                    'Slali neophodne imejlove vezane za nalog, kao što su resetovanje lozinke i važna obaveštenja o usluzi. Ne šaljemo marketinške imejlove.',
                ],
            ],
            [
                'title' => 'Kolačići',
                'paragraphs' => [
                    'Koristimo mali broj kolačića i nijedan vas ne prati:',
                ],
                'items' => [
                    'Kolačić sesije koji vas drži prijavljenim. Aplikacija bez njega ne može da radi.',
                    'Kolačić podešavanja koji pamti vaš izbor jezika i teme.',
                ],
            ],
            [
                'title' => 'Treće strane na koje se oslanjamo',
                'paragraphs' => [
                    'Ovu listu držimo što kraćom. Svaka od njih dobija samo ono najnužnije da bi obavila svoj posao:',
                ],
                'items' => [
                    'Google i Facebook — samo ako odlučite da se prijavite preko njih, i samo da potvrde ko ste.',
                    'Naš provajder imejla — da isporuči imejlove vezane za nalog, kao što je resetovanje lozinke.',
                    'Naš hosting provajder — koji čuva bazu podataka u kojoj žive vaši podaci.',
                ],
            ],
            [
                'title' => 'Kako štitimo vaše podatke',
                'paragraphs' => [
                    'Lozinke se heširaju pomoću bcrypt-a, saobraćaj se odvija preko HTTPS-a, a sesije koriste standardnu zaštitu od falsifikovanja zahteva sa drugih sajtova (CSRF). Nijedan sistem nije savršen, ali ne preskačemo osnovne stvari.',
                ],
            ],
            [
                'title' => 'Čuvanje i brisanje podataka',
                'paragraphs' => [
                    'Vaše podatke čuvamo dokle god je vaš nalog aktivan. Kada zatvorite nalog, svaki zapis povezan sa njim — jato, jaja, troškovi, kupci i ostalo — trajno se briše zajedno sa njim. Takođe nam u bilo kom trenutku možete poslati imejl da zatražite kopiju svojih podataka ili da ih obrišemo.',
                ],
            ],
            [
                'title' => 'Vaša prava',
                'paragraphs' => [
                    'U zavisnosti od toga gde živite, možete imati pravo da pristupite, ispravite, izvezete ili obrišete svoje lične podatke, kao i da se usprotivite načinu na koji se koriste. Većinu toga možete uraditi iz podešavanja naloga, a za sve ostalo nam jednostavno pišite. Nećemo vam naplatiti niti otežati postupak.',
                ],
            ],
            [
                'title' => 'Privatnost dece',
                'paragraphs' => [
                    'ChickenCare nije namenjen deci mlađoj od 16 godina i ne prikupljamo svesno njihove podatke. Ako verujete da je dete napravilo nalog, kontaktirajte nas i uklonićemo ga.',
                ],
            ],
            [
                'title' => 'Izmene ove politike',
                'paragraphs' => [
                    'Ako izmenimo ovu politiku, ažuriraćemo datum na vrhu stranice. Ako je izmena značajna, potrudićemo se da vas obavestimo direktno.',
                ],
            ],
        ],
    ],

    'contact' => [
        'heading' => 'Pitanja?',
        'body' => 'Ako vam je ovde nešto nejasno, ili želite da pristupite svojim podacima ili da ih obrišete, pišite nam. Čita ih pravi čovek.',
        'email' => 'hello@chickencare.app',
    ],
];
