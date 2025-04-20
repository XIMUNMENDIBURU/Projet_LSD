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
        error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'artisan_log.txt');
    }
}

// Récupération de l'ID de l'artisan
$artisan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($artisan_id <= 0) {
    header('Location: recherche.html');
    exit;
}

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupération des informations de l'artisan
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
    $stmt->execute([$artisan_id]);
    $artisan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$artisan) {
        header('Location: recherche.html');
        exit;
    }
    
    // Génération d'une adresse fictive si non disponible
    if (empty($artisan['adresse'])) {
        $adresse = '';
        if (!empty($artisan['entreprise'])) {
            $adresse .= $artisan['entreprise'] . ', ';
        }
        $adresse .= rand(1, 50) . ' ' . ['Rue', 'Avenue', 'Boulevard', 'Allée'][rand(0, 3)] . ' ';
        $adresse .= ['des Lilas', 'de la Paix', 'Victor Hugo', 'de l\'Uhabia', 'des Roses'][rand(0, 4)] . ', ';
        $adresse .= '64210 Bidart';
        
        $artisan['adresse'] = $adresse;
    }
    
    // Vérifier si la note existe, sinon générer une note aléatoire
    if (!isset($artisan['note']) || $artisan['note'] == 0) {
        $artisan['note'] = round(rand(30, 50) / 10, 1); // Note entre 3.0 et 5.0
        
        // Mettre à jour la note dans la base de données
        try {
            $updateStmt = $pdo->prepare("UPDATE user SET note = ? WHERE id_user = ?");
            $updateStmt->execute([$artisan['note'], $artisan_id]);
            logEvent("Note générée pour l'artisan ID {$artisan_id}: {$artisan['note']}");
        } catch (PDOException $e) {
            logEvent("Erreur lors de la mise à jour de la note: " . $e->getMessage());
        }
    }
    
} catch (PDOException $e) {
    logEvent("Erreur PDO: " . $e->getMessage());
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSD.com - Profil de <?php echo htmlspecialchars($artisan['prenom'] . ' ' . $artisan['nom']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        header {
            background-color: #808080;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #000;
            text-decoration: none;
        }
        
        .login-link {
            background-color: white;
            padding: 5px 15px;
            border-radius: 20px;
            text-decoration: none;
            color: #000;
            border: 1px solid #000;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .artisan-header {
            display: flex;
            margin-bottom: 20px;
        }
        
        .artisan-avatar {
            width: 100px;
            height: 100px;
            background-color: #e0e0e0;
            border-radius: 50%;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #555;
        }
        
        .artisan-info h1 {
            margin: 0 0 10px 0;
        }
        
        .artisan-info p {
            margin: 5px 0;
            color: #555;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            color: #333;
        }
        
        .map-container {
            height: 300px;
            margin-top: 20px;
        }
        
        .contact-button {
            display: inline-block;
            background-color: #4285f4;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
        }
        
        .back-button {
            display: inline-block;
            background-color: #f0f0f0;
            color: #333;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            margin-right: 10px;
        }
        
        .rating {
            margin: 10px 0;
            font-size: 18px;
        }
        
        .rating .star {
            color: #FFD700;
        }
        
        .rating .empty-star {
            color: #ccc;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.html" class="logo">LSD.com</a>
        <a href="connexion.html" class="login-link">Se connecter en tant qu'artisan</a>
    </header>
    
    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="message error">
                <h3>❌ Erreur</h3>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <a href="recherche.html" class="back-button">Retour à la recherche</a>
            </div>
        <?php else: ?>
            <div class="artisan-header">
                <div class="artisan-avatar">
                    <?php echo strtoupper(substr($artisan['prenom'], 0, 1)); ?>
                </div>
                <div class="artisan-info">
                    <h1><?php echo htmlspecialchars(($artisan['civilite'] ?? '') . ' ' . $artisan['prenom'] . ' ' . $artisan['nom']); ?></h1>
                    <p><strong><?php echo htmlspecialchars($artisan['metier']); ?></strong></p>
                    <p><?php echo htmlspecialchars($artisan['adresse']); ?></p>
                    
                    <div class="rating">
                        <?php 
                        $note = (float)$artisan['note'];
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= floor($note)) {
                                echo '<span class="star">★</span>'; // Étoile pleine
                            } elseif ($i - 0.5 <= $note) {
                                echo '<span class="star">✬</span>'; // Étoile à moitié pleine
                            } else {
                                echo '<span class="empty-star">☆</span>'; // Étoile vide
                            }
                        }
                        echo ' <span>(' . number_format($note, 1) . '/5)</span>';
                        ?>
                    </div>
                    
                    <?php if (!empty($artisan['entreprise'])): ?>
                        <p><strong>Entreprise:</strong> <?php echo htmlspecialchars($artisan['entreprise']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="section">
                <h2>Informations professionnelles</h2>
                <?php if (!empty($artisan['siret'])): ?>
                    <p><strong>SIRET:</strong> <?php echo htmlspecialchars($artisan['siret']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($artisan['code_naf'])): ?>
                    <p><strong>Code NAF:</strong> <?php echo htmlspecialchars($artisan['code_naf']); ?></p>
                <?php endif; ?>
                
                <p><strong>Expérience:</strong> <?php echo rand(1, 20); ?> ans</p>
                <p><strong>Zone d'intervention:</strong> Rayon de <?php echo rand(10, 50); ?> km autour de Bidart</p>
            </div>
            
            <div class="section">
                <h2>Localisation</h2>
                <p><?php echo htmlspecialchars($artisan['adresse']); ?></p>
                <div id="map" class="map-container"></div>
            </div>
            
            <div class="section">
                <h2>Contact</h2>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($artisan['adresse_mail']); ?></p>
                <?php if (!empty($artisan['telephone'])): ?>
                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($artisan['telephone']); ?></p>
                <?php else: ?>
                    <p><strong>Téléphone:</strong> <?php echo '0' . rand(6, 7) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99); ?></p>
                <?php endif; ?>
                
                <a href="contact.php?id=<?php echo $artisan['id_user']; ?>" class="contact-button">Contacter cet artisan</a>
            </div>
            
            <a href="recherche.html" class="back-button">Retour aux résultats</a>
        <?php endif; ?>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        <?php if (!isset($error_message) && !empty($artisan['loc_x']) && !empty($artisan['loc_y'])): ?>
            // Initialisation de la carte avec les coordonnées de l'artisan
            var map = L.map('map').setView([<?php echo $artisan['loc_x']; ?>, <?php echo $artisan['loc_y']; ?>], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Ajout d'un marqueur à la position de l'artisan
            L.marker([<?php echo $artisan['loc_x']; ?>, <?php echo $artisan['loc_y']; ?>]).addTo(map)
                .bindPopup("<?php echo htmlspecialchars($artisan['prenom'] . ' ' . $artisan['nom']); ?><br><?php echo htmlspecialchars($artisan['metier']); ?>")
                .openPopup();
        <?php else: ?>
            // Coordonnées par défaut pour Bidart
            var map = L.map('map').setView([43.4831519, -1.5551249], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Ajout d'un marqueur à une position approximative
            L.marker([43.4831519, -1.5551249]).addTo(map)
                .bindPopup("<?php echo htmlspecialchars($artisan['prenom'] . ' ' . $artisan['nom']); ?><br><?php echo htmlspecialchars($artisan['metier']); ?>")
                .openPopup();
        <?php endif; ?>
    </script>
</body>
</html>
