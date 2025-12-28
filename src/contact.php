<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Ajoute ça pour CORS

// ❌ RETIRE CES LIGNES QUI ENVOIENT DU JSON TROP TÔT
// echo json_encode([
//     'received' => $data,
//     'env_test' => $_ENV['MAIL_HOST'] ?? null
// ]);

$data = json_decode(file_get_contents('php://input'), true);

// Validation des données
if (!$data || !isset($data['name'], $data['email'], $data['message'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$name = htmlspecialchars($data['name']);
$email = htmlspecialchars($data['email']);
$message = htmlspecialchars($data['message']);

$subject = "Message depuis portfolio";
$body = "Nom: $name\nEmail: $email\nMessage:\n$message";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->SMTPDebug = 0; // ✅ Mets à 0 pour désactiver les messages de debug
    $mail->Host       = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = $_ENV['MAIL_PORT'];

    $mail->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($_ENV['MAIL_TO']);

    $mail->isHTML(false); // ✅ Mets false si ton body est en texte brut
    $mail->Subject = $subject;
    $mail->Body    = $body;

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Message envoyé avec succès !']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $mail->ErrorInfo]);
}
?>