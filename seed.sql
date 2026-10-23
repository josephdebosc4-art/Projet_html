USE suivi_apprentissage;

-- À utiliser uniquement pour préparer une base de démonstration : ce script efface les données présentes.
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE message_recipients;
TRUNCATE TABLE message_attachments;
TRUNCATE TABLE messages;
TRUNCATE TABLE conversation_members;
TRUNCATE TABLE conversations;
TRUNCATE TABLE teams;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Tous les comptes de démonstration ont le mot de passe : password
INSERT INTO users (id, name, email, password_hash, role, created_at) VALUES
(1, 'Admin CampusLink', 'admin@esigelec.fr', '$2y$10$oFuVRglClIoHfg6VXcDpLeu23mTyAWXuSxvHdgRPAfzetetvIh3pe', 'admin', NOW()),
(2, 'Alice Martin', 'alice.apprentie@example.fr', '$2y$10$oFuVRglClIoHfg6VXcDpLeu23mTyAWXuSxvHdgRPAfzetetvIh3pe', 'apprentice', NOW()),
(3, 'Marc Dupont', 'marc.ecole@example.fr', '$2y$10$oFuVRglClIoHfg6VXcDpLeu23mTyAWXuSxvHdgRPAfzetetvIh3pe', 'school_tutor', NOW()),
(4, 'Sophie Bernard', 'sophie.entreprise@example.fr', '$2y$10$oFuVRglClIoHfg6VXcDpLeu23mTyAWXuSxvHdgRPAfzetetvIh3pe', 'company_tutor', NOW()),
(5, 'Hugo Leroy', 'hugo.apprenti@example.fr', '$2y$10$oFuVRglClIoHfg6VXcDpLeu23mTyAWXuSxvHdgRPAfzetetvIh3pe', 'apprentice', NOW()),
(6, 'Claire Robert', 'claire.ecole@example.fr', '$2y$10$oFuVRglClIoHfg6VXcDpLeu23mTyAWXuSxvHdgRPAfzetetvIh3pe', 'school_tutor', NOW()),
(7, 'Paul Petit', 'paul.entreprise@example.fr', '$2y$10$oFuVRglClIoHfg6VXcDpLeu23mTyAWXuSxvHdgRPAfzetetvIh3pe', 'company_tutor', NOW()),
(8, 'Camille Nouveau', 'camille.attente@example.fr', '$2y$10$oFuVRglClIoHfg6VXcDpLeu23mTyAWXuSxvHdgRPAfzetetvIh3pe', 'pending', NOW());

