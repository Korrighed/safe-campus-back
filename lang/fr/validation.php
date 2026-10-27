<?php

// Partiel : uniquement les regles utilisees par les formulaires
// d'authentification (connexion, inscription). Laravel ne livre pas de
// traduction fr — une regle absente d'ici affiche sa cle brute en locale fr.
return [

    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'password' => [
        'letters' => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le champ :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le champ :attribute doit contenir au moins un caractère spécial.',
        'uncompromised' => 'Ce :attribute est apparu dans une fuite de données. Merci d\'en choisir un autre.',
    ],
    'required' => 'Le champ :attribute est obligatoire.',
    'same' => 'Les champs :attribute et :other doivent être identiques.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',

];
