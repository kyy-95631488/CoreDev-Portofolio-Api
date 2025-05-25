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

        if ($action === 'request_reset') {
            if (!isset($input['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing email']);
                exit;
            }

            $email = trim($input['email']);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                exit;
            }

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Email not found']);
                exit;
            }

            $resetCode = sprintf("%06d", mt_rand(100000, 999999));
            $codeExpiry = date('Y-m-d H:i:s', time() + 10 * 60);

            $stmt = $pdo->prepare('UPDATE users SET reset_code = ?, reset_code_expiry = ? WHERE email = ?');
            $stmt->execute([$resetCode, $codeExpiry, $email]);

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
                $mail->Subject = 'Password Reset Code';
                $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 500px; margin: auto; border: 1px solid #eee; padding: 20px; border-radius: 10px; background-color: #f9f9f9;">
                    <h2 style="color:rgb(76, 111, 175); text-align: center;">Password Reset Code</h2>
                    <p>Hello,</p>
                    <p>Here is your password reset code:</p>
                    <div style="text-align: center; margin: 20px 0;">
                        <span style="display: inline-block; font-size: 28px; font-weight: bold; background: rgb(76, 111, 175); color: white; padding: 10px 20px; border-radius: 5px;">' . $resetCode . '</span>
                    </div>
                    <p>This code is valid for <strong>10 minutes</strong>.</p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                    <p style="margin-top: 30px; font-size: 12px; color: #888;">This email was sent automatically. Please do not reply.</p>
                </div>';

                $mail->send();
                http_response_code(200);
                echo json_encode(['message' => 'Password reset code sent to email']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to send reset code email: ' . $mail->ErrorInfo]);
                exit;
            }
            exit;
        }

        if ($action === 'reset_password') {
            if (!isset($input['email']) || !isset($input['code']) || !isset($input['new_password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing email, code, or new password']);
                exit;
            }

            $email = trim($input['email']);
            $code = trim($input['code']);
            $newPassword = $input['new_password'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                exit;
            }

            if (strlen($newPassword) < 8) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 8 characters long']);
                exit;
            }

            $stmt = $pdo->prepare('SELECT id, reset_code, reset_code_expiry, salt FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Email not found']);
                exit;
            }

            if (strtotime($user['reset_code_expiry']) < time()) {
                http_response_code(400);
                echo json_encode(['error' => 'Reset code expired']);
                exit;
            }

            if ($user['reset_code'] !== $code) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid reset code']);
                exit;
            }

            $hashedPassword = hash('sha512', $newPassword . $user['salt']);

            $stmt = $pdo->prepare('UPDATE users SET password = ?, reset_code = NULL, reset_code_expiry = NULL WHERE email = ?');
            $stmt->execute([$hashedPassword, $email]);

            http_response_code(200);
            echo json_encode(['message' => 'Password reset successfully']);
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