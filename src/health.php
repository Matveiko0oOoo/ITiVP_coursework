<?php
header('Content-Type: application/json');

$status = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'services' => []
];

try {
    require_once 'config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn) {
        $status['services']['database'] = 'connected';
    } else {
        $status['services']['database'] = 'disconnected';
        $status['status'] = 'degraded';
    }
} catch (Exception $e) {
    $status['services']['database'] = 'error: ' . $e->getMessage();
    $status['status'] = 'error';
}

echo json_encode($status, JSON_PRETTY_PRINT);

