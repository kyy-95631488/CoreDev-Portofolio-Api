<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    require_once '../../vendor/autoload.php';

    $host = 'localhost';
    $dbname = 'db_porto_coredev';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['session_token'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing session token']);
            exit;
        }

        $sessionToken = trim($input['session_token']);

        $stmt = $pdo->prepare('SELECT id, token_expiry, role FROM users WHERE session_token = ?');
        $stmt->execute([$sessionToken]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid session token', 'valid' => false]);
            exit;
        }

        if (strtotime($user['token_expiry']) < time()) {
            http_response_code(401);
            echo json_encode(['error' => 'Session expired', 'valid' => false]);
            $stmt = $pdo->prepare('UPDATE users SET session_token = NULL, token_expiry = NULL WHERE id = ?');
            $stmt->execute([$user['id']]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'message' => 'Valid session',
            'valid' => true,
            'role' => $user['role']
        ]);
        exit;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
?>