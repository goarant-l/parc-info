-- =====================================================================
--  Migration : ajout des réservations de matériel
--  À importer UNIQUEMENT si la base parc_info existe déjà (01 et 02 déjà importés).
--  Si vous réimportez 01_schema.sql puis 02_donnees.sql, ce fichier est inutile.
-- =====================================================================

USE parc_info;

CREATE TABLE IF NOT EXISTS reservations (
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

INSERT IGNORE INTO reservations (id, machine_id, utilisateur_id, debut, fin, motif, statut, date_creation) VALUES
  (1, 1, 2, '2026-11-12 08:00:00', '2026-11-12 10:00:00', 'TP développement web - groupe SLAM',          'active',  '2026-11-09 17:20:00'),
  (2, 5, 2, '2026-11-12 10:00:00', '2026-11-12 12:00:00', 'TP administration Linux - groupe SISR',       'active',  '2026-11-09 17:25:00'),
  (3, 7, 3, '2026-11-13 14:00:00', '2026-11-13 16:00:00', 'Démonstration du portable aux nouveaux élèves', 'active',  '2026-11-09 18:02:00'),
  (4, 2, 2, '2026-11-03 08:00:00', '2026-11-03 10:00:00', 'Contrôle de configuration avant examen',      'active',  '2026-11-02 16:40:00'),
  (5, 3, 2, '2026-11-11 14:00:00', '2026-11-11 16:00:00', 'Séance reportée (absence)',                   'annulee', '2026-11-05 09:10:00');
