<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// ✅ Configuration des erreurs (à désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Headers en premier
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ✅ Buffer pour éviter les outputs parasites
ob_start();

try {
    // Test 1: Vérifier l'autoload
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception("Autoload introuvable");
    }
    require $autoloadPath;

    // Test 2: Vérifier le fichier .env
    $envPath = __DIR__ . '/..';
    if (!file_exists($envPath . '/.env')) {
        throw new Exception("Fichier .env introuvable");
    }
    
    // 1️⃣ Charger Dotenv si fichier .env présent (local)
    $dotenvPath = __DIR__ . '/..';
    if (file_exists($dotenvPath . '/.env')) {
        $dotenv = Dotenv::createImmutable($dotenvPath);
        $dotenv->safeLoad();
    }

    // Test 3: Vérifier les variables d'environnement
    $requiredEnvVars = ['MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_PORT', 'MAIL_TO', 'MAIL_FROM_NAME'];
    foreach ($requiredEnvVars as $var) {
        if (empty($_ENV[$var])) {
            throw new Exception("Variable manquante: $var");
        }
    }

    // Test 4: Récupérer et valider les données
    $json = file_get_contents('php://input');
    if (empty($json)) {
        throw new Exception('Aucune donnée reçue');
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON invalide: ' . json_last_error_msg());
    }

    if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
        throw new Exception('Données manquantes (name, email ou message)');
    }

    // Nettoyer les données
    $name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(trim($data['message']), ENT_QUOTES, 'UTF-8');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email invalide');
    }

    // Configuration PHPMailer
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0;
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
    $mail->addReplyTo($email, $name); // ✅ Permet de répondre directement

    $mail->isHTML(false);
    $mail->Subject = "Message depuis le portfolio - $name";
    $mail->Body = "Nom: $name\nEmail: $email\n\nMessage:\n$message";

    // Envoi
    $mail->send();

    // ✅ Nettoie le buffer avant d'envoyer le JSON
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès !'
    ]);

} catch (Exception $e) {
    // ✅ Nettoie le buffer en cas d'erreur
    ob_end_clean();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
?>