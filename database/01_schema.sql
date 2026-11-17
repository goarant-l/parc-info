-- =====================================================================
--  ParcScope - Inventaire du parc informatique (BTS SIO - Bloc 1)
--  V0 : schéma de la base de données (MySQL / MariaDB)
--  À importer en premier, puis importer 02_donnees.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS parc_info
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE parc_info;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS signalements;
DROP TABLE IF EXISTS machine_logiciel;
DROP TABLE IF EXISTS logiciels;
DROP TABLE IF EXISTS machines;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS salles;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
--  Salles (V0 : une seule salle, mais le modèle permet d'en ajouter)
-- ---------------------------------------------------------------------
CREATE TABLE salles (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom         VARCHAR(80)  NOT NULL,
  description VARCHAR(255) NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Utilisateurs - niveau 1 = Élève, 2 = Professeur, 3 = Administrateur
-- ---------------------------------------------------------------------
CREATE TABLE utilisateurs (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom                VARCHAR(60)  NOT NULL,
  prenom             VARCHAR(60)  NOT NULL,
  email              VARCHAR(120) NOT NULL,
  mot_de_passe       VARCHAR(255) NOT NULL,   -- hash bcrypt (password_hash)
  niveau             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  salle_id           INT UNSIGNED NULL,       -- salle suivie (professeur)
  actif              TINYINT(1)   NOT NULL DEFAULT 1,
  derniere_connexion DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_utilisateurs_email (email),
  CONSTRAINT chk_niveau CHECK (niveau BETWEEN 1 AND 3),
  CONSTRAINT fk_utilisateurs_salle FOREIGN KEY (salle_id)
    REFERENCES salles (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Machines (configuration matérielle + système)
-- ---------------------------------------------------------------------
CREATE TABLE machines (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  salle_id      INT UNSIGNED NOT NULL,
  nom           VARCHAR(60)  NOT NULL,
  type          VARCHAR(30)  NOT NULL DEFAULT 'Poste fixe',  -- Poste fixe / Portable / Serveur
  marque        VARCHAR(60)  NULL,
  modele        VARCHAR(80)  NULL,
  numero_serie  VARCHAR(60)  NULL,
  cpu           VARCHAR(100) NULL,
  ram_go        SMALLINT UNSIGNED NULL,
  disque_go     INT UNSIGNED NULL,
  disque_type   VARCHAR(20)  NULL,                           -- SSD / HDD / NVMe
  os            VARCHAR(60)  NULL,
  os_version    VARCHAR(60)  NULL,
  ip            VARCHAR(45)  NULL,
  mac           VARCHAR(17)  NULL,
  statut        VARCHAR(20)  NOT NULL DEFAULT 'actif',      -- actif / panne / maintenance
  date_achat    DATE         NULL,
  date_ajout    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_machines_nom (nom),
  CONSTRAINT fk_machines_salle FOREIGN KEY (salle_id)
    REFERENCES salles (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Logiciels (catalogue) et installation sur les machines
-- ---------------------------------------------------------------------
CREATE TABLE logiciels (
  id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom     VARCHAR(80) NOT NULL,
  editeur VARCHAR(80) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_logiciels_nom (nom)
) ENGINE=InnoDB;

CREATE TABLE machine_logiciel (
  machine_id  INT UNSIGNED NOT NULL,
  logiciel_id INT UNSIGNED NOT NULL,
  version     VARCHAR(40)  NULL,
  PRIMARY KEY (machine_id, logiciel_id),
  CONSTRAINT fk_ml_machine  FOREIGN KEY (machine_id)  REFERENCES machines (id)  ON DELETE CASCADE,
  CONSTRAINT fk_ml_logiciel FOREIGN KEY (logiciel_id) REFERENCES logiciels (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Signalements de panne
--  statut : ouvert -> valide | rejete ; valide -> resolu
-- ---------------------------------------------------------------------
CREATE TABLE signalements (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  machine_id     INT UNSIGNED NOT NULL,
  utilisateur_id INT UNSIGNED NULL,
  description    TEXT         NOT NULL,
  statut         VARCHAR(20)  NOT NULL DEFAULT 'ouvert',
  date_creation  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_traitement DATETIME    NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_signalements_machine FOREIGN KEY (machine_id)
    REFERENCES machines (id) ON DELETE CASCADE,
  CONSTRAINT fk_signalements_user FOREIGN KEY (utilisateur_id)
    REFERENCES utilisateurs (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Réservations de matériel (créneau sur une machine)
--  statut : active -> annulee
--  Réservé aux niveaux 2 (professeur) et 3 (administrateur)
-- ---------------------------------------------------------------------
CREATE TABLE reservations (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  machine_id     INT UNSIGNED NOT NULL,
  utilisateur_id INT UNSIGNED NULL,
  debut          DATETIME     NOT NULL,
  fin            DATETIME     NOT NULL,
  motif          VARCHAR(200) NOT NULL,
  statut         VARCHAR(20)  NOT NULL DEFAULT 'active',
  date_creation  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_reservations_machine_debut (machine_id, debut),
  CONSTRAINT chk_reservations_dates CHECK (fin > debut),
  CONSTRAINT fk_reservations_machine FOREIGN KEY (machine_id)
    REFERENCES machines (id) ON DELETE CASCADE,
  CONSTRAINT fk_reservations_user FOREIGN KEY (utilisateur_id)
    REFERENCES utilisateurs (id) ON DELETE SET NULL
) ENGINE=InnoDB;
