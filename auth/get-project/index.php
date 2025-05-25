<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['session_token']) || !isset($_GET['project_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Session token and project ID are required']);
            exit;
        }

        $session_token = $_GET['session_token'];
        $project_id = filter_var($_GET['project_id'], FILTER_VALIDATE_INT);

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
            $stmt = $pdo->prepare('
                SELECT id, name, description, start_date, end_date, thumbnail_path, frameworks, preview_link, github_link
                FROM projects 
                WHERE id = ?
            ');
            $stmt->execute([$project_id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) {
                http_response_code(404);
                echo json_encode(['error' => 'Project not found']);
                exit;
            }

            $project['frameworks'] = json_decode($project['frameworks'], true) ?? [];

            $stmt = $pdo->prepare('
                SELECT u.email 
                FROM project_team_members ptm 
                JOIN users u ON ptm.user_id = u.id 
                WHERE ptm.project_id = ?
            ');
            $stmt->execute([$project_id]);
            $team_members = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $project['team_members'] = $team_members;

            http_response_code(200);
            echo json_encode(['project' => $project]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch project: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
?>