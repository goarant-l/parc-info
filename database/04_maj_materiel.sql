-- =====================================================================
--  Mise à jour du matériel de démonstration (noms, processeur, système)
--  À importer UNIQUEMENT si la base parc_info existe déjà.
--  Si vous réimportez 01_schema.sql puis 02_donnees.sql, ce fichier est inutile.
-- =====================================================================

USE parc_info;

UPDATE machines SET nom = 'inf3-01', cpu = 'Intel(R) Core(TM) 5 120U 1.40 GHz', os = 'Windows Server 2022 Standard Evaluation', os_version = '21H2' WHERE id = 1;
UPDATE machines SET nom = 'inf3-02', cpu = 'Intel(R) Core(TM) 5 120U 1.40 GHz', os = 'Windows Server 2022 Standard Evaluation', os_version = '21H2' WHERE id = 2;
UPDATE machines SET nom = 'inf3-03', cpu = 'Intel(R) Core(TM) 5 120U 1.40 GHz', os = 'Windows Server 2022 Standard Evaluation', os_version = '21H2' WHERE id = 3;
UPDATE machines SET nom = 'inf3-04', cpu = 'Intel(R) Core(TM) 5 120U 1.40 GHz', os = 'Windows Server 2022 Standard Evaluation', os_version = '21H2' WHERE id = 4;
UPDATE machines SET nom = 'inf3-05', cpu = 'Intel(R) Core(TM) 5 120U 1.40 GHz', os = 'Windows Server 2022 Standard Evaluation', os_version = '21H2' WHERE id = 5;
UPDATE machines SET nom = 'inf3-06', cpu = 'Intel(R) Core(TM) 5 120U 1.40 GHz', os = 'Windows Server 2022 Standard Evaluation', os_version = '21H2' WHERE id = 6;
UPDATE machines SET nom = 'inf3-07', cpu = 'Intel(R) Core(TM) 5 120U 1.40 GHz', os = 'Windows Server 2022 Standard Evaluation', os_version = '21H2' WHERE id = 7;
UPDATE machines SET nom = 'inf3-08', cpu = 'Intel(R) Core(TM) 5 120U 1.40 GHz', os = 'Windows Server 2022 Standard Evaluation', os_version = '21H2' WHERE id = 8;
