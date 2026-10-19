CREATE DATABASE IF NOT EXISTS suivi_apprentissage
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE suivi_apprentissage;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('pending', 'apprentice', 'school_tutor', 'company_tutor', 'admin') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    apprentice_id INT UNSIGNED NOT NULL UNIQUE,
    school_tutor_id INT UNSIGNED NOT NULL,
    company_tutor_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_team_apprentice FOREIGN KEY (apprentice_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_school_tutor FOREIGN KEY (school_tutor_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_team_company_tutor FOREIGN KEY (company_tutor_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_team_school_tutor (school_tutor_id),
    INDEX idx_team_company_tutor (company_tutor_id)
) ENGINE=InnoDB;

CREATE TABLE conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    subject VARCHAR(120) NOT NULL,
    category ENUM('suivi', 'information', 'rendez-vous', 'autre') NOT NULL,
    closed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_conversation_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_conversation_team (team_id, closed_at)
) ENGINE=InnoDB;

CREATE TABLE conversation_members (
    conversation_id INT UNSIGNED NOT NULL,
