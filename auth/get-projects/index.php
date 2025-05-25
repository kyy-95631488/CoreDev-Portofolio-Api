<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
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
        $session_token = isset($_GET['session_token']) ? $_GET['session_token'] : null;
        if (!$session_token) {
            http_response_code(401);
            echo json_encode(['error' => 'Session token is required']);
            exit;
        }

        $user = validateSessionToken($pdo, $session_token);
        if (!$user || !in_array($user['role'], ['anggota', 'dosen'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('SELECT id, name, start_date, end_date, description, thumbnail_path, frameworks, preview_link, github_link FROM projects');
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($projects as &$project) {
                $project['frameworks'] = json_decode($project['frameworks'], true);
            }

            http_response_code(200);
            echo json_encode(['projects' => $projects]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch projects: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
?>