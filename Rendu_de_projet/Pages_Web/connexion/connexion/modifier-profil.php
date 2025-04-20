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
$message = '';
$messageType = '';

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Récupérer les données du formulaire
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $entreprise = $_POST['entreprise'] ?? '';
        $siret = $_POST['siret'] ?? '';
        $code_naf = $_POST['code_naf'] ?? '';
        $email = $_POST['email'] ?? '';
        $metier = $_POST['metier'] ?? '';
        $telephone = $_POST['telephone'] ?? '';
        $adresse = $_POST['adresse'] ?? '';
        
        // Vérifier si le mot de passe a été fourni
        $password = $_POST['password'] ?? '';
        $passwordUpdate = '';
        
        if (!empty($password)) {
            // Hacher le nouveau mot de passe
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $passwordUpdate = ", mdp = :password";
        }
        
        // Préparer la requête SQL
        $sql = "UPDATE user SET 
                nom = :nom, 
                prenom = :prenom, 
                entreprise = :entreprise, 
                siret = :siret, 
                code_naf = :code_naf, 
                adresse_mail = :email, 
                metier = :metier,
                telephone = :telephone,
                adresse = :adresse" . $passwordUpdate . " 
                WHERE id_user = :user_id";
        
        $stmt = $pdo->prepare($sql);
        
        // Lier les paramètres
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':entreprise', $entreprise);
        $stmt->bindParam(':siret', $siret);
        $stmt->bindParam(':code_naf', $code_naf);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':metier', $metier);
        $stmt->bindParam(':telephone', $telephone);
        $stmt->bindParam(':adresse', $adresse);
        $stmt->bindParam(':user_id', $user_id);
        
        if (!empty($password)) {
            $stmt->bindParam(':password', $passwordHash);
        }
        
        // Exécuter la requête
        $stmt->execute();
        
        $message = "Profil mis à jour avec succès !";
        $messageType = "success";
        
        // Mettre à jour les informations de session
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_email'] = $email;
        
    } catch (PDOException $e) {
        $message = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
        $messageType = "error";
    }
}

