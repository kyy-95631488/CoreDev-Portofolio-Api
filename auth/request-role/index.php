<?php
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    require_once '../../vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $host = 'localhost';
    $dbname = 'db_porto_coredev';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            echo json_encode(['message' => 'OK']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['session_token']) || !isset($input['requested_role'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing session token or requested role']);
            exit;
        }

        $sessionToken = trim($input['session_token']);
        $requestedRole = trim($input['requested_role']);

        if (!in_array($requestedRole, ['anggota', 'dosen'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid requested role']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id, email, role, token_expiry, role_request_count, last_role_request, role_request_date FROM users WHERE session_token = ?');
        $stmt->execute([$sessionToken]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid session token']);
            exit;
        }

        if (strtotime($user['token_expiry']) < time()) {
            http_response_code(401);
            echo json_encode(['error' => 'Session expired']);
            $stmt = $pdo->prepare('UPDATE users SET session_token = NULL, token_expiry = NULL WHERE id = ?');
            $stmt->execute([$user['id']]);
            exit;
        }

        if ($user['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Only users with role "user" can request a role change']);
            exit;
        }

        $currentDate = date('Y-m-d');
        $requestCount = $user['role_request_count'];
        $lastRequestDate = $user['role_request_date'];
        $lastRequestTime = $user['last_role_request'] ? strtotime($user['last_role_request']) : null;

        if ($lastRequestDate !== $currentDate) {
            $requestCount = 0;
            $stmt = $pdo->prepare('UPDATE users SET role_request_count = 0, role_request_date = ? WHERE id = ?');
            $stmt->execute([$currentDate, $user['id']]);
        }

        if ($requestCount >= 3) {
            http_response_code(429);
            echo json_encode(['error' => 'Maximum 3 role requests per day reached']);
            exit;
        }

        if ($lastRequestTime && (time() - $lastRequestTime < 30)) {
            $secondsRemaining = 30 - (time() - $lastRequestTime);
            http_response_code(429);
            echo json_encode(['error' => "Please wait $secondsRemaining seconds before making another request"]);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE users SET role_request_count = ?, last_role_request = NOW(), role_request_date = ? WHERE id = ?');
        $stmt->execute([$requestCount + 1, $currentDate, $user['id']]);

        $stmt = $pdo->prepare('SELECT email FROM users WHERE role = ?');
        $stmt->execute(['anggota']);
        $anggotaUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($anggotaUsers)) {
            http_response_code(404);
            echo json_encode(['error' => 'No users with role anggota found']);
            exit;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'hendriansyahrizkysetiawan@gmail.com';
            $mail->Password = 'whqedhvrscuhaeeu';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('hendriansyahrizkysetiawan@gmail.com', 'No-Reply');

            foreach ($anggotaUsers as $anggota) {
                $mail->addAddress($anggota['email']);
            }

            $mail->isHTML(true);
            $mail->Subject = 'New Role Request';
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 500px; margin: auto; border: 1px solid #eee; padding: 20px; border-radius: 10px; background-color: #f9f9f9;">
                <h2 style="color:rgb(76, 111, 175); text-align: center;">Role Request Notification</h2>
                <p>Halo,</p>
                <p>User dengan email <strong>' . htmlspecialchars($user['email']) . '</strong> telah meminta untuk mengubah perannya menjadi <strong>' . htmlspecialchars($requestedRole) . '</strong>.</p>
                <p>Silakan tinjau permintaan ini di dashboard CoreDev.</p>
                <p style="margin-top: 30px; font-size: 12px; color: #888;">Email ini dikirim secara otomatis. Mohon untuk tidak membalas.</p>
            </div>';

            $mail->send();
            http_response_code(200);
            echo json_encode(['message' => 'Role request sent successfully to anggota users']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send role request email: ' . $mail->ErrorInfo]);
            exit;
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
?>