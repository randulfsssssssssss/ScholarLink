-- ScholarLink Database Schema
-- MySQL / MariaDB compatible
-- ------------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- Users table: stores all user accounts (student, organization, admin)
CREATE TABLE `users` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`            VARCHAR(36) NOT NULL DEFAULT '',
    `email`           VARCHAR(255) NOT NULL,
    `password_hash`   VARCHAR(255) NOT NULL,
    `role`            ENUM('student', 'organization', 'admin') NOT NULL DEFAULT 'student',
    `first_name`      VARCHAR(100) NOT NULL DEFAULT '',
    `last_name`       VARCHAR(100) NOT NULL DEFAULT '',
    `school`          VARCHAR(255) DEFAULT NULL,
    `graduation_year` YEAR DEFAULT NULL,
    `organization_name` VARCHAR(255) DEFAULT NULL,
    `phone`           VARCHAR(30) DEFAULT NULL,
    `profile_complete` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
    `email_verified`  TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login`      TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_uuid` (`uuid`),
    KEY `idx_role` (`role`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens for real password recovery flow
CREATE TABLE `password_reset_tokens` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `token`       VARCHAR(255) NOT NULL,
    `expires_at`  TIMESTAMP NOT NULL,
    `used`        TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_token` (`token`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_prt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scholarship categories
CREATE TABLE `categories` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scholarships
CREATE TABLE `scholarships` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` INT UNSIGNED NOT NULL,
    `title`           VARCHAR(255) NOT NULL,
    `description`     TEXT NOT NULL,
    `amount`          DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
    `category_id`     INT UNSIGNED NOT NULL,
    `deadline`        DATE NOT NULL,
    `status`          ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    `is_verified`     TINYINT(1) NOT NULL DEFAULT 0,
    `requirements_json` JSON DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `published_at`    TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_org_id` (`organization_id`),
    KEY `idx_category` (`category_id`),
    KEY `idx_deadline` (`deadline`),
    KEY `idx_status` (`status`),
    KEY `idx_is_verified` (`is_verified`),
    CONSTRAINT `fk_scholarship_org` FOREIGN KEY (`organization_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_scholarship_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Application status enum values:
-- started, under_review, approved, declined, withdrawn
CREATE TABLE `scholarship_requirements` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `scholarship_id`     INT UNSIGNED NOT NULL,
    `requirement_type`   VARCHAR(50) NOT NULL,
    `label`              VARCHAR(255) NOT NULL,
    `description`        TEXT DEFAULT NULL,
    `is_required`        TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order`         INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_scholarship` (`scholarship_id`),
    CONSTRAINT `fk_req_scholarship` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Applications
CREATE TABLE `applications` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED NOT NULL,
    `scholarship_id`  INT UNSIGNED NOT NULL,
    `organization_id` INT UNSIGNED NOT NULL,
    `status`          ENUM('started', 'under_review', 'approved', 'declined', 'withdrawn') NOT NULL DEFAULT 'started',
    `submitted_at`    TIMESTAMP NULL DEFAULT NULL,
    `reviewed_at`     TIMESTAMP NULL DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_scholarship` (`user_id`, `scholarship_id`),
    KEY `idx_scholarship` (`scholarship_id`),
    KEY `idx_organization` (`organization_id`),
    KEY `idx_status` (`status`),
    KEY `idx_user_status` (`user_id`, `status`),
    CONSTRAINT `fk_application_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_application_scholarship` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_application_organization` FOREIGN KEY (`organization_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Application documents / file uploads
CREATE TABLE `application_documents` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id`      INT UNSIGNED NOT NULL,
    `requirement_id`      INT UNSIGNED NOT NULL,
    `file_name`           VARCHAR(255) NOT NULL,
    `file_path`           VARCHAR(500) NOT NULL,
    `file_size`           INT UNSIGNED NOT NULL,
    `file_type`           VARCHAR(50) NOT NULL,
    `file_extension`      VARCHAR(10) NOT NULL,
    `uploaded_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `uploader_id`         INT UNSIGNED NOT NULL,
    `is_current`          TINYINT(1) NOT NULL DEFAULT 1,
    `review_status`       ENUM('missing', 'uploaded', 'reviewed', 'approved', 'rejected') NOT NULL DEFAULT 'uploaded',
    `review_notes`        TEXT DEFAULT NULL,
    `reviewed_at`         TIMESTAMP NULL DEFAULT NULL,
    `reviewed_by`         INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_application` (`application_id`),
    KEY `idx_requirement` (`requirement_id`),
    KEY `idx_uploader` (`uploader_id`),
    CONSTRAINT `fk_doc_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_doc_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `scholarship_requirements` (`id`),
    CONSTRAINT `fk_doc_uploader` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_doc_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Application requirement progress tracking
CREATE TABLE `application_requirements` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id`  INT UNSIGNED NOT NULL,
    `requirement_id`  INT UNSIGNED NOT NULL,
    `status`          ENUM('missing', 'uploaded', 'reviewed', 'approved', 'rejected') NOT NULL DEFAULT 'missing',
    `document_id`     INT UNSIGNED NULL DEFAULT NULL,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_app_requirement` (`application_id`, `requirement_id`),
    KEY `idx_application` (`application_id`),
    KEY `idx_requirement` (`requirement_id`),
    KEY `idx_document` (`document_id`),
    CONSTRAINT `fk_areq_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_areq_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `scholarship_requirements` (`id`),
    CONSTRAINT `fk_areq_document` FOREIGN KEY (`document_id`) REFERENCES `application_documents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookmarks: students save scholarships for later
CREATE TABLE `bookmarks` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `scholarship_id` INT UNSIGNED NOT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_scholarship` (`user_id`, `scholarship_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_scholarship` (`scholarship_id`),
    CONSTRAINT `fk_bookmark_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bookmark_scholarship` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log for admin actions and document access
CREATE TABLE `audit_logs` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NULL DEFAULT NULL,
    `action`       VARCHAR(100) NOT NULL,
    `entity_type`  VARCHAR(50) DEFAULT NULL,
    `entity_id`    INT UNSIGNED NULL DEFAULT NULL,
    `details`      JSON DEFAULT NULL,
    `ip_address`   VARCHAR(45) DEFAULT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_action` (`user_id`, `action`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications / messages between users
CREATE TABLE `messages` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sender_id`   INT UNSIGNED NOT NULL,
    `recipient_id` INT UNSIGNED NOT NULL,
    `subject`     VARCHAR(255) NOT NULL,
    `body`        TEXT NOT NULL,
    `application_id` INT UNSIGNED NULL DEFAULT NULL,
    `scholarship_id` INT UNSIGNED NULL DEFAULT NULL,
    `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_recipient` (`recipient_id`, `is_read`),
    KEY `idx_sender` (`sender_id`),
    KEY `idx_application` (`application_id`),
    CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_message_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_message_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_message_scholarship` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
