<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/functions.php';
$admin = require_admin();
$error = '';

if (is_post()) {
    verify_csrf();
    $userId = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $newRole = (string) ($_POST['role'] ?? '');
    $allowedRoles = ['pending', 'apprentice', 'school_tutor', 'company_tutor'];

    if (!$userId || !in_array($newRole, $allowedRoles, true)) {
        $error = 'La modification demandée n’est pas valide.';
    } elseif ($userId === (int) $admin['id']) {
        $error = 'Vous ne pouvez pas modifier votre propre rôle.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT
                EXISTS(SELECT 1 FROM teams WHERE apprentice_id = ?) AS is_apprentice,
                EXISTS(SELECT 1 FROM teams WHERE school_tutor_id = ?) AS is_school,
                EXISTS(SELECT 1 FROM teams WHERE company_tutor_id = ?) AS is_company'
        );
        $stmt->execute([$userId, $userId, $userId]);
        $links = $stmt->fetch();
        $requiredRole = null;
        if ($links['is_apprentice']) $requiredRole = 'apprentice';
        if ($links['is_school']) $requiredRole = 'school_tutor';
        if ($links['is_company']) $requiredRole = 'company_tutor';

        if ($requiredRole && $newRole !== $requiredRole) {
            $error = 'Retirez d’abord cet utilisateur de son équipe avant de changer son rôle.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND role <> 'admin'");
            $stmt->execute([$newRole, $userId]);
            flash('success', 'Le rôle a été mis à jour.');
            redirect('admin/users.php');
        }
    }
}

$users = $pdo->query(
    "SELECT u.id, u.name, u.email, u.role, u.created_at,
            (SELECT GROUP_CONCAT(t.id ORDER BY t.id SEPARATOR ', ')
             FROM teams t WHERE t.apprentice_id = u.id OR t.school_tutor_id = u.id OR t.company_tutor_id = u.id) AS team_ids
     FROM users u ORDER BY FIELD(u.role, 'pending', 'apprentice', 'school_tutor', 'company_tutor', 'admin'), u.name"
)->fetchAll();
