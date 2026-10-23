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
    user_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (conversation_id, user_id),
    CONSTRAINT fk_member_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_member_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    conversation_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    subject VARCHAR(120) NOT NULL,
    category ENUM('suivi', 'information', 'rendez-vous', 'autre') NOT NULL,
    body TEXT NOT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(190) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_message_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_message_team_date (team_id, created_at),
    INDEX idx_message_sender_date (sender_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE message_recipients (
    message_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED NOT NULL,
    read_at DATETIME NULL,
    PRIMARY KEY (message_id, recipient_id),
    CONSTRAINT fk_recipient_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    CONSTRAINT fk_recipient_user FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_recipient_inbox (recipient_id, read_at)
) ENGINE=InnoDB;

CREATE TABLE message_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message_id INT UNSIGNED NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    original_name VARCHAR(190) NOT NULL,
    CONSTRAINT fk_attachment_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB;
