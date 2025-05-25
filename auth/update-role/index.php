<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: PUT, OPTIONS');
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

        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['session_token']) || !isset($input['email']) || !isset($input['role'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing session_token, email, or role']);
            exit;
        }

        $sessionToken = $input['session_token'];
        $emailToUpdate = $input['email'];
        $newRole = $input['role'];

        if (!in_array($newRole, ['user', 'anggota', 'dosen'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT role FROM users WHERE session_token = ? AND token_expiry > NOW()');
        $stmt->execute([$sessionToken]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !in_array($user['role'], ['anggota', 'dosen'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized: Only anggota or dosen can update roles']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$emailToUpdate]);
        if ($stmt->fetchColumn() == 0) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE email = ?');
        $stmt->execute([$newRole, $emailToUpdate]);

        http_response_code(200);
        echo json_encode(['message' => 'Role updated successfully']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
?>