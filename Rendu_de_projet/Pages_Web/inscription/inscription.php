<?php
// Démarrer la session
session_start();

// Activer l'affichage des erreurs pour le débogage (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration de la base de données
$host = 'localhost';
$dbname = 'lsd_project';
$user = 'root';
$pass = '';

// Mode débogage (à mettre à false en production)
$debug_mode = true;

// Fonction pour journaliser les événements
function logEvent($message) {
    global $debug_mode;
    if ($debug_mode) {
        error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'inscription_log.txt');
    }
}

// Fonction pour afficher les messages
function showMessage($message, $type = 'success') {
    return '<div class="message ' . $type . '">' . htmlspecialchars($message) . '</div>';
}

// Fonction pour rediriger avec des erreurs
function redirectWithErrors($errors, $submitted = []) {
    $errorParam = urlencode(json_encode($errors));
    $submittedParam = urlencode(json_encode($submitted));
    header("Location: inscription.html?errors={$errorParam}&submitted={$submittedParam}");
    exit;
}

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logEvent("Connexion à la base de données réussie");
    
    // Traitement du formulaire d'inscription
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        logEvent("Formulaire soumis");
        
        // Récupération des données du formulaire
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $entreprise = $_POST['entreprise'] ?? null;
        $siret = $_POST['siret'] ?? null;
        $code_naf = $_POST['code_naf'] ?? null;
        $email = $_POST['email'] ?? '';
        $mdp = $_POST['mdp'] ?? '';
        $metier = $_POST['metier'] ?? null;
        $telephone = $_POST['telephone'] ?? null;
        $adresse = $_POST['adresse'] ?? '';
        $loc_x = $_POST['loc_x'] ?? null;
        $loc_y = $_POST['loc_y'] ?? null;
        
        // Construire un tableau des valeurs soumises pour les renvoyer en cas d'erreur
        $submitted = [
            'nom' => $nom,
            'prenom' => $prenom,
            'entreprise' => $entreprise,
            'siret' => $siret,
            'code_naf' => $code_naf,
            'email' => $email,
            'metier' => $metier,
            'telephone' => $telephone,
            'adresse' => $adresse
        ];
        
        // Validation côté serveur
        $errors = [];
        if (empty($nom)) $errors['nom'] = "Le nom est requis.";
        if (empty($prenom)) $errors['prenom'] = "Le prénom est requis.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "L'email est invalide.";
        if (empty($mdp) || strlen($mdp) < 8) $errors['mdp'] = "Le mot de passe doit contenir au moins 8 caractères.";
        if (empty($adresse)) $errors['adresse'] = "L'adresse est requise.";
        if (empty($loc_x) || empty($loc_y)) $errors['adresse'] = "La géolocalisation de l'adresse a échoué.";
        
        if ($siret && !preg_match('/^[0-9]{14}$/', $siret)) $errors['siret'] = "Le SIRET doit contenir 14 chiffres.";
        if ($code_naf && !preg_match('/^[0-9]{4}[A-Z]$/', $code_naf)) $errors['code_naf'] = "Le code NAF doit être au format 0000A.";
        
        // Vérifier si l'email existe déjà
        $checkEmail = $pdo->prepare("SELECT COUNT(*) FROM user WHERE adresse_mail = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetchColumn() > 0) {
            $errors['email'] = "Cette adresse email est déjà utilisée.";
        }
        
        // Rediriger vers le formulaire avec les erreurs s'il y en a
        if (!empty($errors)) {
            redirectWithErrors($errors, $submitted);
        }
        
        // Si nous arrivons ici, c'est que la validation a réussi
        
        // Hachage du mot de passe
        $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
        
        // Vérifier si la colonne adresse existe dans la table user
        $checkColumn = $pdo->query("SHOW COLUMNS FROM user LIKE 'adresse'");
        if ($checkColumn->rowCount() === 0) {
            // Ajouter la colonne adresse si elle n'existe pas
            $pdo->exec("ALTER TABLE user ADD COLUMN adresse VARCHAR(255) NULL AFTER code_naf");
            logEvent("Colonne 'adresse' ajoutée à la table user");
        }
        
        // Vérifier si la colonne telephone existe dans la table user
        $checkColumn = $pdo->query("SHOW COLUMNS FROM user LIKE 'telephone'");
        if ($checkColumn->rowCount() === 0) {
            // Ajouter la colonne telephone si elle n'existe pas
            $pdo->exec("ALTER TABLE user ADD COLUMN telephone VARCHAR(20) NULL AFTER metier");
            logEvent("Colonne 'telephone' ajoutée à la table user");
        }
        
        // Insertion dans la base de données
        $sql = "INSERT INTO user (nom, prenom, entreprise, siret, code_naf, adresse, adresse_mail, mdp, metier, telephone, loc_x, loc_y) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            $nom, 
            $prenom, 
            $entreprise, 
            $siret, 
            $code_naf,
            $adresse,
            $email, 
            $mdp_hash, 
            $metier,
            $telephone,
            $loc_x, 
            $loc_y
        ]);
        
        // Récupération de l'ID généré
        $userId = $pdo->lastInsertId();
        logEvent("Insertion réussie, ID généré: $userId");
        
        // Rediriger vers la page de connexion
        header("Location: connexion.html?success=1");
        exit;
    }
    
    // Si on arrive ici sans POST, rediriger vers le formulaire
    header("Location: inscription.html");
    exit;
    
} catch (PDOException $e) {
    logEvent("Erreur PDO: " . $e->getMessage());
    
    // Rediriger avec une erreur générale
    $errors = ['general' => "Une erreur est survenue lors de la connexion à la base de données: " . $e->getMessage()];
    redirectWithErrors($errors);
}
?>
