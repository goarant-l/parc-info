<?php
/**
 * Configuration de l'application.
 * Adapter ces valeurs à votre environnement (XAMPP, WAMP, hébergeur…).
 */
return [
    'app_name' => 'ParcScope',
    'db' => [
        'host'    => 'localhost',
        'name'    => 'parc_info',
        'user'    => 'root',   // en production : un utilisateur dédié, jamais root
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
];
