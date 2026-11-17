-- =====================================================================
--  ParcScope - V0 : jeu de données FICTIVES pour tester
--  (une salle, 8 machines, 3 comptes de démonstration)
-- =====================================================================

USE parc_info;

INSERT INTO salles (id, nom, description) VALUES
  (1, 'Salle B204', 'Salle informatique BTS SIO - 1er étage');

-- Comptes de démonstration (mots de passe : eleve123 / prof1234 / admin123)
INSERT INTO utilisateurs (id, nom, prenom, email, mot_de_passe, niveau, salle_id, actif) VALUES
  (1, 'Le Gall', 'Camille', 'eleve@parc.local', '$2y$10$CMURIz8bXw0vownDtB1NLeDGf5AkLMCfe056Di.Kas/G8YHy/YcIS', 1, NULL, 1),
  (2, 'Tanguy',  'Pierre',  'prof@parc.local',  '$2y$10$3O9EHgXlSIBsfse.4BwOKONE9/QTJlrbkYlxgnkUeRcpcim0uAnXG', 2, 1,    1),
  (3, 'Admin',   'Parc',    'admin@parc.local', '$2y$10$fdsuvmrQ/5Zyav0Rx0xfzOkj.5NdkltcEMpRvwLHbC0FhiRV6z5n6', 3, NULL, 1);

INSERT INTO machines
  (id, salle_id, nom, type, marque, modele, numero_serie, cpu, ram_go, disque_go, disque_type, os, os_version, ip, mac, statut, date_achat)
VALUES
  (1, 1, 'inf3-01', 'Poste fixe', 'Dell',   'OptiPlex 3000',     'DL3000-0A1F', 'Intel(R) Core(TM) 5 120U 1.40 GHz',  16,  256, 'SSD',  'Windows Server 2022 Standard Evaluation', '21H2',       '192.168.20.11', '00:1A:2B:3C:4D:01', 'actif',       '2023-09-04'),
  (2, 1, 'inf3-02', 'Poste fixe', 'Dell',   'OptiPlex 3000',     'DL3000-0A20', 'Intel(R) Core(TM) 5 120U 1.40 GHz',  16,  256, 'SSD',  'Windows Server 2022 Standard Evaluation', '21H2',       '192.168.20.12', '00:1A:2B:3C:4D:02', 'actif',       '2023-09-04'),
  (3, 1, 'inf3-03', 'Poste fixe', 'HP',     'ProDesk 400 G7',    'HP400-77B3',  'Intel(R) Core(TM) 5 120U 1.40 GHz',   8,  256, 'SSD',  'Windows Server 2022 Standard Evaluation', '21H2',       '192.168.20.13', '00:1A:2B:3C:4D:03', 'actif',       '2021-01-18'),
  (4, 1, 'inf3-04', 'Poste fixe', 'HP',     'ProDesk 400 G7',    'HP400-77B4',  'Intel(R) Core(TM) 5 120U 1.40 GHz',   8,  256, 'SSD',  'Windows Server 2022 Standard Evaluation', '21H2',       '192.168.20.14', '00:1A:2B:3C:4D:04', 'panne',       '2021-01-18'),
  (5, 1, 'inf3-05', 'Poste fixe', 'Lenovo', 'ThinkCentre M70s',  'LN70S-5521',  'Intel(R) Core(TM) 5 120U 1.40 GHz',  16,  512, 'NVMe', 'Windows Server 2022 Standard Evaluation',         '21H2',  '192.168.20.15', '00:1A:2B:3C:4D:05', 'actif',       '2022-03-21'),
  (6, 1, 'inf3-06', 'Poste fixe', 'Lenovo', 'ThinkCentre M70s',  'LN70S-5522',  'Intel(R) Core(TM) 5 120U 1.40 GHz',  16,  512, 'NVMe', 'Windows Server 2022 Standard Evaluation',         '21H2',  '192.168.20.16', '00:1A:2B:3C:4D:06', 'maintenance', '2022-03-21'),
  (7, 1, 'inf3-07', 'Portable',   'Dell',   'Latitude 5430',     'DL5430-9C02', 'Intel(R) Core(TM) 5 120U 1.40 GHz',  16,  512, 'NVMe', 'Windows Server 2022 Standard Evaluation', '21H2',       '192.168.20.17', '00:1A:2B:3C:4D:07', 'actif',       '2024-01-15'),
  (8, 1, 'inf3-08',   'Serveur',    'Dell',   'PowerEdge T150',    'DLT150-31AA', 'Intel(R) Core(TM) 5 120U 1.40 GHz',    32, 2000, 'HDD',  'Windows Server 2022 Standard Evaluation',         '21H2', '192.168.20.2',  '00:1A:2B:3C:4D:AA', 'actif',       '2023-06-12');

