-- CMU Enrollment System: Step 1 - non-destructive enrollment-period schema
-- BACK UP YOUR DATABASE before running this script.
-- Run ONCE in phpMyAdmin, against your cmuenrollment database.
-- This migration does NOT reset students or change the current enrollment period.

CREATE TABLE IF NOT EXISTS enrollment_periods (
  period_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  semester VARCHAR(50) NOT NULL,
  school_year VARCHAR(20) NOT NULL,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  status ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'CLOSED',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME DEFAULT NULL,
  PRIMARY KEY (period_id),
  UNIQUE KEY uq_enrollment_period (semester, school_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Register the current period without changing system_settings.
INSERT INTO enrollment_periods (semester, school_year, start_date, end_date, status)
SELECT semester, school_year, start_date, end_date, enrollment_status
FROM system_settings
WHERE setting_id = 1 AND semester IS NOT NULL AND school_year IS NOT NULL
ON DUPLICATE KEY UPDATE period_id = period_id;

-- Each COR submission must belong to a specific period going forward.
-- Nullable at first: existing COR records have no reliable period metadata.
ALTER TABLE cor_submissions
  ADD COLUMN period_id INT UNSIGNED NULL AFTER student_id,
  ADD INDEX idx_cor_student_period_status (student_id, period_id, status),
  ADD CONSTRAINT fk_cor_submission_period
    FOREIGN KEY (period_id) REFERENCES enrollment_periods(period_id);

-- Check existing COR records. Assign period_id only after verifying their true period.
SELECT cor_submission_id, student_id, file_name, submitted_at, status, period_id
FROM cor_submissions ORDER BY submitted_at DESC;

SELECT period_id, semester, school_year, status FROM enrollment_periods;
