<?php
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: PUT');
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

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Since PHP doesn't natively parse multipart/form-data for PUT, we need to handle it manually
        $input = file_get_contents('php://input');
        $boundary = substr($input, 0, strpos($input, "\r\n"));
        $parts = array_slice(explode($boundary, $input), 1);

        $data = [];
        foreach ($parts as $part) {
            if ($part == "--\r\n") continue;
            $content = explode("\r\n\r\n", $part, 2);
            if (count($content) < 2) continue;
            $headers = $content[0];
            $value = trim($content[1]);
            preg_match('/name="([^"]+)"/', $headers, $matches);
            if (isset($matches[1])) {
                $data[$matches[1]] = $value;
            }
        }

        if (!isset($data['session_token']) || empty(trim($data['session_token']))) {
            http_response_code(401);
            echo json_encode(['error' => 'Session token is required']);
            exit;
        }

        $session_token = filter_var($data['session_token'], FILTER_SANITIZE_STRING);
        $user = validateSessionToken($pdo, $session_token);

        if (!$user || !in_array($user['role'], ['anggota', 'dosen'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }

        $required_fields = ['name', 'email', 'role', 'description', 'shortStory', 'photo', 'skills'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                http_response_code(400);
                echo json_encode(['error' => "Missing or empty required field: $field"]);
                exit;
            }
        }

        $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $role = filter_var($data['role'], FILTER_SANITIZE_STRING);
        $description = filter_var($data['description'], FILTER_SANITIZE_STRING);
        $short_story = filter_var($data['shortStory'], FILTER_SANITIZE_STRING);
        $photo_url = filter_var($data['photo'], FILTER_SANITIZE_URL);
        $skills = json_decode($data['skills'], true);
        $linkedin = isset($data['linkedin']) ? filter_var($data['linkedin'], FILTER_SANITIZE_URL) : null;
        $github = isset($data['github']) ? filter_var($data['github'], FILTER_SANITIZE_URL) : null;
        $instagram = isset($data['instagram']) ? filter_var($data['instagram'], FILTER_SANITIZE_URL) : null;
        $whatsapp = isset($data['whatsapp']) ? filter_var($data['whatsapp'], FILTER_SANITIZE_STRING) : null;
        $portfolio_link = isset($data['portfolioLink']) ? filter_var($data['portfolioLink'], FILTER_SANITIZE_URL) : null;

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data for skills']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            exit;
        }

        if (!in_array($role, ['android_developer', 'frontend', 'backend', 'uiux', 'qa', 'fullstack', 'devops'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role']);
            exit;
        }

        if (!filter_var($photo_url, FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid photo URL']);
            exit;
        }

        if (empty($skills)) {
            http_response_code(400);
            echo json_encode(['error' => 'At least one skill is required']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Check if the team member exists
            $stmt = $pdo->prepare('SELECT email FROM team_members WHERE email = ?');
            $stmt->execute([$email]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['error' => 'Team member not found']);
                exit;
            }

            $stmt = $pdo->prepare('
                UPDATE team_members SET
                    name = ?,
                    role = ?,
                    description = ?,
                    short_story = ?,
                    photo_url = ?,
                    skills = ?,
                    linkedin = ?,
                    github = ?,
                    instagram = ?,
                    whatsapp = ?,
                    portfolio_link = ?
                WHERE email = ?
            ');
            $stmt->execute([
                $name,
                $role,
                $description,
                $short_story,
                $photo_url,
                json_encode($skills),
                $linkedin,
                $github,
                $instagram,
                $whatsapp,
                $portfolio_link,
                $email
            ]);

            $pdo->commit();
            http_response_code(200);
            echo json_encode(['message' => 'Team member updated successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update team member: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
?>