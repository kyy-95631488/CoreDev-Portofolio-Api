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
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $segments = [];
        $member_id = isset($_GET['id']) ? $_GET['id'] : null;

        if (!$member_id) {
            $segments = explode('/', rtrim($uri, '/'));
            $member_id = end($segments);
        }

        error_log("URI: $uri");
        error_log("Segments: " . print_r($segments, true));
        error_log("Member ID: $member_id");

        if (!is_numeric($member_id) || $member_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid team member ID']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('
                SELECT id, name, email, role, description, short_story, photo_url, skills,
                    linkedin, github, instagram, whatsapp, portfolio_link
                FROM team_members
                WHERE id = ?
            ');
            $stmt->execute([(int)$member_id]);
            $team_member = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$team_member) {
                http_response_code(404);
                echo json_encode(['error' => 'Team member not found']);
                exit;
            }

            $team_member['skills'] = json_decode($team_member['skills'], true) ?: [];

            http_response_code(200);
            echo json_encode(['team_member' => $team_member]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch team member: ' . $e->getMessage()]);
            exit;
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
?>