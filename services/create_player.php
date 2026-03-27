<?php
if (!session_status()) session_start();
require_once 'dbConnection.php';
require_once '../vendor/phpmailer/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

try {
    $fields = [
        'fedRegNumber','trncId','fName','lName','fatherName','motherName','birthPlace','birthDate','phoneNo','address','passNo','resPermitNo','username','email','appId'
    ];
    $in = [];
    foreach ($fields as $f) { $in[$f] = isset($_POST[$f]) ? trim((string)$_POST[$f]) : ''; }
    if ($in['fName'] === '' || $in['lName'] === '' || $in['username'] === '' || $in['email'] === '') {
        echo json_encode(['success'=>false,'message'=>'Missing required fields']);
        exit();
    }
    $plr_idNum = $in['fedRegNumber'] . $in['trncId'] . $in['passNo'] . $in['resPermitNo'];
    if ($plr_idNum === '') { echo json_encode(['success'=>false,'message'=>'Invalid ID fields']); exit(); }

    $conn->begin_transaction();

    $rawPassword = bin2hex(random_bytes(10));
    $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);
    $role = 'player';

    $u = $conn->prepare("INSERT INTO users (user_name, email, password, user_role) VALUES (?, ?, ?, ?)");
    $u->bind_param('ssss', $in['username'], $in['email'], $hashedPassword, $role);
    if (!$u->execute()) { throw new Exception('Failed to create user'); }
    $userId = (int)$conn->insert_id; $u->close();

    $p = $conn->prepare("INSERT INTO players (plr_idNum, plr_name, plr_surname, plr_address, plr_dob, plr_mother, plr_father, plr_pob, plr_phone, plr_username, plr_app, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $plr_name = $in['fName']; $plr_surname = $in['lName'];
    $plr_address = $in['address']; $plr_dob = $in['birthDate']; $plr_mother = $in['motherName']; $plr_father = $in['fatherName'];
    $plr_pob = $in['birthPlace']; $plr_phone = $in['phoneNo']; $plr_username = $in['username'];
    $plr_app = ($in['appId'] !== '' ? (int)$in['appId'] : null);
    $p->bind_param('ssssssssssii', $plr_idNum, $plr_name, $plr_surname, $plr_address, $plr_dob, $plr_mother, $plr_father, $plr_pob, $plr_phone, $plr_username, $plr_app, $userId);
    if (!$p->execute()) { throw new Exception('Failed to create player'); }
    $p->close();

    if ($plr_app) {
        $app = $conn->prepare("UPDATE applications SET isApproved = 1 WHERE app_id = ?");
        $app->bind_param('i', $plr_app);
        $app->execute();
        $app->close();
    }

    $conn->commit();

    // Best-effort email with credentials
    try {
        $cls = '\\PHPMailer\\PHPMailer\\PHPMailer';
        if (class_exists($cls)) {
            $mail = new $cls(true);
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST') ?: 'localhost';
            $mail->SMTPAuth = (bool)(getenv('SMTP_AUTH') ?: false);
            if ($mail->SMTPAuth) {
                $mail->Username = getenv('SMTP_USER') ?: '';
                $mail->Password = getenv('SMTP_PASS') ?: '';
            }
            $secure = getenv('SMTP_SECURE') ?: '';
            if ($secure) { $mail->SMTPSecure = $secure; }
            $mail->Port = getenv('SMTP_PORT') ? (int)getenv('SMTP_PORT') : 25;
            $mail->setFrom(getenv('SMTP_FROM') ?: 'noreply@example.com', 'Famagusta Dart Club');
            $mail->addAddress($in['email']);
            $mail->isHTML(true);
            $mail->Subject = 'Your player account credentials';
            $mail->Body = "Hello {$plr_name} {$plr_surname},<br/>Your account has been created.<br/>Username: {$in['username']}<br/>Temporary password: {$rawPassword}";
            $mail->AltBody = "Hello {$plr_name} {$plr_surname},\nYour account has been created.\nUsername: {$in['username']}\nTemporary password: {$rawPassword}";
            $mail->send();
        }
    } catch (\Exception $e) {
        // ignore
    }

    echo json_encode(['success'=>true,'message'=>'Player and user created','user_id'=>$userId]);
} catch (\Exception $e) {
    if ($conn) { $conn->rollback(); }
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

?>
