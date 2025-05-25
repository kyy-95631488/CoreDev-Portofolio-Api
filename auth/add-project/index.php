<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['session_token'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Session token is required']);
            exit;
        }

        $session_token = $_POST['session_token'];
        $user = validateSessionToken($pdo, $session_token);

        if (!$user || !in_array($user['role'], ['anggota', 'dosen'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }

        $required_fields = ['name', 'startDate', 'endDate', 'frameworks'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                http_response_code(400);
                echo json_encode(['error' => "Missing or empty required field: $field"]);
                exit;
            }
        }

        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $description = isset($_POST['description']) ? filter_var($_POST['description'], FILTER_SANITIZE_STRING) : null;
        $start_date = $_POST['startDate'];
        $end_date = $_POST['endDate'];
        $preview_link = isset($_POST['previewLink']) ? filter_var($_POST['previewLink'], FILTER_SANITIZE_URL) : null;
        $github_link = isset($_POST['githubLink']) ? filter_var($_POST['githubLink'], FILTER_SANITIZE_URL) : null;
        $frameworks = json_decode($_POST['frameworks'], true);
        $team_members = isset($_POST['teamMembers']) ? json_decode($_POST['teamMembers'], true) : [];
        $thumbnail_path = isset($_POST['thumbnail_path']) ? filter_var($_POST['thumbnail_path'], FILTER_SANITIZE_URL) : null;

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

        // Validate thumbnail URL if provided
        if ($thumbnail_path && !filter_var($thumbnail_path, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid thumbnail URL']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('
                INSERT INTO projects (name, description, start_date, end_date, thumbnail_path, frameworks, preview_link, github_link)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $name,
                $description,
                $start_date,
                $end_date,
                $thumbnail_path,
                json_encode($frameworks),
                $preview_link,
                $github_link
            ]);
            $project_id = $pdo->lastInsertId();

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
            echo json_encode(['message' => 'Project added successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add project: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
?>