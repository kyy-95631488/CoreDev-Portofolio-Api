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

    function formatTextWithSymbols($text) {
        $formattedText = $text;
        $formattedText = preg_replace('/:\)/', '😊', $formattedText);
        $formattedText = preg_replace('/:\(/', '😔', $formattedText);
        $formattedText = preg_replace('/<3/', '❤️', $formattedText);
        $formattedText = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $formattedText);
        $formattedText = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $formattedText);
        $formattedText = str_replace("\n", '<br />', $formattedText);
        $formattedText = str_replace('[paragraph]', '</p><p>', $formattedText);
        if (!preg_match('/^<p>/', $formattedText)) {
            $formattedText = "<p>$formattedText</p>";
        }
        $formattedText = htmlspecialchars_decode(htmlspecialchars($formattedText, ENT_QUOTES, 'UTF-8'));
        return $formattedText;
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

        $required_fields = ['name', 'email', 'role', 'description', 'shortStory', 'photo', 'skills'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                http_response_code(400);
                echo json_encode(['error' => "Missing or empty required field: $field"]);
                exit;
            }
        }

        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $role = filter_var($_POST['role'], FILTER_SANITIZE_STRING);
        $description = formatTextWithSymbols($_POST['description']);
        $short_story = formatTextWithSymbols($_POST['shortStory']);
        $photo_url = filter_var($_POST['photo'], FILTER_SANITIZE_URL);
        $skills = json_decode($_POST['skills'], true);
        $linkedin = isset($_POST['linkedin']) ? filter_var($_POST['linkedin'], FILTER_SANITIZE_URL) : null;
        $github = isset($_POST['github']) ? filter_var($_POST['github'], FILTER_SANITIZE_URL) : null;
        $instagram = isset($_POST['instagram']) ? filter_var($_POST['instagram'], FILTER_SANITIZE_URL) : null;
        $whatsapp = isset($_POST['whatsapp']) ? filter_var($_POST['whatsapp'], FILTER_SANITIZE_STRING) : null;
        $portfolio_link = isset($_POST['portfolioLink']) ? filter_var($_POST['portfolioLink'], FILTER_SANITIZE_URL) : null;

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

            $stmt = $pdo->prepare('
                INSERT INTO team_members (
                    name, email, role, description, short_story, photo_url, skills,
                    linkedin, github, instagram, whatsapp, portfolio_link
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $name,
                $email,
                $role,
                $description,
                $short_story,
                $photo_url,
                json_encode($skills),
                $linkedin,
                $github,
                $instagram,
                $whatsapp,
                $portfolio_link
            ]);

            $pdo->commit();
            http_response_code(200);
            echo json_encode(['message' => 'Team member added successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add team member: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
?>