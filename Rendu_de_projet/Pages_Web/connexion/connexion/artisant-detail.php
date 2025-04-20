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
        error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'artisan_detail_log.txt');
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
    
    // Vérifier si la table avis existe
    $tableExists = false;
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'avis'");
        $tableExists = $checkTable->rowCount() > 0;
    } catch (PDOException $e) {
        logEvent("Erreur lors de la vérification de la table avis: " . $e->getMessage());
    }
    
    // Créer la table avis si elle n'existe pas
    if (!$tableExists) {
        try {
            $createTableSql = "CREATE TABLE avis (
                id_avis INT AUTO_INCREMENT PRIMARY KEY,
                id_user INT NOT NULL,
                id_artisan INT NOT NULL,
                note FLOAT NOT NULL,
                commentaire TEXT,
                date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
                FOREIGN KEY (id_artisan) REFERENCES user(id_user) ON DELETE CASCADE
            )";
            $pdo->exec($createTableSql);
            logEvent("Table avis créée avec succès");
        } catch (PDOException $e) {
            logEvent("Erreur lors de la création de la table avis: " . $e->getMessage());
        }
    }
    
    // Récupération des avis pour cet artisan
    $avis = [];
    try {
        $avisStmt = $pdo->prepare("
            SELECT a.*, u.nom, u.prenom 
            FROM avis a 
            JOIN user u ON a.id_user = u.id_user 
            WHERE a.id_artisan = ? 
            ORDER BY a.date_creation DESC
        ");
        $avisStmt->execute([$artisan_id]);
        $avis = $avisStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logEvent("Erreur lors de la récupération des avis: " . $e->getMessage());
    }
    
    // Calculer la note moyenne basée sur les avis
    $note_moyenne = 0;
    $nombre_avis = count($avis);
    
    if ($nombre_avis > 0) {
        $total_notes = 0;
        foreach ($avis as $a) {
            $total_notes += $a['note'];
        }
        $note_moyenne = round($total_notes / $nombre_avis, 1);
        
        // Mettre à jour la note de l'artisan si nécessaire
        if ($note_moyenne != $artisan['note']) {
            try {
                $updateNoteStmt = $pdo->prepare("UPDATE user SET note = ? WHERE id_user = ?");
                $updateNoteStmt->execute([$note_moyenne, $artisan_id]);
                $artisan['note'] = $note_moyenne;
                logEvent("Note mise à jour pour l'artisan ID {$artisan_id}: {$note_moyenne}");
            } catch (PDOException $e) {
                logEvent("Erreur lors de la mise à jour de la note: " . $e->getMessage());
            }
        }
    } else {
        $note_moyenne = $artisan['note'];
    }
    
} catch (PDOException $e) {
    logEvent("Erreur PDO: " . $e->getMessage());
    $error_message = $e->getMessage();
}

// Fonction pour générer les étoiles HTML
function generateStars($note, $max = 5) {
    $html = '';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= floor($note)) {
            $html .= '<span class="star">★</span>'; // Étoile pleine
        } elseif ($i - 0.5 <= $note) {
            $html .= '<span class="star">✬</span>'; // Étoile à moitié pleine
        } else {
            $html .= '<span class="empty-star">☆</span>'; // Étoile vide
        }
    }
    return $html;
}

