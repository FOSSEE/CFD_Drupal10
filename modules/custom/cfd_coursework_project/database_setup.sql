-- Run once in the same database used by Drupal.
-- If the Drupal installation uses a table prefix, add that prefix to every
-- source and destination table name below before running this script.
-- This creates independent Coursework Project tables from the existing
-- CFD Case Study structures. It does not copy user proposals or submissions.

CREATE TABLE IF NOT EXISTS coursework_project_proposal
  LIKE case_study_proposal;
CREATE TABLE IF NOT EXISTS coursework_project_qr_code
  LIKE case_study_qr_code;
CREATE TABLE IF NOT EXISTS coursework_project_simulation_type
  LIKE case_study_simulation_type;
CREATE TABLE IF NOT EXISTS coursework_project_software_version
  LIKE case_study_software_version;
CREATE TABLE IF NOT EXISTS coursework_project_solvers
  LIKE case_study_solvers;
CREATE TABLE IF NOT EXISTS coursework_project_submitted_abstracts
  LIKE case_study_submitted_abstracts;
CREATE TABLE IF NOT EXISTS coursework_project_submitted_abstracts_file
  LIKE case_study_submitted_abstracts_file;
CREATE TABLE IF NOT EXISTS coursework_project_titles
  LIKE list_of_project_titles;

-- Seed lookup tables required by the copied interface.
INSERT IGNORE INTO coursework_project_simulation_type
SELECT * FROM case_study_simulation_type;

INSERT IGNORE INTO coursework_project_software_version
SELECT * FROM case_study_software_version;

INSERT IGNORE INTO coursework_project_solvers
SELECT * FROM case_study_solvers;

-- Coursework titles intentionally start empty. Add them through the module.
-- If the CFD team explicitly wants to start with the Case Study title list,
-- copy the associated resource files and then run:
-- INSERT IGNORE INTO coursework_project_titles
-- SELECT * FROM list_of_project_titles;
