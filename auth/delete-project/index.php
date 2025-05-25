<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: DELETE');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $host = 'localhost';
    $dbname = 'db_porto_coredev';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    function validateSessionToken($pdo, $session_token) {
        $stmt = $pdo->prepare('SELECT id, role FROM users WHERE session_token = ?');
        $stmt->execute([$session_token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['session_token']) || !isset($input['project_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Session token and project ID are required']);
            exit;
        }

        $session_token = $input['session_token'];
        $project_id = filter_var($input['project_id'], FILTER_VALIDATE_INT);

        if (!$project_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid project ID']);
            exit;
        }

        $user = validateSessionToken($pdo, $session_token);

        if (!$user || !in_array($user['role'], ['anggota', 'dosen'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Check if project exists
            $stmt = $pdo->prepare('SELECT id FROM projects WHERE id = ?');
            $stmt->execute([$project_id]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Project not found']);
                $pdo->rollBack();
                exit;
            }

            // Delete related team members
            $stmt = $pdo->prepare('DELETE FROM project_team_members WHERE project_id = ?');
            $stmt->execute([$project_id]);

            // Delete project
            $stmt = $pdo->prepare('DELETE FROM projects WHERE id = ?');
            $stmt->execute([$project_id]);

            $pdo->commit();
            http_response_code(200);
            echo json_encode(['message' => 'Project deleted successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete project: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
?>