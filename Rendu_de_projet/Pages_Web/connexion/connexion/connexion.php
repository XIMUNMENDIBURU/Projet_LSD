<?php
require_once 'config.php';
// Démarrer la session
session_start();

// Activer l'affichage des erreurs pour le débogage (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Mode débogage (à mettre à false en production)
$debug_mode = true;

// Fonction pour journaliser les événements
function logEvent($message) {
    global $debug_mode;
    if ($debug_mode) {
        error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'connexion_log.txt');
    }
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $email = $_POST['email'] ?? '';
    $mdp = $_POST['mdp'] ?? '';
    
    // Validation des données
    if (empty($email) || empty($mdp)) {
        header("Location: connexion.html?error=empty");
        exit;
    }
    
    try {
        // Connexion à la base de données
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        logEvent("Connexion à la base de données réussie");
        
        // Recherche de l'utilisateur par email
        $stmt = $pdo->prepare("SELECT * FROM user WHERE adresse_mail = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Vérification du mot de passe
        if ($user && password_verify($mdp, $user['mdp'])) {
            // Connexion réussie
            logEvent("Connexion réussie pour l'utilisateur ID: " . $user['id_user']);
            
            // Stocker les informations de l'utilisateur en session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_email'] = $user['adresse_mail'];
            $_SESSION['user_logged_in'] = true;
            
            // Rediriger vers le tableau de bord
            header("Location: dashboard.php");
            exit;
        } else {
            // Identifiants incorrects
            logEvent("Échec de connexion pour l'email: " . $email);
            header("Location: connexion.html?error=invalid");
            exit;
        }
    } catch (PDOException $e) {
        // Erreur de base de données
        logEvent("Erreur PDO: " . $e->getMessage());
        header("Location: connexion.html?error=db");
        exit;
    }
} else {
    // Si la page est accédée directement sans soumission de formulaire
    header("Location: connexion.html");
    exit;
}
?>
