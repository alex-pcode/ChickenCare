<?php

return [
    'required' => 'Polje :attribute je obavezno.',
    'string' => 'Polje :attribute mora biti tekst.',
    'email' => 'Polje :attribute mora biti važeća adresa e-pošte.',
    'integer' => 'Polje :attribute mora biti ceo broj.',
    'numeric' => 'Polje :attribute mora biti broj.',
    'enum' => 'Izabrana vrednost za polje :attribute nije ispravna.',
    'in' => 'Izabrana vrednost za polje :attribute nije ispravna.',
    'current_password' => 'Lozinka nije ispravna.',
    'min' => [
        'numeric' => 'Polje :attribute mora biti najmanje :min.',
        'string' => 'Polje :attribute mora imati najmanje :min karaktera.',
    ],
    'max' => [
        'numeric' => 'Polje :attribute ne sme biti veće od :max.',
        'string' => 'Polje :attribute ne sme imati više od :max karaktera.',
    ],
    'attributes' => [
        'name' => 'ime',
        'email' => 'adresa e-pošte',
        'chicken_goal' => 'cilj uzgoja',
        'yearly_egg_goal' => 'godišnji cilj proizvodnje jaja',
        'egg_price' => 'cena po jajetu',
        'locale' => 'jezik',
        'date' => 'datum',
        'category' => 'kategorija',
        'description' => 'opis',
        'amount' => 'iznos',
        'count' => 'broj jaja',
        'size' => 'velicina',
        'color' => 'boja',
        'notes' => 'napomene',
    ],
];
