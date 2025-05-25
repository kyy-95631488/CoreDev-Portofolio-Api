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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Project ID is required and must be numeric']);
            exit;
        }

        try {
            $stmt = $pdo->prepare('
                SELECT id, name, description, thumbnail_path, start_date, end_date, frameworks, preview_link, github_link
                FROM projects WHERE id = ?
            ');
            $stmt->execute([$_GET['id']]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($project) {
                $stmt = $pdo->prepare('
                    SELECT u.email
                    FROM project_team_members ptm
                    JOIN users u ON ptm.user_id = u.id
                    WHERE ptm.project_id = ?
                ');
                $stmt->execute([$_GET['id']]);
                $team_members = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $project['team_members'] = $team_members;

                $project['frameworks'] = json_decode($project['frameworks'], true);

                http_response_code(200);
                echo json_encode(['project' => $project]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Project not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch project: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
?>