INSERT INTO logiciels (id, nom, editeur) VALUES
  (1,  'Microsoft Office',  'Microsoft'),
  (2,  'Visual Studio Code','Microsoft'),
  (3,  'Google Chrome',     'Google'),
  (4,  'Mozilla Firefox',   'Mozilla'),
  (5,  'Git',               'Software Freedom Conservancy'),
  (6,  'XAMPP',             'Apache Friends'),
  (7,  'MySQL Workbench',   'Oracle'),
  (8,  'VirtualBox',        'Oracle'),
  (9,  'Wireshark',         'Wireshark Foundation'),
  (10, 'Cisco Packet Tracer','Cisco'),
  (11, 'LibreOffice',       'The Document Foundation'),
  (12, 'Apache HTTP Server','Apache Software Foundation'),
  (13, 'MariaDB Server',    'MariaDB Foundation'),
  (14, 'OpenSSH Server',    'OpenBSD Project');

INSERT INTO machine_logiciel (machine_id, logiciel_id, version) VALUES
  -- inf3-01
  (1, 1, '2021'), (1, 2, '1.95'), (1, 3, '131.0'), (1, 5, '2.47'), (1, 6, '8.2.12'), (1, 7, '8.0.40'),
  -- inf3-02
  (2, 1, '2021'), (2, 2, '1.95'), (2, 3, '131.0'), (2, 5, '2.47'), (2, 6, '8.2.12'), (2, 7, '8.0.40'),
  -- inf3-03
  (3, 1, '2021'), (3, 2, '1.94'), (3, 4, '133.0'), (3, 10, '8.2.2'),
  -- inf3-04
  (4, 1, '2021'), (4, 2, '1.94'), (4, 4, '133.0'), (4, 10, '8.2.2'),
  -- inf3-05
  (5, 2, '1.95'), (5, 4, '133.0'), (5, 5, '2.34'), (5, 8, '7.0.22'), (5, 9, '4.2.2'), (5, 11, '7.3.7'),
  -- inf3-06
  (6, 2, '1.95'), (6, 4, '133.0'), (6, 5, '2.34'), (6, 8, '7.0.22'), (6, 9, '4.2.2'), (6, 11, '7.3.7'),
  -- inf3-07
  (7, 1, '2021'), (7, 2, '1.95'), (7, 3, '131.0'), (7, 5, '2.47'), (7, 10, '8.2.2'),
  -- inf3-08
  (8, 5, '2.39'), (8, 12, '2.4.62'), (8, 13, '10.11.6'), (8, 14, '9.2p1');

INSERT INTO signalements (id, machine_id, utilisateur_id, description, statut, date_creation, date_traitement) VALUES
  (1, 4, 1, 'L''écran reste noir au démarrage, le voyant de l''unité centrale clignote.', 'valide', '2026-11-04 10:12:00', '2026-11-04 14:30:00'),
  (2, 2, 1, 'Le clavier ne répond plus par moments, il faut le débrancher et le rebrancher.', 'ouvert', '2026-11-09 08:47:00', NULL);

-- Réservations de démonstration (professeur = utilisateur 2, admin = utilisateur 3)
INSERT INTO reservations (id, machine_id, utilisateur_id, debut, fin, motif, statut, date_creation) VALUES
  (1, 1, 2, '2026-11-12 08:00:00', '2026-11-12 10:00:00', 'TP développement web - groupe SLAM',          'active',  '2026-11-09 17:20:00'),
  (2, 5, 2, '2026-11-12 10:00:00', '2026-11-12 12:00:00', 'TP administration Linux - groupe SISR',       'active',  '2026-11-09 17:25:00'),
  (3, 7, 3, '2026-11-13 14:00:00', '2026-11-13 16:00:00', 'Démonstration du portable aux nouveaux élèves', 'active',  '2026-11-09 18:02:00'),
  (4, 2, 2, '2026-11-03 08:00:00', '2026-11-03 10:00:00', 'Contrôle de configuration avant examen',      'active',  '2026-11-02 16:40:00'),
  (5, 3, 2, '2026-11-11 14:00:00', '2026-11-11 16:00:00', 'Séance reportée (absence)',                   'annulee', '2026-11-05 09:10:00');
