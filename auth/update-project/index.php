<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: PUT');
    header('Access-Control-Allow-Headers: Content-Type');

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

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = [];
        parse_str(file_get_contents('php://input'), $data);

        if (!isset($data['session_token']) || !isset($data['project_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Session token and project ID are required']);
            exit;
        }

        $session_token = $data['session_token'];
        $project_id = filter_var($data['project_id'], FILTER_VALIDATE_INT);

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

        $required_fields = ['name', 'startDate', 'endDate', 'frameworks'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                http_response_code(400);
                echo json_encode(['error' => "Missing or empty required field: $field"]);
                exit;
            }
        }

        $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
        $description = isset($data['description']) ? filter_var($data['description'], FILTER_SANITIZE_STRING) : null;
        $start_date = $data['startDate'];
        $end_date = $data['endDate'];
        $preview_link = isset($data['previewLink']) ? filter_var($data['previewLink'], FILTER_SANITIZE_URL) : null;
        $github_link = isset($data['githubLink']) ? filter_var($data['githubLink'], FILTER_SANITIZE_URL) : null;
        $frameworks = json_decode($data['frameworks'], true);
        $team_members = isset($data['teamMembers']) ? json_decode($data['teamMembers'], true) : [];
        $thumbnail_path = isset($data['thumbnail_path']) ? filter_var($data['thumbnail_path'], FILTER_SANITIZE_URL) : null;

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data for frameworks or team members']);
            exit;
        }

        if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format']);
            exit;
        }

        if ($thumbnail_path && !filter_var($thumbnail_path, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid thumbnail URL']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT id FROM projects WHERE id = ?');
            $stmt->execute([$project_id]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Project not found']);
                $pdo->rollBack();
                exit;
            }

            $stmt = $pdo->prepare('
                UPDATE projects 
                SET name = ?, description = ?, start_date = ?, end_date = ?, thumbnail_path = ?, frameworks = ?, preview_link = ?, github_link = ?
                WHERE id = ?
            ');
            $stmt->execute([
                $name,
                $description,
                $start_date,
                $end_date,
                $thumbnail_path,
                json_encode($frameworks),
                $preview_link,
                $github_link,
                $project_id
            ]);

            $stmt = $pdo->prepare('DELETE FROM project_team_members WHERE project_id = ?');
            $stmt->execute([$project_id]);

            if (!empty($team_members)) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND role = "anggota"');
                $insert_team = $pdo->prepare('INSERT INTO project_team_members (project_id, user_id) VALUES (?, ?)');
                
                foreach ($team_members as $email) {
                    $stmt->execute([$email]);
                    $user_id = $stmt->fetchColumn();
                    if ($user_id) {
                        $insert_team->execute([$project_id, $user_id]);
                    }
                }
            }

            $pdo->commit();
            http_response_code(200);
            echo json_encode(['message' => 'Project updated successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update project: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
?>