// Récupérer les informations actuelles de l'utilisateur
$userData = [];
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
    $stmt->execute([$user_id]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des informations : " . $e->getMessage();
    $messageType = "error";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Modifier son profil - LSD.com</title>
    <style>
        /* Réinitialisation des styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
        }
        
        /* Conteneur principal */
        .page-container {
            width: 100%;
            max-width: 100%;
            height: 100vh;
            margin: 0;
            background-color: white;
            position: relative;
            overflow: auto;
            display: flex;
            flex-direction: column;
        }
        
        /* En-tête */
        .site-header {
            background-color: #808080;
            padding: 15px;
            text-align: center;
            position: relative;
            flex-shrink: 0;
        }
        
        .logo {
            color: black;
            font-size: 28px;
            margin: 0;
            font-weight: bold;
        }
        
        /* Bouton de retour */
        .back-link {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: black;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .back-link svg {
            width: 24px;
            height: 24px;
            margin-right: 5px;
        }
        
        /* Formulaire */
        .form-container {
            padding: 20px;
            flex-grow: 1;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }
        
        .form-content {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-field {
            width: 100%;
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-field label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-field input {
            width: 100%;
            padding: 8px 0;
            border: none;
            border-bottom: 1px solid #000;
            font-size: 16px;
            background-color: transparent;
            outline: none;
        }
        
        .form-field input:focus {
            border-bottom: 2px solid #000;
        }
        
        /* Message de notification */
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            width: 100%;
            text-align: center;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Boutons */
        .form-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            margin-top: 30px;
        }
        
        .validate-btn {
            padding: 10px 30px;
            background-color: white;
            border: 1px solid black;
            border-radius: 20px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .delete-btn {
            padding: 10px 20px;
            background-color: #f8d7da;
            border: 1px solid black;
            border-radius: 20px;
            cursor: pointer;
            font-size: 16px;
            color: black;
            width: 100%;
            max-width: 250px;
            text-align: center;
        }
        
        /* Popup de confirmation */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .popup-container {
            background-color: white;
            padding: 20px;
            border: 1px solid black;
            width: 90%;
            max-width: 400px;
            position: relative;
        }
        
        .popup-header {
            background-color: #808080;
            padding: 15px;
            text-align: center;
            margin: -20px -20px 20px -20px;
        }
        
        .popup-delete-btn {
            padding: 10px 20px;
            background-color: #f8d7da;
            border: 1px solid black;
            border-radius: 20px;
            cursor: pointer;
            font-size: 16px;
            color: black;
            margin: 0 auto;
            display: block;
        }
        
        .popup-content {
            margin: 20px 0;
            text-align: center;
        }
        
        .popup-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .popup-btn {
            padding: 5px 20px;
            background-color: white;
            border: 1px solid black;
            cursor: pointer;
        }
        
        /* Icône d'aide */
        .help-icon {
            position: fixed;
            bottom: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid black;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            background-color: white;
        }
        
        /* Media queries pour le responsive design */
        @media (max-width: 768px) {
            .form-container {
                padding: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .popup-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .popup-btn {
                width: 100%;
            }
            
            .back-link span {
                display: none;
            }
        }
        
        @media (max-height: 600px) {
            .form-container {
                padding: 10px;
            }
            
            .form-field {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <header class="site-header">
            <a href="dashboard.php" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                <span>Retour</span>
            </a>
            <h1 class="logo">LSD.com</h1>
        </header>
        
        <div class="form-container">
            <div class="form-content">
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form id="profileForm" action="modifier-profil.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    
                    <div class="form-field">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($userData['nom'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($userData['prenom'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="entreprise">Entreprise</label>
                        <input type="text" id="entreprise" name="entreprise" value="<?php echo htmlspecialchars($userData['entreprise'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-field">
                        <label for="siret">Siret</label>
                        <input type="text" id="siret" name="siret" value="<?php echo htmlspecialchars($userData['siret'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-field">
                        <label for="code_naf">code NAF</label>
                        <input type="text" id="code_naf" name="code_naf" value="<?php echo htmlspecialchars($userData['code_naf'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-field">
                        <label for="email">Adresse mail</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['adresse_mail'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="telephone">Numéro de téléphone</label>
                        <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($userData['telephone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-field">
                        <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($userData['adresse'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-field">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" autocomplete="new-password">
                    </div>
                    
                    <div class="form-field">
                        <label for="metier">Métier</label>
                        <input type="text" id="metier" name="metier" value="<?php echo htmlspecialchars($userData['metier'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="validate-btn">Valider</button>
                        <button type="button" id="deleteAccountBtn" class="delete-btn">Supprimer son compte</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="help-icon">?</div>
    </div>
    
    <!-- Popup de confirmation de suppression -->
    <div id="deleteConfirmPopup" class="popup-overlay">
        <div class="popup-container">
            <div class="popup-header">
                <h2 class="logo">LSD.com</h2>
            </div>
            
            <button class="popup-delete-btn">Supprimer son compte</button>
            
            <div class="popup-content">
                <p>Êtes-vous certain de supprimer votre compte ?</p>
            </div>
            
            <div class="popup-actions">
                <button id="confirmDeleteBtn" class="popup-btn">Oui</button>
                <button id="cancelDeleteBtn" class="popup-btn">Non</button>
            </div>
        </div>
    </div>
    
    <script>
        // Afficher la popup de confirmation
        document.getElementById('deleteAccountBtn').addEventListener('click', function() {
            document.getElementById('deleteConfirmPopup').style.display = 'flex';
        });
        
        // Fermer la popup si l'utilisateur clique sur "Non"
        document.getElementById('cancelDeleteBtn').addEventListener('click', function() {
            document.getElementById('deleteConfirmPopup').style.display = 'none';
        });
        
        // Confirmer la suppression si l'utilisateur clique sur "Oui"
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            // Rediriger vers le script de suppression
            window.location.href = 'supprimer-compte.php';
        });
        
        // Afficher l'aide
        document.querySelector('.help-icon').addEventListener('click', function() {
            alert('Aide pour la modification du profil:\n\n' +
                  '- Modifiez vos informations personnelles\n' +
                  '- Laissez le champ mot de passe vide si vous ne souhaitez pas le changer\n' +
                  '- Cliquez sur "Valider" pour enregistrer vos modifications\n' +
                  '- Cliquez sur "Supprimer son compte" pour supprimer définitivement votre compte');
        });
    </script>
</body>
</html>
