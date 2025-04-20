<?php
require_once 'config.php';
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: connexion.html");
    exit;
}

// Récupérer l'ID de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'] ?? '';

// Si l'ID de l'utilisateur est vide, rediriger vers la page de connexion
if (empty($user_id)) {
    header("Location: connexion.html");
    exit;
}

$message = '';
$messageType = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Vérifier si l'utilisateur a des avis
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM avis WHERE id_user = ?");
    $stmt->execute([$user_id]);
    $hasReviews = ($stmt->fetchColumn() > 0);
    
    if ($hasReviews) {
        // Supprimer d'abord les avis de l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM avis WHERE id_user = ?");
        $stmt->execute([$user_id]);
    }
    
    // Supprimer l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM user WHERE id_user = ?");
    $stmt->execute([$user_id]);
    
    // Valider la transaction
    $pdo->commit();
    
    // Détruire la session
    session_destroy();
    
    // Rediriger vers la page d'accueil avec un message de succès
    header("Location: index.html?message=account_deleted");
    exit;
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Enregistrer l'erreur
    error_log("Erreur lors de la suppression du compte: " . $e->getMessage());
    
    // Rediriger vers la page de modification du profil avec un message d'erreur
    header("Location: modifier-profil.php?error=delete_failed");
    exit;
}
?>
