<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// ✅ Config erreurs locales (désactiver en prod)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Headers JSON et CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ✅ Buffer pour éviter les sorties parasites
ob_start();

try {
    // 1️⃣ Autoload
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception("Autoload introuvable: $autoloadPath");
    }
    require $autoloadPath;

    // 2️⃣ Charger .env si présent (local)
    $dotenvPath = __DIR__ . '/..';
    if (file_exists($dotenvPath . '/.env')) {
        $dotenv = Dotenv::createImmutable($dotenvPath);
        $dotenv->safeLoad();
    }

    // 3️⃣ Vérifier les variables d'environnement nécessaires
    $requiredEnvVars = ['MAIL_HOST','MAIL_USERNAME','MAIL_PASSWORD','MAIL_PORT','MAIL_TO','MAIL_FROM_NAME'];
    foreach ($requiredEnvVars as $var) {
        if (empty($_ENV[$var])) {
            throw new Exception("Missing env variable: $var");
        }
    }

    // 4️⃣ Récupérer et valider le JSON du fetch
    $json = file_get_contents('php://input');
    if (!$json) throw new Exception("No data received");

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON: " . json_last_error_msg());
    }

    // 5️⃣ Vérification des champs
    if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
        throw new Exception("Missing required fields: name, email, or message");
    }

    // 6️⃣ Nettoyage des données
    $name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(trim($data['message']), ENT_QUOTES, 'UTF-8');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email");
    }

    // 7️⃣ PHPMailer
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0; // 2 pour debug
    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'];
    $mail->Password = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int)$_ENV['MAIL_PORT'];
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($_ENV['MAIL_TO']);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(false);
    $mail->Subject = "Message depuis le portfolio - $name";
    $mail->Body = "Nom: $name\nEmail: $email\n\nMessage:\n$message";

    $mail->send();

    // ✅ Réponse JSON
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès !'
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
