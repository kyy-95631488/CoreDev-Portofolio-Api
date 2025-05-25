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

        if (!isset($input['email']) || !isset($input['password']) || !isset($input['confirmPassword']) || !isset($input['agreeTerms'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }

        $email = trim($input['email']);
        $password = $input['password'];
        $confirmPassword = $input['confirmPassword'];
        $agreeTerms = filter_var($input['agreeTerms'], FILTER_VALIDATE_BOOLEAN);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            exit;
        }

        if ($password !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['error' => 'Passwords do not match']);
            exit;
        }

        if (!$agreeTerms) {
            http_response_code(400);
            echo json_encode(['error' => 'You must agree to the terms']);
            exit;
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters long']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already registered']);
            exit;
        }

        $salt = bin2hex(random_bytes(32));
        $hashedPassword = hash('sha512', $password . $salt);
        $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
        $codeExpiry = date('Y-m-d H:i:s', time() + 10 * 60);
        $role = 'user';
        $verified = 0;

        $stmt = $pdo->prepare('
            INSERT INTO users (
                email, password, role, salt, verification_code, code_expiry, verified, created_at,
                last_login, session_token, token_expiry, reset_token, reset_expiry
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NULL, NULL, NULL, NULL, NULL)
        ');
        $stmt->execute([$email, $hashedPassword, $role, $salt, $verificationCode, $codeExpiry, $verified]);

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
            http_response_code(201);
            echo json_encode([
                'message' => 'Registration successful, verification code sent to email',
                'role' => $role,
                'verified' => (bool)$verified,
                'verification_code' => $verificationCode
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send verification email: ' . $mail->ErrorInfo]);
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