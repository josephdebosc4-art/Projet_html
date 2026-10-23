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

INSERT INTO teams (id, apprentice_id, school_tutor_id, company_tutor_id) VALUES
(1, 2, 3, 4),
(2, 5, 3, 7);

INSERT INTO conversations (id, team_id, subject, category, created_at) VALUES
(1, 1, 'Compte rendu de la première semaine', 'suivi', NOW() - INTERVAL 3 DAY),
(2, 2, 'Disponibilités pour le point mensuel', 'rendez-vous', NOW() - INTERVAL 1 DAY);

INSERT INTO conversation_members (conversation_id, user_id) VALUES
(1, 2), (1, 3), (1, 4), (2, 5), (2, 3), (2, 7);

INSERT INTO messages (id, team_id, conversation_id, sender_id, subject, category, body, created_at) VALUES
(1, 1, 1, 2, 'Compte rendu de la première semaine', 'suivi', 'Bonjour,\n\nVoici un petit bilan de ma première semaine. L’intégration se passe bien et j’ai commencé à découvrir les outils de l’équipe.\n\nAlice', NOW() - INTERVAL 3 DAY),
(2, 1, 1, 3, 'Re : Compte rendu de la première semaine', 'suivi', 'Merci Alice. Pense à noter les missions principales pour notre prochain point.', NOW() - INTERVAL 2 DAY),
(3, 2, 2, 7, 'Disponibilités pour le point mensuel', 'rendez-vous', 'Bonjour,\n\nJe suis disponible mardi après-midi ou jeudi matin. Quel créneau vous convient ?', NOW() - INTERVAL 1 DAY);

INSERT INTO message_recipients (message_id, recipient_id, read_at) VALUES
(1, 3, NOW() - INTERVAL 2 DAY),
(1, 4, NULL),
(2, 2, NOW() - INTERVAL 1 DAY),
(2, 4, NULL),
(3, 3, NULL),
(3, 5, NULL);
