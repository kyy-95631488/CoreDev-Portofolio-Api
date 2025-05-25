<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: DELETE, OPTIONS');
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

        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['session_token']) || !isset($input['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing session_token or email']);
            exit;
        }

        $sessionToken = $input['session_token'];
        $emailToDelete = $input['email'];

        $stmt = $pdo->prepare('SELECT role FROM users WHERE session_token = ? AND token_expiry > NOW()');
        $stmt->execute([$sessionToken]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !in_array($user['role'], ['anggota', 'dosen'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized: Only anggota or dosen can delete users']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$emailToDelete]);
        if ($stmt->fetchColumn() == 0) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE email = ?');
        $stmt->execute([$emailToDelete]);

        http_response_code(200);
        echo json_encode(['message' => 'User deleted successfully']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
?>