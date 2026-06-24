<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$email = strtolower(trim($_GET['email'] ?? ''));
$domain = abas_email_domain($email);
if ($domain === '') {
    echo json_encode(['company' => null, 'message' => 'Ugyldig e-mail']);
    exit;
}

$conn = abas_db();
$installer = abas_installer_approved_for_domain($conn, $domain);
if (!$installer) {
    echo json_encode(['company' => null, 'message' => 'Domænet er ikke godkendt til montør-registrering']);
    exit;
}

echo json_encode(['company' => $installer['company_name']], JSON_UNESCAPED_UNICODE);
