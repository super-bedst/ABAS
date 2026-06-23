<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

$conn = abas_db();
$stmt = $conn->query(
    'SELECT ss.id FROM service_sessions ss
     WHERE ss.status="active" AND ss.unlimited=0 AND ss.expires_at IS NOT NULL AND ss.expires_at < NOW()'
);
$count = 0;
while ($row = $stmt->fetch_assoc()) {
    $id = (int) $row['id'];
    $u = $conn->prepare('UPDATE service_sessions SET status="expired", ended_at=NOW() WHERE id=?');
    $u->bind_param('i', $id);
    $u->execute();
    $u->close();
    $count++;
}
echo date('c') . " expired sessions: $count\n";
