-- =====================================================================
-- LMLinga Barangay Health Information System
-- Consolidated MySQL Schema (3NF)
-- =====================================================================
-- NOTES:
-- 1. Engine is InnoDB throughout (required for FOREIGN KEY enforcement).
-- 2. resident_id / unregistered_*_id columns are nullable FKs used as an
--    "exclusive-arc" pattern: exactly one of the pair should be filled
--    per row. MySQL allows NULL FK values to pass the FK check trivially,
--    so these are safe to declare as real FOREIGN KEYs even though only
--    one will be populated per row.
-- 3. CHECK constraints enforcing that exclusivity (exactly one of
--    resident_id / unregistered_*_id must be non-null), along with
--    business-rule UNIQUE constraints (e.g. one dose per round/year),
--    are intentionally deferred per project decision and are NOT
--    included in this script yet. See the TODO block at the bottom.
-- 4. Import via: MySQL Workbench -> File -> Import -> Reverse Engineer
--    MySQL Create Script -> select this file.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS lmlinga_health_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lmlinga_health_system;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- USER MANAGEMENT
-- =====================================================================

CREATE TABLE user_management (
  user_id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  last_name       VARCHAR(100) NOT NULL,
  first_name      VARCHAR(100) NOT NULL,
  middle_name     VARCHAR(100) NULL,
  suffix          VARCHAR(20) NULL,
  position        ENUM('BHW','BNS','BSPO','Admin') NOT NULL,
  email_address   VARCHAR(150) NOT NULL UNIQUE,
  password        VARCHAR(255) NOT NULL,
  birth_date      DATE NULL,
  sex             ENUM('Male','Female') NULL,
  civil_status    VARCHAR(50) NULL,
  mobile_number   VARCHAR(20) NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- LOOKUP TABLES (no dependencies)
-- =====================================================================

CREATE TABLE occupations (
  occupation_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  occupation_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE religions (
  religion_id     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  religion_name   VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE education_levels (
  education_level_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  education_name      VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE disability_types (
  disability_type_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  disability_name     VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE monthly_income_ranges (
  monthly_income_range_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  income_range             VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE medical_conditions (
  condition_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condition_name  VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE water_sources (
  water_source_id   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  water_source_name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- HOUSE-TO-HOUSE PROFILING
-- =====================================================================

CREATE TABLE households (
  household_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  household_no    VARCHAR(50) NOT NULL UNIQUE,
  address         VARCHAR(255) NOT NULL,
  purok           VARCHAR(20) NOT NULL,
  latitude        DECIMAL(10,8) NOT NULL,
  longitude       DECIMAL(11,8) NOT NULL,
  date_registered DATE NOT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE residents (
  resident_id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  household_id            BIGINT UNSIGNED NOT NULL,
  first_name              VARCHAR(100) NOT NULL,
  middle_name             VARCHAR(100) NULL,
  last_name               VARCHAR(100) NOT NULL,
  suffix                  VARCHAR(20) NULL,
  birthdate               DATE NOT NULL,
  sex                     ENUM('Male','Female') NOT NULL,
  civil_status            VARCHAR(50) NOT NULL,
  relationship_to_head    VARCHAR(50) NOT NULL,
  is_household_head       BOOLEAN NOT NULL DEFAULT FALSE,
  occupation_id           BIGINT UNSIGNED NULL,
  occupation_other        VARCHAR(255) NULL,
  monthly_income_range_id BIGINT UNSIGNED NULL,
  religion_id             BIGINT UNSIGNED NULL,
  religion_other          VARCHAR(255) NULL,
  education_level_id      BIGINT UNSIGNED NULL,
  philhealth_no           VARCHAR(12) NULL,
  disability_type_id      BIGINT UNSIGNED NULL,
  disability_other        VARCHAR(255) NULL,
  created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_residents_household FOREIGN KEY (household_id) REFERENCES households(household_id),
  CONSTRAINT fk_residents_occupation FOREIGN KEY (occupation_id) REFERENCES occupations(occupation_id),
  CONSTRAINT fk_residents_income_range FOREIGN KEY (monthly_income_range_id) REFERENCES monthly_income_ranges(monthly_income_range_id),
  CONSTRAINT fk_residents_religion FOREIGN KEY (religion_id) REFERENCES religions(religion_id),
  CONSTRAINT fk_residents_education FOREIGN KEY (education_level_id) REFERENCES education_levels(education_level_id),
  CONSTRAINT fk_residents_disability FOREIGN KEY (disability_type_id) REFERENCES disability_types(disability_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE resident_medical_conditions (
  resident_id     BIGINT UNSIGNED NOT NULL,
  condition_id    BIGINT UNSIGNED NOT NULL,
  condition_other VARCHAR(255) NULL,
  remarks         TEXT NULL,
  PRIMARY KEY (resident_id, condition_id),
  CONSTRAINT fk_rmc_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_rmc_condition FOREIGN KEY (condition_id) REFERENCES medical_conditions(condition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE water_supply_assessments (
  assessment_id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  household_id              BIGINT UNSIGNED NOT NULL,
  assessment_date           DATE NOT NULL,
  se_status                 ENUM('NHTS','Non-NHTS') NOT NULL,
  water_level               ENUM('I','II','III') NOT NULL,
  water_source_id           BIGINT UNSIGNED NULL,
  water_source_other        VARCHAR(50) NULL,
  basic_safe_water          BOOLEAN NOT NULL,
  inside_premises           BOOLEAN NOT NULL,
  available_24_hours        BOOLEAN NOT NULL,
  micro_validation_date     DATE NULL,
  micro_validation_result   ENUM('Passed','Failed') NULL,
  physico_validation_date   DATE NULL,
  physico_validation_result ENUM('Passed','Failed') NULL,
  safe_drinking_water       BOOLEAN NOT NULL,
  remarks                   TEXT NULL,
  created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wsa_household FOREIGN KEY (household_id) REFERENCES households(household_id),
  CONSTRAINT fk_wsa_water_source FOREIGN KEY (water_source_id) REFERENCES water_sources(water_source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Risk Assessment sub-module
-- ---------------------------------------------------------------------

CREATE TABLE risk_assessments (
  risk_assessment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id         BIGINT UNSIGNED NOT NULL,
  assessment_date      DATE NOT NULL,
  if_ip                BOOLEAN DEFAULT FALSE,
  severe_injuries      BOOLEAN DEFAULT FALSE,
  remarks              TEXT NULL,
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ra_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE red_flags_assessment (
  red_flag_id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  risk_assessment_id    BIGINT UNSIGNED NOT NULL UNIQUE,
  chest_pain            BOOLEAN DEFAULT FALSE,
  diff_breathing        BOOLEAN DEFAULT FALSE,
  loss_consciousness    BOOLEAN DEFAULT FALSE,
  slurred_speech        BOOLEAN DEFAULT FALSE,
  facial_assym          BOOLEAN DEFAULT FALSE,
  weakness_body         BOOLEAN DEFAULT FALSE,
  disorientation        BOOLEAN DEFAULT FALSE,
  chest_retract         BOOLEAN DEFAULT FALSE,
  seizure               BOOLEAN DEFAULT FALSE,
  self_harm             BOOLEAN DEFAULT FALSE,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rfa_assessment FOREIGN KEY (risk_assessment_id) REFERENCES risk_assessments(risk_assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE past_medical_history (
  past_med_id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  risk_assessment_id     BIGINT UNSIGNED NOT NULL UNIQUE,
  hypertension           BOOLEAN DEFAULT FALSE,
  heart_disease          BOOLEAN DEFAULT FALSE,
  diabetes               BOOLEAN DEFAULT FALSE,
  cancer                 BOOLEAN DEFAULT FALSE,
  copd                   BOOLEAN DEFAULT FALSE,
  asthma                 BOOLEAN DEFAULT FALSE,
  allergies              BOOLEAN DEFAULT FALSE,
  mental_disorders       BOOLEAN DEFAULT FALSE,
  vision_problems        BOOLEAN DEFAULT FALSE,
  surgical_history       BOOLEAN DEFAULT FALSE,
  thyroid_disorders      BOOLEAN DEFAULT FALSE,
  kidney_disorders       BOOLEAN DEFAULT FALSE,
  is_agitated            BOOLEAN DEFAULT FALSE,
  eye_inquiry            BOOLEAN DEFAULT FALSE,
  created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pmh_assessment FOREIGN KEY (risk_assessment_id) REFERENCES risk_assessments(risk_assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE family_history (
  fam_history_id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  risk_assessment_id     BIGINT UNSIGNED NOT NULL UNIQUE,
  hypertension           BOOLEAN DEFAULT FALSE,
  stroke                 BOOLEAN DEFAULT FALSE,
  heart_disease          BOOLEAN DEFAULT FALSE,
  diabetes_mellitus      BOOLEAN DEFAULT FALSE,
  asthma                 BOOLEAN DEFAULT FALSE,
  cancer                 BOOLEAN DEFAULT FALSE,
  kidney_disease         BOOLEAN DEFAULT FALSE,
  first_degree_cardio    BOOLEAN DEFAULT FALSE,
  tb                     BOOLEAN DEFAULT FALSE,
  mental_problem         BOOLEAN DEFAULT FALSE,
  copd                   BOOLEAN DEFAULT FALSE,
  created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_fh_assessment FOREIGN KEY (risk_assessment_id) REFERENCES risk_assessments(risk_assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ncd_risk_factors (
  ncd_id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  risk_assessment_id     BIGINT UNSIGNED NOT NULL UNIQUE,
  tabaco_use             ENUM('Q1','Q2','Q3','Q4') NULL,
  alcohol_intake_q1      BOOLEAN DEFAULT FALSE,
  alcohol_intake_q2      BOOLEAN DEFAULT FALSE,
  physical_activity      BOOLEAN DEFAULT FALSE,
  nutri_diet_assessment  BOOLEAN DEFAULT FALSE,
  weight_kg              DECIMAL(5,2) NULL,
  height_cm              DECIMAL(5,2) NULL,
  waist_circum_cm        DECIMAL(5,2) NULL,
  bp_systolic            SMALLINT UNSIGNED NULL,
  bp_diastolic           SMALLINT UNSIGNED NULL,
  created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ncd_assessment FOREIGN KEY (risk_assessment_id) REFERENCES risk_assessments(risk_assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE glycemic_screening (
  glycemic_screening_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  risk_assessment_id      BIGINT UNSIGNED NOT NULL,
  test_type                ENUM('FBS','RBS') NOT NULL,
  result_mg_dl             DECIMAL(6,2) NULL,
  test_date_taken          DATE NULL,
  has_polyphagia           BOOLEAN DEFAULT FALSE,
  has_polydipsia           BOOLEAN DEFAULT FALSE,
  has_polyuria             BOOLEAN DEFAULT FALSE,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_glyc_assessment FOREIGN KEY (risk_assessment_id) REFERENCES risk_assessments(risk_assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lipid_profile (
  lipid_profile_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  risk_assessment_id      BIGINT UNSIGNED NOT NULL,
  total_cholesterol        DECIMAL(6,2) NULL,
  hdl                      DECIMAL(6,2) NULL,
  ldl                      DECIMAL(6,2) NULL,
  triglycerides            DECIMAL(6,2) NULL,
  vldl_calculated          DECIMAL(6,2) GENERATED ALWAYS AS (triglycerides / 5) STORED,
  test_date_taken          DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lipid_assessment FOREIGN KEY (risk_assessment_id) REFERENCES risk_assessments(risk_assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE urinalysis_screening (
  urinalysis_id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  risk_assessment_id      BIGINT UNSIGNED NOT NULL,
  protein_result           VARCHAR(50) NULL,
  protein_date_taken       DATE NULL,
  ketone_result            VARCHAR(50) NULL,
  ketone_date_taken        DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_urine_assessment FOREIGN KEY (risk_assessment_id) REFERENCES risk_assessments(risk_assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE respiratory_screening (
  respiratory_screening_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  risk_assessment_id         BIGINT UNSIGNED NOT NULL,
  has_breathlessness          BOOLEAN DEFAULT FALSE,
  has_chronic_cough           BOOLEAN DEFAULT FALSE,
  has_sputum_production       BOOLEAN DEFAULT FALSE,
  has_chest_tightness         BOOLEAN DEFAULT FALSE,
  has_wheezing                BOOLEAN DEFAULT FALSE,
  assessment_date             DATE NULL,
  created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_resp_assessment FOREIGN KEY (risk_assessment_id) REFERENCES risk_assessments(risk_assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE risk_management (
  risk_management_id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  risk_assessment_id             BIGINT UNSIGNED NOT NULL UNIQUE,
  lifestyle_modification         BOOLEAN DEFAULT FALSE,
  medications_anti_hypertensive  BOOLEAN DEFAULT FALSE,
  medications_insulin            BOOLEAN DEFAULT FALSE,
  date_follow_up                 DATE NULL,
  created_at                     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rm_assessment FOREIGN KEY (risk_assessment_id) REFERENCES risk_assessments(risk_assessment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- FAMILY PLANNING
-- =====================================================================

CREATE TABLE unregistered_clients (
  unregistered_client_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name               VARCHAR(100) NOT NULL,
  middle_name              VARCHAR(100) NULL,
  last_name                VARCHAR(100) NOT NULL,
  birthdate                DATE NULL,
  sex                      ENUM('Male','Female') NOT NULL,
  civil_status             VARCHAR(50) NULL,
  address                  VARCHAR(255) NOT NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE family_planning (
  family_planning_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id               BIGINT UNSIGNED NULL,
  unregistered_client_id    BIGINT UNSIGNED NULL,
  visit_date                DATE NOT NULL,
  remarks                   TEXT NULL,
  created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_fp_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_fp_unregistered_client FOREIGN KEY (unregistered_client_id) REFERENCES unregistered_clients(unregistered_client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fp_commodities_given (
  fp_commodity_id     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  family_planning_id   BIGINT UNSIGNED NOT NULL,
  commodity_name        VARCHAR(100) NOT NULL,
  quantity              INT UNSIGNED NOT NULL,
  date_given            DATE NOT NULL,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_fpcg_family_planning FOREIGN KEY (family_planning_id) REFERENCES family_planning(family_planning_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- CHILD CARE (shared unregistered_children table used across modules)
-- =====================================================================

CREATE TABLE unregistered_children (
  unregistered_child_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name              VARCHAR(100) NOT NULL,
  middle_name             VARCHAR(100) NULL,
  last_name               VARCHAR(100) NOT NULL,
  birthdate               DATE NOT NULL,
  sex                     ENUM('Male','Female') NOT NULL,
  address                 VARCHAR(255) NOT NULL,
  parent_guardian_name    VARCHAR(150) NULL,
  school_name             VARCHAR(150) NULL,
  grade_level             VARCHAR(50) NULL,
  created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE timbang_records (
  timbang_id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id             BIGINT UNSIGNED NULL,
  unregistered_child_id   BIGINT UNSIGNED NULL,
  measurement_date        DATE NOT NULL,
  weight_kg                DECIMAL(5,2) NULL,
  height_cm                DECIMAL(5,2) NULL,
  muac_cm                  DECIMAL(4,1) NULL,
  weight_for_age           ENUM('Severely Underweight','Underweight','Normal') NULL,
  height_for_age           ENUM('Severely Stunted','Stunted','Normal') NULL,
  weight_for_height        VARCHAR(50) NULL,
  remarks                  TEXT NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_timbang_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_timbang_unregistered_child FOREIGN KEY (unregistered_child_id) REFERENCES unregistered_children(unregistered_child_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- DEWORMING
-- =====================================================================

CREATE TABLE deworming_records (
  deworming_id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id             BIGINT UNSIGNED NULL,
  unregistered_child_id   BIGINT UNSIGNED NULL,
  year                     YEAR NOT NULL,
  deworming_round          TINYINT UNSIGNED NOT NULL,
  se_status                ENUM('NHTS','Non-NHTS') NOT NULL,
  date_given               DATE NOT NULL,
  received                 BOOLEAN NOT NULL,
  adr                      BOOLEAN NOT NULL,
  adr_remarks              TEXT NULL,
  remarks                  TEXT NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_deworm_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_deworm_unregistered_child FOREIGN KEY (unregistered_child_id) REFERENCES unregistered_children(unregistered_child_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- VITAMIN A
-- =====================================================================

CREATE TABLE vit_a_supplementation (
  vit_a_id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id             BIGINT UNSIGNED NULL,
  unregistered_child_id   BIGINT UNSIGNED NULL,
  year                     YEAR NOT NULL,
  round                    TINYINT UNSIGNED NOT NULL,
  dosage_iu                ENUM('100,000 IU','200,000 IU') NOT NULL,
  date_given               DATE NOT NULL,
  received                 BOOLEAN NOT NULL,
  adr                      BOOLEAN NOT NULL DEFAULT FALSE,
  adr_remarks              TEXT NULL,
  remarks                  TEXT NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vita_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_vita_unregistered_child FOREIGN KEY (unregistered_child_id) REFERENCES unregistered_children(unregistered_child_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- CHILD CARE — PART 1: CHILD IMMUNIZATION
-- =====================================================================

CREATE TABLE child_immunization (
  child_immunization_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id             BIGINT UNSIGNED NULL,
  unregistered_child_id   BIGINT UNSIGNED NULL,
  family_serial_no         VARCHAR(50) NULL,
  date_of_registration     DATE NULL,
  mother_name              VARCHAR(150) NULL,
  cpab_tt_2doses           BOOLEAN DEFAULT FALSE,
  cpab_tt3_to_tt5          BOOLEAN DEFAULT FALSE,
  remarks                  TEXT NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ci_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_ci_unregistered_child FOREIGN KEY (unregistered_child_id) REFERENCES unregistered_children(unregistered_child_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE immunization_doses (
  dose_id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  child_immunization_id   BIGINT UNSIGNED NOT NULL,
  vaccine_type             ENUM('BCG','Hepa B','DPT-HiB-HepB','OPV','IPV','PCV','MMR') NOT NULL,
  dose_number              ENUM('1st Dose','2nd Dose','3rd Dose') NULL,
  date_given               DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_id_child_immunization FOREIGN KEY (child_immunization_id) REFERENCES child_immunization(child_immunization_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fic_cic_status (
  fic_cic_id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  child_immunization_id   BIGINT UNSIGNED NOT NULL UNIQUE,
  fic_completed            BOOLEAN DEFAULT FALSE,
  fic_date                 DATE NULL,
  cic_completed            BOOLEAN DEFAULT FALSE,
  cic_date                 DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_fcs_child_immunization FOREIGN KEY (child_immunization_id) REFERENCES child_immunization(child_immunization_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- CHILD CARE — PART 2: SCHOOL-BASED IMMUNIZATION
-- =====================================================================

CREATE TABLE school_immunization (
  school_immunization_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id               BIGINT UNSIGNED NULL,
  unregistered_child_id     BIGINT UNSIGNED NULL,
  family_serial_no           VARCHAR(50) NULL,
  date_of_registration       DATE NULL,
  grade_level                ENUM('Grade 1','Grade 7') NULL,
  td_vaccine_date             DATE NULL,
  mr_vaccine_date             DATE NULL,
  hpv_1st_dose_date           DATE NULL,
  hpv_2nd_dose_date           DATE NULL,
  hpv_completed               BOOLEAN NULL,
  hpv_completed_date          DATE NULL,
  remarks                     TEXT NULL,
  created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_si_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_si_unregistered_child FOREIGN KEY (unregistered_child_id) REFERENCES unregistered_children(unregistered_child_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- CHILD CARE — PART 3: CHILD NUTRITION
-- =====================================================================

CREATE TABLE child_nutrition (
  child_nutrition_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id             BIGINT UNSIGNED NULL,
  unregistered_child_id   BIGINT UNSIGNED NULL,
  family_serial_no         VARCHAR(50) NULL,
  date_of_registration     DATE NULL,
  mother_name              VARCHAR(150) NULL,
  length_at_birth_cm       DECIMAL(5,2) NULL,
  weight_at_birth_kg       DECIMAL(5,2) NULL,
  birth_weight_status      ENUM('Low','Normal','Unknown') NULL,
  breastfeeding_initiated_date DATE NULL,
  remarks                  TEXT NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cn_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_cn_unregistered_child FOREIGN KEY (unregistered_child_id) REFERENCES unregistered_children(unregistered_child_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE iron_supplementation (
  iron_supp_id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  child_nutrition_id                     BIGINT UNSIGNED NOT NULL,
  month_number                           ENUM('1','2','3') NULL,
  date_given                             DATE NULL,
  is_iron_supplementation_complete       BOOLEAN DEFAULT FALSE,
  iron_supplementation_date_completed    DATE NULL,
  created_at                             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_iron_child_nutrition FOREIGN KEY (child_nutrition_id) REFERENCES child_nutrition(child_nutrition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nutrition_supplementation (
  supplementation_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  child_nutrition_id      BIGINT UNSIGNED NOT NULL,
  supplement_type          ENUM('Vitamin A','MNP','LNS-SQ') NOT NULL,
  age_group                ENUM('6-11 months','12-23 months','12-59 months') NULL,
  dose_number              TINYINT UNSIGNED NULL,
  date_given               DATE NULL,
  completed                BOOLEAN DEFAULT FALSE,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ns_child_nutrition FOREIGN KEY (child_nutrition_id) REFERENCES child_nutrition(child_nutrition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE malnutrition_management (
  malnutrition_mgmt_id  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  child_nutrition_id      BIGINT UNSIGNED NOT NULL,
  malnutrition_type        ENUM('MAM','SAM') NULL,
  identified               BOOLEAN DEFAULT FALSE,
  enrolled_program         BOOLEAN DEFAULT FALSE,
  cured                    BOOLEAN DEFAULT FALSE,
  non_cured                BOOLEAN DEFAULT FALSE,
  defaulted                BOOLEAN DEFAULT FALSE,
  died                     BOOLEAN DEFAULT FALSE,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mm_child_nutrition FOREIGN KEY (child_nutrition_id) REFERENCES child_nutrition(child_nutrition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- CHILD CARE — PART 4: MANAGEMENT OF SICK INFANTS
-- =====================================================================

CREATE TABLE sick_infant_management (
  sick_infant_id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id             BIGINT UNSIGNED NULL,
  unregistered_child_id   BIGINT UNSIGNED NULL,
  family_serial_no         VARCHAR(50) NULL,
  date_of_registration     DATE NULL,
  mother_name              VARCHAR(150) NULL,
  high_risk_vit_a_date     DATE NULL,
  high_risk_vit_a_dosage   ENUM('100,000 IU','200,000 IU') NULL,
  diagnosis_findings       ENUM('Measles','Persistent Diarrhea') NULL,
  diarrhea_treatment_date  DATE NULL,
  diarrhea_treatment_type  ENUM('ORS only','ORS and Zinc') NULL,
  pneumonia_treatment_date DATE NULL,
  pneumonia_treatment_type ENUM('Amoxicillin drops','Amoxicillin suspension') NULL,
  remarks                  TEXT NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_sim_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_sim_unregistered_child FOREIGN KEY (unregistered_child_id) REFERENCES unregistered_children(unregistered_child_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- MATERNAL CARE AND SERVICES
-- =====================================================================

CREATE TABLE maternal_records (
  maternal_record_id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id                     BIGINT UNSIGNED NULL,
  unregistered_client_id           BIGINT UNSIGNED NULL,
  family_serial_no                 VARCHAR(50) NULL,
  date_of_registration             DATE NULL,
  age_group                        ENUM('A','B','C') NULL,
  lmp_date                         DATE NULL,
  gravida                          TINYINT UNSIGNED NULL,
  para                             TINYINT UNSIGNED NULL,
  edd                              DATE NULL,
  completed_8anc                   BOOLEAN NULL,
  bmi_1st_trimester                DECIMAL(4,1) NULL,
  bmi_classification               ENUM('Low','Normal','High') NULL,
  trans_status                     ENUM('A','B') NULL,
  fim_status                       BOOLEAN NULL,
  dewormed_during_pregnancy        BOOLEAN NULL,
  dewormed_date                    DATE NULL,
  completed_mm_supplementation     BOOLEAN NULL,
  completed_mm_date                DATE NULL,
  completed_cc_supplementation     BOOLEAN NULL,
  completed_cc_date                DATE NULL,
  completed_ifa_supplementation    BOOLEAN NULL,
  completed_ifa_date               DATE NULL,
  remarks                          TEXT NULL,
  created_at                       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mr_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_mr_unregistered_client FOREIGN KEY (unregistered_client_id) REFERENCES unregistered_clients(unregistered_client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE anc_visits (
  anc_visit_id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maternal_record_id      BIGINT UNSIGNED NOT NULL,
  visit_number             TINYINT UNSIGNED NOT NULL,
  trimester                ENUM('1st','2nd','3rd') NULL,
  visit_date               DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_anc_maternal_record FOREIGN KEY (maternal_record_id) REFERENCES maternal_records(maternal_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE td_immunization (
  td_immunization_id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maternal_record_id      BIGINT UNSIGNED NOT NULL,
  dose_number              TINYINT UNSIGNED NOT NULL,
  date_given               DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tdi_maternal_record FOREIGN KEY (maternal_record_id) REFERENCES maternal_records(maternal_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ifa_supplementation (
  ifa_supp_id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maternal_record_id      BIGINT UNSIGNED NOT NULL,
  visit_number             TINYINT UNSIGNED NOT NULL,
  tablets_given            INT UNSIGNED NULL,
  date_given               DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ifa_maternal_record FOREIGN KEY (maternal_record_id) REFERENCES maternal_records(maternal_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mms_supplementation (
  mms_supp_id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maternal_record_id      BIGINT UNSIGNED NOT NULL,
  visit_number             TINYINT UNSIGNED NOT NULL,
  tablets_given            INT UNSIGNED NULL,
  date_given               DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mms_maternal_record FOREIGN KEY (maternal_record_id) REFERENCES maternal_records(maternal_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cc_supplementation (
  cc_supp_id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maternal_record_id      BIGINT UNSIGNED NOT NULL,
  visit_number             TINYINT UNSIGNED NOT NULL,
  tablets_given            INT UNSIGNED NULL,
  date_given               DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cc_maternal_record FOREIGN KEY (maternal_record_id) REFERENCES maternal_records(maternal_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lab_screenings (
  lab_screening_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maternal_record_id      BIGINT UNSIGNED NOT NULL,
  screening_type           ENUM('Hepatitis B','CBC/Hgb and Hct Count','Gestational Diabetes Mellitus') NOT NULL,
  date_screened            DATE NULL,
  result                   VARCHAR(50) NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ls_maternal_record FOREIGN KEY (maternal_record_id) REFERENCES maternal_records(maternal_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE delivery_outcomes (
  delivery_outcome_id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  maternal_record_id                BIGINT UNSIGNED NOT NULL UNIQUE,
  date_terminated                    DATE NULL,
  outcome                            ENUM('Full Term','Pre-term','Fetal Death','Abortion/Miscarriage') NULL,
  delivery_type                      ENUM('Cesarean Section','Vaginal Delivery','Combined Vaginal-Cesarean') NULL,
  birth_weight_grams                 DECIMAL(6,2) NULL,
  birth_weight_status                ENUM('Normal','Low','Unknown') NULL,
  facility_type                      ENUM('Public','Private') NULL,
  health_facility_name               VARCHAR(150) NULL,
  bemonc_cemonc_capable               BOOLEAN NULL,
  non_health_facility_type            ENUM('Home','Others') NULL,
  non_health_facility_other           VARCHAR(150) NULL,
  birth_attendant                     ENUM('Doctor','Nurse','Midwife','Others') NULL,
  date_of_delivery                    DATE NULL,
  time_of_delivery                    TIME NULL,
  completed_4pnc                      BOOLEAN NULL,
  completed_ifa_postpartum            BOOLEAN NULL,
  completed_ifa_postpartum_date       DATE NULL,
  completed_vit_a_postpartum          BOOLEAN NULL,
  completed_vit_a_postpartum_date     DATE NULL,
  remarks                             TEXT NULL,
  created_at                          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_do_maternal_record FOREIGN KEY (maternal_record_id) REFERENCES maternal_records(maternal_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE postnatal_care_visits (
  pnc_visit_id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  delivery_outcome_id      BIGINT UNSIGNED NOT NULL,
  contact_number           TINYINT UNSIGNED NOT NULL,
  visit_date               DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pcv_delivery_outcome FOREIGN KEY (delivery_outcome_id) REFERENCES delivery_outcomes(delivery_outcome_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE postpartum_ifa_supplementation (
  postpartum_ifa_id     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  delivery_outcome_id      BIGINT UNSIGNED NOT NULL,
  visit_number             TINYINT UNSIGNED NOT NULL,
  tablets_given            INT UNSIGNED NULL,
  date_given               DATE NULL,
  created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pis_delivery_outcome FOREIGN KEY (delivery_outcome_id) REFERENCES delivery_outcomes(delivery_outcome_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- RECORD REQUEST MODULE
-- Allows a logged-in resident to request a copy of their household's
-- house-to-house profiling record, with identity verification via
-- SMS (IPROG SMS API) or Email (verification link, self-managed).
-- =====================================================================

CREATE TABLE resident_accounts (
  account_id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id     BIGINT UNSIGNED NOT NULL UNIQUE,
  password        VARCHAR(255) NOT NULL,
  mobile_number   VARCHAR(20) NOT NULL,
  email_address   VARCHAR(150) NULL,
  last_login_at   TIMESTAMP NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_resacct_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE record_requests (
  request_id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resident_id                 BIGINT UNSIGNED NOT NULL,
  relationship_submitted       VARCHAR(50) NOT NULL,
  mobile_number_submitted      VARCHAR(20) NOT NULL,
  email_submitted              VARCHAR(150) NULL,
  verification_status          ENUM('Pending','Verified','Failed') NOT NULL DEFAULT 'Pending',
  identity_verified            BOOLEAN NOT NULL DEFAULT FALSE,
  status                       ENUM('Pending','Approved','Released','Denied') NOT NULL DEFAULT 'Pending',
  processed_by                 BIGINT UNSIGNED NULL,
  date_released                DATE NULL,
  remarks                      TEXT NULL,
  created_at                   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_rr_resident FOREIGN KEY (resident_id) REFERENCES residents(resident_id),
  CONSTRAINT fk_rr_processed_by FOREIGN KEY (processed_by) REFERENCES user_management(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE record_request_verifications (
  verification_id       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id              BIGINT UNSIGNED NOT NULL,
  delivery_channel         ENUM('SMS','Email') NOT NULL,
  recipient                VARCHAR(150) NOT NULL,
  otp_code                 VARCHAR(6) NULL,
  verification_token       VARCHAR(255) NULL,
  generated_at             TIMESTAMP NOT NULL,
  expires_at               TIMESTAMP NOT NULL,
  delivery_status           ENUM('Sent','Failed') NOT NULL,
  delivery_message          VARCHAR(255) NULL,
  is_verified               BOOLEAN NOT NULL DEFAULT FALSE,
  verified_at               TIMESTAMP NULL,
  attempt_count             TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rrv_request FOREIGN KEY (request_id) REFERENCES record_requests(request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- CONSTRAINTS PASS
-- Implements the exclusive-arc CHECK constraints (resident_id vs.
-- unregistered_*_id — exactly one must be populated per row) and the
-- business-rule UNIQUE constraints (no duplicate dose/visit per period)
-- that were intentionally deferred during initial schema design.
--
-- REQUIRES MySQL 8.0.16+ or MariaDB 10.2.1+ for CHECK constraints to be
-- enforced (older versions parse but silently ignore CHECK). Confirm
-- your target MySQL Workbench / server version supports this before
-- relying on these for data integrity.
-- =====================================================================

-- ---------------------------------------------------------------------
-- Exclusive-arc CHECK constraints
-- (exactly one of resident_id / unregistered_*_id must be non-null)
-- ---------------------------------------------------------------------

ALTER TABLE family_planning
  ADD CONSTRAINT chk_one_client_ref_fp CHECK (
    (resident_id IS NOT NULL AND unregistered_client_id IS NULL)
    OR (resident_id IS NULL AND unregistered_client_id IS NOT NULL)
  );

ALTER TABLE timbang_records
  ADD CONSTRAINT chk_one_child_ref_timbang CHECK (
    (resident_id IS NOT NULL AND unregistered_child_id IS NULL)
    OR (resident_id IS NULL AND unregistered_child_id IS NOT NULL)
  );

ALTER TABLE deworming_records
  ADD CONSTRAINT chk_one_child_ref_deworm CHECK (
    (resident_id IS NOT NULL AND unregistered_child_id IS NULL)
    OR (resident_id IS NULL AND unregistered_child_id IS NOT NULL)
  );

ALTER TABLE vit_a_supplementation
  ADD CONSTRAINT chk_one_child_ref_vita CHECK (
    (resident_id IS NOT NULL AND unregistered_child_id IS NULL)
    OR (resident_id IS NULL AND unregistered_child_id IS NOT NULL)
  );

ALTER TABLE child_immunization
  ADD CONSTRAINT chk_one_child_ref_ci CHECK (
    (resident_id IS NOT NULL AND unregistered_child_id IS NULL)
    OR (resident_id IS NULL AND unregistered_child_id IS NOT NULL)
  );

ALTER TABLE school_immunization
  ADD CONSTRAINT chk_one_child_ref_si CHECK (
    (resident_id IS NOT NULL AND unregistered_child_id IS NULL)
    OR (resident_id IS NULL AND unregistered_child_id IS NOT NULL)
  );

ALTER TABLE child_nutrition
  ADD CONSTRAINT chk_one_child_ref_cn CHECK (
    (resident_id IS NOT NULL AND unregistered_child_id IS NULL)
    OR (resident_id IS NULL AND unregistered_child_id IS NOT NULL)
  );

ALTER TABLE sick_infant_management
  ADD CONSTRAINT chk_one_child_ref_sim CHECK (
    (resident_id IS NOT NULL AND unregistered_child_id IS NULL)
    OR (resident_id IS NULL AND unregistered_child_id IS NOT NULL)
  );

ALTER TABLE maternal_records
  ADD CONSTRAINT chk_one_client_ref_mr CHECK (
    (resident_id IS NOT NULL AND unregistered_client_id IS NULL)
    OR (resident_id IS NULL AND unregistered_client_id IS NOT NULL)
  );

-- record_request_verifications: exactly one of otp_code / verification_token,
-- matching the delivery_channel selected.
ALTER TABLE record_request_verifications
  ADD CONSTRAINT chk_verification_method CHECK (
    (delivery_channel = 'SMS' AND otp_code IS NOT NULL AND verification_token IS NULL)
    OR (delivery_channel = 'Email' AND verification_token IS NOT NULL AND otp_code IS NULL)
  );

-- ---------------------------------------------------------------------
-- Business-rule UNIQUE constraints
-- (no duplicate dose / visit / round entries for the same person/period)
-- ---------------------------------------------------------------------

-- Deworming: one entry per person per year per round
ALTER TABLE deworming_records
  ADD CONSTRAINT uq_deworm_resident_round UNIQUE (resident_id, year, deworming_round);
ALTER TABLE deworming_records
  ADD CONSTRAINT uq_deworm_unregistered_round UNIQUE (unregistered_child_id, year, deworming_round);

-- Vitamin A: one entry per person per year per round
ALTER TABLE vit_a_supplementation
  ADD CONSTRAINT uq_vita_resident_round UNIQUE (resident_id, year, round);
ALTER TABLE vit_a_supplementation
  ADD CONSTRAINT uq_vita_unregistered_round UNIQUE (unregistered_child_id, year, round);

-- Child immunization: one entry per vaccine + dose number per child_immunization record
ALTER TABLE immunization_doses
  ADD CONSTRAINT uq_dose UNIQUE (child_immunization_id, vaccine_type, dose_number);

-- Iron supplementation: one entry per month per nutrition record
ALTER TABLE iron_supplementation
  ADD CONSTRAINT uq_iron_month UNIQUE (child_nutrition_id, month_number);

-- Nutrition supplementation: one entry per supplement type + age group + dose per nutrition record
ALTER TABLE nutrition_supplementation
  ADD CONSTRAINT uq_supplement UNIQUE (child_nutrition_id, supplement_type, age_group, dose_number);

-- Maternal care: one entry per visit number per maternal record, per sub-table
ALTER TABLE anc_visits
  ADD CONSTRAINT uq_anc_visit UNIQUE (maternal_record_id, visit_number);
ALTER TABLE td_immunization
  ADD CONSTRAINT uq_td_dose UNIQUE (maternal_record_id, dose_number);
ALTER TABLE ifa_supplementation
  ADD CONSTRAINT uq_ifa_visit UNIQUE (maternal_record_id, visit_number);
ALTER TABLE mms_supplementation
  ADD CONSTRAINT uq_mms_visit UNIQUE (maternal_record_id, visit_number);
ALTER TABLE cc_supplementation
  ADD CONSTRAINT uq_cc_visit UNIQUE (maternal_record_id, visit_number);

-- Postpartum care: one entry per contact/visit number per delivery outcome
ALTER TABLE postnatal_care_visits
  ADD CONSTRAINT uq_pnc_contact UNIQUE (delivery_outcome_id, contact_number);
ALTER TABLE postpartum_ifa_supplementation
  ADD CONSTRAINT uq_postpartum_ifa_visit UNIQUE (delivery_outcome_id, visit_number);

-- =====================================================================
-- STILL DEFERRED — not enforceable as a simple table-level constraint:
--
-- 1. HPV eligibility (females, 9 yrs+ only, on school_immunization).
--    Requires a cross-table lookup (birthdate/sex live on residents or
--    unregistered_children, not on school_immunization itself). Enforce
--    at the application layer, or via a BEFORE INSERT/UPDATE trigger
--    that looks up sex + computed age from the linked resident/child
--    record and rejects hpv_1st_dose_date / hpv_2nd_dose_date otherwise.
--
-- 2. verification_token should be stored hashed at the application
--    layer (like password), not validated purely via the is_verified
--    flag — the database cannot enforce hashing on its own.
-- =====================================================================