// Fonction pour formater la date
function formatDate($date) {
    $timestamp = strtotime($date);
    return date('d/m/Y à H:i', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSD.com - <?php echo htmlspecialchars($artisan['prenom'] . ' ' . $artisan['nom']); ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        /* Styles généraux */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        /* En-tête */
        header {
            background-color: #808080;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #000;
            text-decoration: none;
        }
        
        .login-link {
            background-color: white;
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            color: #000;
            border: 1px solid #000;
            font-size: 14px;
        }
        
        /* Conteneur principal */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        /* Carte de l'artisan */
        .artisan-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .artisan-header {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 20px;
            gap: 20px;
        }
        
        .artisan-avatar {
            width: 100px;
            height: 100px;
            background-color: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #555;
            flex-shrink: 0;
        }
        
        .artisan-info {
            flex: 1;
            min-width: 200px;
        }
        
        .artisan-info h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .artisan-info p {
            margin: 5px 0;
            color: #555;
        }
        
        .artisan-rating {
            margin: 10px 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .star {
            color: #FFD700;
        }
        
        .empty-star {
            color: #ccc;
        }
        
        .rating-count {
            margin-left: 5px;
            color: #666;
            font-size: 14px;
        }
        
        /* Actions */
        .artisan-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-btn {
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 150px;
        }
        
        .contact-btn {
            background-color: #4285f4;
            color: white;
            border: none;
        }
        
        .contact-btn:hover {
            background-color: #3367d6;
        }
        
        .review-btn {
            background-color: white;
            color: #000;
            border: 1px solid #000;
        }
        
        .review-btn:hover {
            background-color: #f0f0f0;
        }
        
        .back-btn {
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #ccc;
        }
        
        .back-btn:hover {
            background-color: #e0e0e0;
        }
        
        /* Informations détaillées */
        .artisan-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        
        .detail-section {
            flex: 1;
            min-width: 300px;
        }
        
        .detail-section h2 {
            font-size: 18px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-section p {
            margin: 8px 0;
        }
        
        .detail-section strong {
            font-weight: bold;
            color: #333;
        }
        
        /* Carte */
        .map-container {
            height: 300px;
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* Section des avis */
        .reviews-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .reviews-header h2 {
            font-size: 20px;
            margin: 0;
        }
        
        .reviews-list {
            margin-top: 20px;
        }
        
        .review-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .reviewer-info {
            font-weight: bold;
        }
        
        .review-date {
            color: #888;
            font-size: 14px;
        }
        
        .review-rating {
            margin-bottom: 10px;
        }
        
        .review-content {
            color: #555;
        }
        
        .no-reviews {
            text-align: center;
            padding: 20px;
            color: #888;
            font-style: italic;
        }
        
        /* Formulaire d'avis */
        .review-form-container {
            display: none;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .review-form-container h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .rating-input {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .rating-input button {
            background: none;
            border: none;
            font-size: 24px;
            color: #ccc;
            cursor: pointer;
        }
        
        .rating-input button.active {
            color: #FFD700;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .form-actions button {
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .submit-btn {
            background-color: #4285f4;
            color: white;
            border: none;
        }
        
        .cancel-btn {
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #ccc;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .artisan-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .artisan-info {
                width: 100%;
            }
            
            .artisan-rating {
                justify-content: center;
            }
            
            .artisan-actions {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
            }
            
            .review-header {
                flex-direction: column;
            }
            
            .review-date {
                margin-top: 5px;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .artisan-card, .reviews-section, .review-form-container {
                padding: 15px;
            }
            
            .artisan-avatar {
                width: 80px;
                height: 80px;
                font-size: 28px;
            }
            
            .artisan-info h1 {
                font-size: 20px;
            }
            
            .reviews-header {
                flex-direction: column;
                gap: 10px;
            }
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
                <a href="recherche.html" class="back-btn">Retour à la recherche</a>
            </div>
        <?php else: ?>
            <!-- Carte de l'artisan -->
            <div class="artisan-card">
                <div class="artisan-header">
                    <div class="artisan-avatar">
                        <?php echo strtoupper(substr($artisan['prenom'], 0, 1)); ?>
                    </div>
                    <div class="artisan-info">
                        <h1><?php echo htmlspecialchars($artisan['prenom'] . ' ' . $artisan['nom']); ?></h1>
                        
                        <?php if (!empty($artisan['entreprise'])): ?>
                            <p><strong>Entreprise:</strong> <?php echo htmlspecialchars($artisan['entreprise']); ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Métier:</strong> <?php echo htmlspecialchars($artisan['metier']); ?></p>
                        <p><strong>Adresse:</strong> <?php echo htmlspecialchars($artisan['adresse']); ?></p>
                        
                        <div class="artisan-rating">
                            <?php echo generateStars($note_moyenne); ?>
                            <span><?php echo number_format($note_moyenne, 1); ?>/5</span>
                            <span class="rating-count">(<?php echo $nombre_avis; ?> avis)</span>
                        </div>
                    </div>
                </div>
                
                <div class="artisan-actions">
                    <a href="contact.php?id=<?php echo $artisan_id; ?>" class="action-btn contact-btn">Contacter l'artisan</a>
                    <button id="leaveReviewBtn" class="action-btn review-btn">Laisser un avis</button>
                    <a href="recherche.html" class="action-btn back-btn">Retour aux résultats</a>
                </div>
                
                <div class="artisan-details">
                    <div class="detail-section">
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
                    
                    <div class="detail-section">
                        <h2>Contact</h2>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($artisan['adresse_mail']); ?></p>
                        <?php if (!empty($artisan['telephone'])): ?>
                            <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($artisan['telephone']); ?></p>
                        <?php else: ?>
                            <p><strong>Téléphone:</strong> <?php echo '0' . rand(6, 7) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h2>Localisation</h2>
                    <div id="map" class="map-container"></div>
                </div>
            </div>
            
            <!-- Formulaire d'avis -->
            <div id="reviewForm" class="review-form-container">
                <h2>Laisser un avis</h2>
                <form id="newReviewForm" action="soumettre-avis.php" method="POST">
                    <input type="hidden" name="id_artisan" value="<?php echo $artisan_id; ?>">
                    
                    <div class="form-group">
                        <label for="rating">Note:</label>
                        <div class="rating-input" id="ratingStars">
                            <button type="button" data-value="1">★</button>
                            <button type="button" data-value="2">★</button>
                            <button type="button" data-value="3">★</button>
                            <button type="button" data-value="4">★</button>
                            <button type="button" data-value="5">★</button>
                        </div>
                        <input type="hidden" name="note" id="ratingValue" value="5">
                    </div>
                    
                    <div class="form-group">
                        <label for="commentaire">Commentaire:</label>
                        <textarea name="commentaire" id="commentaire" placeholder="Partagez votre expérience avec cet artisan..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancelReviewBtn" class="cancel-btn">Annuler</button>
                        <button type="submit" class="submit-btn">Soumettre l'avis</button>
                    </div>
                </form>
            </div>
            
            <!-- Section des avis -->
            <div class="reviews-section">
                <div class="reviews-header">
                    <h2>Avis des clients (<?php echo $nombre_avis; ?>)</h2>
                </div>
                
                <div class="reviews-list">
                    <?php if (empty($avis)): ?>
                        <div class="no-reviews">
                            <p>Cet artisan n'a pas encore reçu d'avis.</p>
                            <p>Soyez le premier à partager votre expérience !</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($avis as $a): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <?php echo htmlspecialchars($a['prenom'] . ' ' . $a['nom']); ?>
                                    </div>
                                    <div class="review-date">
                                        <?php echo formatDate($a['date_creation']); ?>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php echo generateStars($a['note']); ?>
                                    <span><?php echo number_format($a['note'], 1); ?>/5</span>
                                </div>
                                <div class="review-content">
                                    <?php echo nl2br(htmlspecialchars($a['commentaire'] ?? '')); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Initialisation de la carte
        <?php if (!isset($error_message) && !empty($artisan['loc_x']) && !empty($artisan['loc_y'])): ?>
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
        
        // Gestion du formulaire d'avis
        document.addEventListener('DOMContentLoaded', function() {
            const leaveReviewBtn = document.getElementById('leaveReviewBtn');
            const reviewForm = document.getElementById('reviewForm');
            const cancelReviewBtn = document.getElementById('cancelReviewBtn');
            const ratingStars = document.getElementById('ratingStars');
            const ratingValue = document.getElementById('ratingValue');
            
            // Afficher le formulaire d'avis
            if (leaveReviewBtn) {
                leaveReviewBtn.addEventListener('click', function() {
                    reviewForm.style.display = 'block';
                    // Faire défiler jusqu'au formulaire
                    reviewForm.scrollIntoView({ behavior: 'smooth' });
                });
            }
            
            // Masquer le formulaire d'avis
            if (cancelReviewBtn) {
                cancelReviewBtn.addEventListener('click', function() {
                    reviewForm.style.display = 'none';
                });
            }
            
            // Gestion des étoiles pour la notation
            if (ratingStars) {
                const stars = ratingStars.querySelectorAll('button');
                
                // Initialiser avec 5 étoiles
                updateStars(5);
                
                // Gérer le clic sur les étoiles
                stars.forEach(function(star) {
                    star.addEventListener('click', function() {
                        const value = parseInt(this.getAttribute('data-value'));
                        ratingValue.value = value;
                        updateStars(value);
                    });
                });
                
                function updateStars(value) {
                    stars.forEach(function(star) {
                        const starValue = parseInt(star.getAttribute('data-value'));
                        if (starValue <= value) {
                            star.classList.add('active');
                        } else {
                            star.classList.remove('active');
                        }
                    });
                }
            }
            
            // Validation du formulaire
            const newReviewForm = document.getElementById('newReviewForm');
            if (newReviewForm) {
                newReviewForm.addEventListener('submit', function(e) {
                    const commentaire = document.getElementById('commentaire').value.trim();
                    if (commentaire.length < 10) {
                        e.preventDefault();
                        alert('Veuillez saisir un commentaire d\'au moins 10 caractères.');
                    }
                });
            }
        });
    </script>
</body>
</html>
