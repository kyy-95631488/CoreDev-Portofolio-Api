<?php
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
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing action field']);
            exit;
        }

        $action = $input['action'];

        if ($action === 'login') {
            if (!isset($input['email']) || !isset($input['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing email or password']);
                exit;
            }

            $email = trim($input['email']);
            $password = $input['password'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                exit;
            }

            $stmt = $pdo->prepare('SELECT id, password, salt, role, verified FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Email not found']);
                exit;
            }

            $hashedPassword = hash('sha512', $password . $user['salt']);
            if ($hashedPassword !== $user['password']) {
                http_response_code(401);
                echo json_encode(['error' => 'Incorrect password']);
                exit;
            }

            if (!$user['verified']) {
                http_response_code(301);
                echo json_encode([
                    'error' => 'Account not verified',
                    'action_required' => 'verify',
                    'email' => $email
                ]);
                exit;
            }

            $sessionToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', time() + 24 * 60 * 60);
            $lastLogin = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare('UPDATE users SET session_token = ?, token_expiry = ?, last_login = ? WHERE id = ?');
            $stmt->execute([$sessionToken, $tokenExpiry, $lastLogin, $user['id']]);

            http_response_code(200);
            echo json_encode([
                'message' => 'Login successful',
                'role' => $user['role'],
                'session_token' => $sessionToken,
                'token_expiry' => $tokenExpiry
            ]);
            exit;
        }

        if ($action === 'verify') {
            if (!isset($input['email']) || !isset($input['code'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing email or verification code']);
                exit;
            }

            $email = trim($input['email']);
            $code = trim($input['code']);

            $stmt = $pdo->prepare('SELECT id, verification_code, code_expiry FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Email not found']);
                exit;
            }

            if (strtotime($user['code_expiry']) < time()) {
                http_response_code(400);
                echo json_encode(['error' => 'Verification code expired', 'action_required' => 'resend']);
                exit;
            }

            if ($user['verification_code'] !== $code) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid verification code']);
                exit;
            }

            $sessionToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', time() + 24 * 60 * 60);
            $lastLogin = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare('UPDATE users SET verified = 1, session_token = ?, token_expiry = ?, last_login = ?, verification_code = NULL, code_expiry = NULL WHERE id = ?');
            $stmt->execute([$sessionToken, $tokenExpiry, $lastLogin, $user['id']]);

            http_response_code(200);
            echo json_encode([
                'message' => 'Verification successful, logged in',
                'session_token' => $sessionToken,
                'token_expiry' => $tokenExpiry
            ]);
            exit;
        }

        if ($action === 'resend') {
            if (!isset($input['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing email']);
                exit;
            }

            $email = trim($input['email']);
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(401);
                echo json_encode(['error' => 'Email not found']);
                exit;
            }

            $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
            $codeExpiry = date('Y-m-d H:i:s', time() + 10 * 60);

            $stmt = $pdo->prepare('UPDATE users SET verification_code = ?, code_expiry = ? WHERE email = ?');
            $stmt->execute([$verificationCode, $codeExpiry, $email]);

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
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Kode Verifikasi Akun Anda';
                $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 500px; margin: auto; border: 1px solid #eee; padding: 20px; border-radius: 10px; background-color: #f9f9f9;">
                    <h2 style="color:rgb(76, 111, 175); text-align: center;">Kode Verifikasi</h2>
                    <p>Halo,</p>
                    <p>Berikut adalah kode verifikasi untuk akun Anda:</p>
                    <div style="text-align: center; margin: 20px 0;">
                        <span style="display: inline-block; font-size: 28px; font-weight: bold; background: rgb(76, 111, 175); color: white; padding: 10px 20px; border-radius: 5px;">' . $verificationCode . '</span>
                    </div>
                    <p>Kode ini berlaku selama <strong>10 menit</strong>.</p>
                    <p>Jika Anda tidak merasa meminta kode ini, silakan abaikan email ini.</p>
                    <p style="margin-top: 30px; font-size: 12px; color: #888;">Email ini dikirim secara otomatis. Mohon untuk tidak membalas.</p>
                </div>';

                $mail->send();
                http_response_code(200);
                echo json_encode(['message' => 'Verification code resent to email']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to send verification email: ' . $mail->ErrorInfo]);
                exit;
            }
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
?>