<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/bas_sso_auth.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/installation_sync.php';
require_once __DIR__ . '/../includes/users.php';
require_once __DIR__ . '/../includes/threecx_calls.php';
require_once __DIR__ . '/../includes/installation_links.php';

header('Content-Type: application/json; charset=utf-8');

if (!empty($_GET['embed'])) {
    abas_set_embed_session(true);
}

$user = abas_require_login();
abas_require_role(['vagtcentral', 'admin']);

$conn = abas_db();
$type = (string) ($_GET['type'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));

if ($type === 'installations') {
    $callerUserId = (int) ($_GET['caller_user_id'] ?? 0);
    $filterToOwner = false;
    if ($callerUserId > 0) {
        $ownerStmt = $conn->prepare(
            'SELECT id, role FROM users WHERE id = ? AND active = 1 AND role IN ("anlaegsejer", "anlaegsafprover") LIMIT 1'
        );
        $ownerStmt->bind_param('i', $callerUserId);
        $ownerStmt->execute();
        $ownerRow = $ownerStmt->get_result()->fetch_assoc();
        $ownerStmt->close();
        $filterToOwner = (bool) $ownerRow;
    }

    if ($filterToOwner) {
        $linked = abas_user_linked_installations($conn, $callerUserId);
        if ($q !== '') {
            $qLower = mb_strtolower($q, 'UTF-8');
            $linked = array_values(array_filter($linked, static function (array $row) use ($qLower): bool {
                $hay = mb_strtolower(
                    (string) ($row['miscno2'] ?? '') . ' ' . (string) ($row['name'] ?? '') . ' ' . (string) ($row['city'] ?? ''),
                    'UTF-8'
                );

                return str_contains($hay, $qLower);
            }));
        }
        $items = array_slice($linked, 0, 50);
    } else {
        if (mb_strlen($q) < 2) {
            echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $items = abas_search_installations_local($conn, $q, true, 0);
        if ($items === [] && abas_is_miscno2_query($q)) {
            try {
                $items = abas_search_installations_from_api($conn, $user, $q);
            } catch (Throwable $e) {
                echo json_encode(['items' => [], 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }

    $out = [];
    foreach ($items as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'miscno2' => (string) ($row['miscno2'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'city' => (string) ($row['city'] ?? ''),
        ];
    }

    echo json_encode(['items' => $out], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($type === 'montors') {
    $rows = abas_search_montors($conn, $q, 40);
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'username' => (string) ($row['username'] ?? ''),
            'display_name' => abas_user_display_name($row),
            'phone' => (string) ($row['phone'] ?? ''),
            'company_name' => (string) ($row['company_name'] ?? ''),
        ];
    }

    echo json_encode(['items' => $out], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($type === 'linked') {
    $installationId = (int) ($_GET['installation_id'] ?? 0);
    if ($installationId <= 0) {
        echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['items' => abas_vc_linked_installation_options($conn, $installationId)], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($type === 'calls') {
    echo json_encode(['items' => abas_threecx_list_active_calls($conn)], JSON_UNESCAPED_UNICODE);
    exit;
}

abas_json_error(400, 'Ukendt søgetype.', 'http_error', ['type' => $type]);
