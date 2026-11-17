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
        'dev_ip_simulee' => '192.168.20.11',   // simule le poste inf3-01 ; '' en production
    ],
];
