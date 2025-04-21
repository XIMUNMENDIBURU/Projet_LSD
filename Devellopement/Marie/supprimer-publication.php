<?php
// Démarrer la session
session_start();

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lsd_project";

$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Récupérer l'ID de la publication
$id_publication = isset($_GET['id']) ? $_GET['id'] : 0;

// Vérifier si l'ID est valide
if ($id_publication > 0) {
    // Supprimer d'abord les images associées à la publication
    $sql_delete_images = "DELETE FROM image WHERE id_publication = $id_publication";
    $conn->query($sql_delete_images);
    
    // Supprimer la publication
    $sql_delete_publication = "DELETE FROM publication WHERE id_publication = $id_publication";
    
    if ($conn->query($sql_delete_publication) === TRUE) {
        // Redirection vers la page portfolio avec un message de succès
        header("Location: portfolio.php?message=success");
        exit;
    } else {
        // Redirection vers la page portfolio avec un message d'erreur
        header("Location: portfolio.php?message=error");
        exit;
    }
} else {
    // Redirection vers la page portfolio avec un message d'erreur
    header("Location: portfolio.php?message=error");
    exit;
}
?>
