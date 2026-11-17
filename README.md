# ParcScope — Inventaire du parc informatique (V0)

Projet BTS SIO – Bloc 1 (méthode ScrumBan, un sprint = une version).
Site web dynamique en **HTML / CSS / PHP / JavaScript + MySQL** qui affiche la configuration
matérielle, les composants système et l'environnement logiciel des ordinateurs d'une salle.

## Installation en local (XAMPP / WAMP / MAMP)

1. Copier le dossier `parc-info` dans `htdocs` (XAMPP).
2. Ouvrir **phpMyAdmin** → onglet *Importer* → importer, **dans cet ordre** :
   1. `database/01_schema.sql` (crée la base `parc_info` et les tables)
   2. `database/02_donnees.sql` (jeu de données fictives)

   Base déjà importée avant l'ajout des réservations ? Importez seulement `database/03_reservations.sql`.
   Pour renommer les machines de démonstration (inf3-01…, processeur et système) sur une base déjà importée : `database/04_maj_materiel.sql`.
3. Vérifier `config/config.php` (hôte, utilisateur, mot de passe MySQL).
4. Ouvrir <http://localhost/parc-info/login.php>.

Prérequis : PHP 8.0 ou plus, extension `pdo_mysql`, MySQL 8 / MariaDB 10.4 ou plus.

## Comptes de démonstration

| Niveau | E-mail | Mot de passe |
|---|---|---|
| 1 – Élève | eleve@parc.local | eleve123 |
| 2 – Professeur | prof@parc.local | prof1234 |
| 3 – Administrateur | admin@parc.local | admin123 |

## Droits par niveau (cumulatifs)

| Fonctionnalité | Élève (1) | Prof. (2) | Admin (3) |
|---|:-:|:-:|:-:|
| Se connecter, tableau de bord | ✔ | ✔ | ✔ |
| Consulter les machines (config, système, logiciels) | ✔ | ✔ | ✔ |
| Signaler une panne | ✔ | ✔ | ✔ |
| Voir ses propres signalements | ✔ | | |
| Voir les signalements de sa salle, les valider / rejeter / résoudre | | ✔ | ✔ |
| Ajouter / modifier une machine | | ✔ | ✔ |
| Réserver du matériel (menu Réservations), annuler ses réservations | | ✔ | ✔ |
| Annuler les réservations des autres | | | ✔ |
| Supprimer une machine | | | ✔ |
| Gérer les comptes (créer, niveau, désactiver, supprimer) | | | ✔ |

Les droits sont vérifiés **côté serveur** à chaque page (`require_level()` dans `includes/auth.php`) :
masquer un bouton ne suffit pas, l'accès direct à l'URL est refusé (erreur 403).

## Structure du projet

```
parc-info/
├── index.php            Tableau de bord
├── login.php / logout.php
├── machines.php         Liste + recherche + filtre par statut
├── machine.php          Fiche (onglets Matériel / Système / Logiciels / Signalements)
├── machine_form.php     Ajout et modification (niveau 2+)
├── machine_delete.php   Suppression (niveau 3)
├── signalements.php     Suivi et traitement des pannes
├── reservations.php     Réservation de matériel (niveaux 2 et 3)
├── comptes.php          Gestion des comptes (niveau 3)
├── includes/            bootstrap, db (PDO), auth (droits), functions, header, footer
├── config/config.php    Paramètres de connexion à la base
├── database/            01_schema.sql, 02_donnees.sql, 03_reservations.sql et 04_maj_materiel.sql (mises à jour)
└── assets/              css/style.css, js/app.js
```

## Modèle de données

`salles` ← `machines` → `machine_logiciel` ← `logiciels` ; `utilisateurs` ; `signalements` (→ machine, → utilisateur) ; `reservations` (→ machine, → utilisateur).
Cycle d'un signalement : `ouvert` → `valide` (machine « en panne ») → `resolu` (machine « en service »),
ou `ouvert` → `rejete`.

## Sécurité mise en place

- Mots de passe hachés (`password_hash` / `password_verify`), jamais stockés en clair
- Requêtes SQL **préparées** (PDO) → pas d'injection SQL
- Sortie HTML échappée (`e()`) → pas de XSS
- Jeton **CSRF** sur tous les formulaires qui modifient des données (les suppressions se font en POST)
- `session_regenerate_id()` à la connexion, cookie de session `HttpOnly` + `SameSite`
- Dossiers `includes/`, `config/`, `database/` interdits d'accès direct (`.htaccess`, Apache)

## Tests réalisés (sprint 1)

- Consultation : liste, recherche, filtre, fiche détaillée, machine inexistante (404)
- Ajout : machine valide, champs invalides (nom vide ou en double, IP, MAC, RAM, statut)
- Modification et suppression d'une machine (avec suppression en cascade des signalements)
- Droits : un élève ne peut ni ajouter, ni supprimer, ni accéder aux comptes ; un professeur ne peut pas supprimer
- Signalements : création, validation, résolution, rejet, changement automatique du statut de la machine
- Comptes : création, changement de niveau, désactivation, suppression, connexion avec le nouveau compte
- Réservations : accès refusé aux élèves (403), créneaux qui se chevauchent refusés, créneau passé / machine en panne / dates et heures invalides refusés, annulation (les siennes pour un professeur, toutes pour l'admin), suppression en cascade avec la machine

## Mise en ligne (étape suivante)

1. Choisir un hébergement avec PHP + MySQL (hébergeur mutualisé, ou VPS Linux avec Apache/Nginx + PHP-FPM + MariaDB).
2. Importer la base, créer un **utilisateur MySQL dédié** (pas `root`) et le renseigner dans `config/config.php`.
3. Activer **HTTPS** (Let's Encrypt) — le cookie de session passe alors automatiquement en `Secure`.
4. **Supprimer les comptes de démonstration** (ou changer leurs mots de passe) et masquer l'encart de `login.php`.
5. Sous Nginx, reproduire l'interdiction d'accès aux dossiers `includes/`, `config/` et `database/` (les `.htaccess` ne fonctionnent que sous Apache).

## Pistes pour les prochains sprints

- V1 : saisie des logiciels installés sur une machine, import d'une configuration depuis un script de collecte (JSON)
- V2 : export PDF/CSV des rapports, calendrier visuel des réservations, historique des modifications
- V3 : déploiement de paquets, plusieurs salles, journal des connexions
