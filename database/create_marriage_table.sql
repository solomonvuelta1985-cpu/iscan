-- Create certificate_of_marriage table
CREATE TABLE IF NOT EXISTS `certificate_of_marriage` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Registry Information
    `registry_no` VARCHAR(100) DEFAULT NULL,
    `date_of_registration` DATE NOT NULL,

    -- Husband's Information
    `husband_first_name` VARCHAR(100) NOT NULL,
    `husband_middle_name` VARCHAR(100) DEFAULT NULL,
    `husband_last_name` VARCHAR(100) NOT NULL,
    `husband_date_of_birth` DATE NOT NULL,
    `husband_place_of_birth` VARCHAR(255) NOT NULL,
    `husband_residence` TEXT NOT NULL,
    `husband_father_name` VARCHAR(255) DEFAULT NULL,
    `husband_father_residence` TEXT DEFAULT NULL,
    `husband_mother_name` VARCHAR(255) DEFAULT NULL,
    `husband_mother_residence` TEXT DEFAULT NULL,

    -- Wife's Information
    `wife_first_name` VARCHAR(100) NOT NULL,
    `wife_middle_name` VARCHAR(100) DEFAULT NULL,
    `wife_last_name` VARCHAR(100) NOT NULL,
    `wife_date_of_birth` DATE NOT NULL,
    `wife_place_of_birth` VARCHAR(255) NOT NULL,
    `wife_residence` TEXT NOT NULL,
    `wife_father_name` VARCHAR(255) DEFAULT NULL,
    `wife_father_residence` TEXT DEFAULT NULL,
    `wife_mother_name` VARCHAR(255) DEFAULT NULL,
    `wife_mother_residence` TEXT DEFAULT NULL,

    -- Marriage Information
    `date_of_marriage` DATE NOT NULL,
    `place_of_marriage` VARCHAR(255) NOT NULL,

    -- PDF File
    `pdf_filename` VARCHAR(255) DEFAULT NULL,
    `pdf_filepath` VARCHAR(500) DEFAULT NULL,

    -- Metadata
    `status` ENUM('Active', 'Archived', 'Deleted') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT(11) DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,

    PRIMARY KEY (`id`),
    INDEX `idx_registry_no` (`registry_no`),
    INDEX `idx_husband_name` (`husband_last_name`, `husband_first_name`),
    INDEX `idx_wife_name` (`wife_last_name`, `wife_first_name`),
    INDEX `idx_date_of_marriage` (`date_of_marriage`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
