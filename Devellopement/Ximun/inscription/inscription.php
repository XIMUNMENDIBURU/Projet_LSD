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

// Inclure le fichier CSS
echo '<link rel="stylesheet" href="style.css">';

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
        $loc_x = $_POST['loc_x'] ?? null;
        $loc_y = $_POST['loc_y'] ?? null;
        $adresse = $_POST['adresse'] ?? '';
        
        // Construire un tableau des valeurs soumises pour les renvoyer en cas d'erreur
        $submitted = [
            'nom' => $nom,
            'prenom' => $prenom,
            'entreprise' => $entreprise,
            'siret' => $siret,
            'code_naf' => $code_naf,
            'email' => $email,
            'metier' => $metier,
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
        
        // Insertion dans la base de données
        $sql = "INSERT INTO user (nom, prenom, entreprise, siret, code_naf, adresse_mail, mdp, metier, loc_x, loc_y) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            $nom, 
            $prenom, 
            $entreprise, 
            $siret, 
            $code_naf, 
            $email, 
            $mdp_hash, 
            $metier, 
            $loc_x, 
            $loc_y
        ]);
        
        // Récupération de l'ID généré
        $userId = $pdo->lastInsertId();
        logEvent("Insertion réussie, ID généré: $userId");
        
        if ($userId) {
            // Vérification que l'utilisateur a bien été créé
            $checkSql = "SELECT * FROM user WHERE id_user = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$userId]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<div class='message success'>";
                echo "<h3>✅ Inscription réussie!</h3>";
                echo "<p>L'utilisateur a bien été enregistré dans la base de données.</p>";
                echo "<p><strong>ID:</strong> " . $userId . "</p>";
                echo "<p><strong>Nom:</strong> " . htmlspecialchars($user['nom']) . "</p>";
                echo "<p><strong>Prénom:</strong> " . htmlspecialchars($user['prenom']) . "</p>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($user['adresse_mail']) . "</p>";
                echo "<p><strong>Adresse:</strong> " . htmlspecialchars($adresse) . "</p>";
                echo "</div>";
                
                if ($debug_mode) {
                    echo "<div class='debug-info'>";
                    echo "<h3>Informations de débogage</h3>";
                    echo "<pre>";
                    print_r($user);
                    echo "</pre>";
                    echo "</div>";
                }
            } else {
                echo "<div class='message warning'>";
                echo "<h3>⚠️ Anomalie détectée</h3>";
                echo "<p>L'insertion a généré un ID ($userId) mais l'utilisateur n'a pas été trouvé lors de la vérification.</p>";
                echo "</div>";
                logEvent("Anomalie: ID généré mais utilisateur non trouvé: $userId");
            }
        } else {
            echo "<div class='message error'>";
            echo "<h3>❌ Échec de l'inscription</h3>";
            echo "<p>Aucun ID n'a été généré. L'insertion a échoué.</p>";
            echo "</div>";
            logEvent("Échec de l'insertion: Aucun ID généré");
        }
    }
    
    // Affichage de la liste des utilisateurs
    $usersSql = "SELECT id_user, nom, prenom, adresse_mail FROM user ORDER BY id_user DESC";
    $usersStmt = $pdo->query($usersSql);
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Liste des utilisateurs enregistrés</h2>";
    
    if (count($users) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id_user']) . "</td>";
            echo "<td>" . htmlspecialchars($user['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($user['prenom']) . "</td>";
            echo "<td>" . htmlspecialchars($user['adresse_mail']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucun utilisateur trouvé dans la base de données.</p>";
    }
    
    // Bouton pour retourner au formulaire
    echo "<a href='inscription.html' class='btn'>Retour au formulaire d'inscription</a>";
    
} catch (PDOException $e) {
    echo "<div class='message error'>";
    echo "<h3>❌ Erreur de base de données</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    logEvent("Erreur PDO: " . $e->getMessage());
    
    if ($debug_mode) {
        echo "<div class='debug-info'>";
        echo "<h3>Détails de l'erreur</h3>";
        echo "<pre>";
        print_r($e);
        echo "</pre>";
        echo "</div>";
    }
    
    // Bouton pour retourner au formulaire même en cas d'erreur
    echo "<a href='inscription.html' class='btn'>Retour au formulaire d'inscription</a>";
}

// Vérification de la structure de la table (en mode débogage uniquement)
if ($debug_mode && isset($pdo)) {
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'user'");
        $tableExists = $tableCheck->rowCount() > 0;
        
        if (!$tableExists) {
            echo "<div class='debug-info'>";
            echo "<h3>⚠️ Table manquante</h3>";
            echo "<p>La table 'user' n'existe pas dans la base de données. Voici le SQL pour la créer :</p>";
            echo "<pre>
CREATE TABLE `user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `entreprise` varchar(255) DEFAULT NULL,
  `siret` varchar(14) DEFAULT NULL,
  `code_naf` varchar(5) DEFAULT NULL,
  `adresse_mail` varchar(255) NOT NULL,
  `mdp` varchar(255) NOT NULL,
  `metier` varchar(255) DEFAULT NULL,
  `loc_x` float DEFAULT NULL,
  `loc_y` float DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `adresse_mail` (`adresse_mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            </pre>";
            echo "</div>";
        } else {
            // Vérifier la structure de la table
            $columns = $pdo->query("SHOW COLUMNS FROM user")->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['id_user', 'nom', 'prenom', 'adresse_mail', 'mdp', 'loc_x', 'loc_y'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (!empty($missingColumns)) {
                echo "<div class='debug-info'>";
                echo "<h3>⚠️ Colonnes manquantes</h3>";
                echo "<p>Les colonnes suivantes sont manquantes dans la table 'user' :</p>";
                echo "<ul>";
                foreach ($missingColumns as $column) {
                    echo "<li>" . htmlspecialchars($column) . "</li>";
                }
                echo "</ul>";
                echo "</div>";
            }
        }
    } catch (PDOException $e) {
        // Ne rien faire, l'erreur principale a déjà été affichée
    }
}
?>