<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Afficher les erreurs localement (désactiver en prod)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Headers JSON et CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Buffer pour éviter les sorties parasites
ob_start();
var_dump( $_ENV['MAIL_USERNAME']);

try {
    // ✅ Composer autoload
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) throw new Exception("Autoload not found");
    require $autoloadPath;

    // ✅ Charger .env si présent (local)
    $dotenvPath = __DIR__ . '/..';
    if (file_exists($dotenvPath . '/.env')) {
        $dotenv = Dotenv::createImmutable($dotenvPath);
        $dotenv->safeLoad();
    }

    // ✅ Vérifier variables d’environnement
    $requiredEnvVars = ['MAIL_HOST','MAIL_USERNAME','MAIL_PASSWORD','MAIL_PORT','MAIL_TO','MAIL_FROM_NAME'];
    foreach ($requiredEnvVars as $var) {
        if (empty($_ENV[$var])) throw new Exception("Missing env variable: $var");
    }

    // ✅ Lire et décoder JSON
    $json = file_get_contents('php://input');
    if (!$json) throw new Exception("No data received");
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("Invalid JSON: " . json_last_error_msg());

    // ✅ Vérifier champs
    foreach (['name','email','message'] as $field) {
        if (empty($data[$field])) throw new Exception("Missing field: $field");
    }

    // ✅ Nettoyage des données
    $name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(trim($data['message']), ENT_QUOTES, 'UTF-8');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid email");

    // ✅ PHPMailer
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0; // mettre 2 pour debug
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
        'message' => 'Message sent successfully!',
          'env' => [
            'MAIL_HOST' => $_ENV['MAIL_HOST'] ?? null,
            'MAIL_USERNAME' => $_ENV['MAIL_USERNAME'] ?? null
        ],
    ]);

} catch (Exception $e) {
    // Récupérer tout output inutile
    $output = ob_get_contents();
    ob_end_clean();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'env' => [
            'MAIL_HOST' => $_ENV['MAIL_HOST'] ?? 'null',
            'MAIL_USERNAME' => $_ENV['MAIL_USERNAME'] ?? 'null'
        ],
        'debug' => $output
    ]);
}

exit;
