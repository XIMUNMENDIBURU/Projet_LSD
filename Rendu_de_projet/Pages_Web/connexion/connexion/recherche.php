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
}

// Fonction pour calculer la distance entre deux points géographiques
function distance($lat1, $lon1, $lat2, $lon2) {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    return $miles * 1.609344; // Conversion en kilomètres
}

// Fonction pour calculer la similarité entre deux chaînes (logique fuzzy)
function similarity($str1, $str2) {
    $str1 = strtolower($str1);
    $str2 = strtolower($str2);
    
    // Calcul de la distance de Levenshtein
    $levenshtein = levenshtein($str1, $str2);
    $maxLength = max(strlen($str1), strlen($str2));
    
    if ($maxLength === 0) return 1.0;
    
    // Normalisation entre 0 et 1 (1 = identique, 0 = complètement différent)
    return 1.0 - ($levenshtein / $maxLength);
}

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logEvent("Connexion à la base de données réussie");
    
    // Récupération des paramètres de recherche
    $metier = $_GET['metier'] ?? '';
    $location = $_GET['location'] ?? '';
    $distance_km = isset($_GET['distance']) ? (int)$_GET['distance'] : 3;
    $min_rating = isset($_GET['minRating']) ? (int)$_GET['minRating'] : 3;
    $max_rating = isset($_GET['maxRating']) ? (int)$_GET['maxRating'] : 5;
    $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 0;
    $lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 0;
    
    logEvent("Recherche - Métier: $metier, Location: $location, Distance: $distance_km km, Note min: $min_rating, Note max: $max_rating, Coords: $lat,$lng");
    
    // Vérifier si la colonne 'note' existe dans la table user
    $checkColumnSql = "SHOW COLUMNS FROM user LIKE 'note'";
    $checkColumnStmt = $pdo->query($checkColumnSql);
    $noteColumnExists = $checkColumnStmt->rowCount() > 0;
    
    // Si la colonne 'note' n'existe pas, on l'ajoute
    if (!$noteColumnExists) {
        $alterTableSql = "ALTER TABLE user ADD COLUMN note FLOAT DEFAULT 0";
        $pdo->exec($alterTableSql);
        logEvent("Colonne 'note' ajoutée à la table user");
        
        // Ajouter des notes aléatoires pour la démonstration
        $updateNotesSql = "UPDATE user SET note = ROUND(RAND() * 4 + 1, 1) WHERE metier IS NOT NULL";
        $pdo->exec($updateNotesSql);
        logEvent("Notes aléatoires ajoutées aux utilisateurs");
    }
    
    // Récupération de tous les artisans
    $sql = "SELECT * FROM user WHERE metier IS NOT NULL";
    $stmt = $pdo->query($sql);
    $artisans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filtrage des résultats avec logique fuzzy, distance et note
    $results = [];
    $map_data = []; // Initialiser le tableau pour les données de la carte
    $similarity_threshold = 0.6; // Seuil de similarité (0.6 = 60% de similarité)
    
    foreach ($artisans as $artisan) {
        // Vérification de la similarité du métier (si un métier est spécifié)
        $metier_similarity = 1.0;
        if (!empty($metier) && !empty($artisan['metier'])) {
            $metier_similarity = similarity($metier, $artisan['metier']);
            if ($metier_similarity < $similarity_threshold) {
                continue; // Passer à l'artisan suivant si le métier ne correspond pas assez
            }
        }
        
        // Vérification de la note
        $note = isset($artisan['note']) ? (float)$artisan['note'] : 0;
        if ($note < $min_rating || $note > $max_rating) {
            continue; // Passer à l'artisan suivant si la note n'est pas dans la plage
        }
        
        // Ajouter le score de similarité à l'artisan
        $artisan['similarity'] = $metier_similarity;
        
        // Vérification de la distance (si des coordonnées sont spécifiées)
        if ($lat && $lng && isset($artisan['loc_x']) && isset($artisan['loc_y']) && 
            !empty($artisan['loc_x']) && !empty($artisan['loc_y'])) {
            $dist = distance($lat, $lng, (float)$artisan['loc_x'], (float)$artisan['loc_y']);
            $artisan['distance'] = round($dist, 1);
            
            // Ajouter à map_data même si hors du rayon de recherche
            $map_data[] = [
                'id' => $artisan['id_user'],
                'lat' => (float)$artisan['loc_x'],
                'lng' => (float)$artisan['loc_y'],
                'name' => $artisan['prenom'] . ' ' . $artisan['nom'],
                'metier' => $artisan['metier'],
                'note' => (float)$artisan['note'],
                'distance' => $artisan['distance'],
                'inRadius' => ($dist <= $distance_km) // Indiquer si l'artisan est dans le rayon
            ];
            
            // Vérifier si l'artisan est dans le rayon de recherche
            if ($dist > $distance_km) {
                continue; // Passer à l'artisan suivant si trop éloigné
            }
        } else {
            $artisan['distance'] = null;
            continue; // Passer à l'artisan suivant s'il n'a pas de coordonnées
        }
        
        // Ajouter l'artisan aux résultats
        $results[] = $artisan;
    }
    
    // Trier les résultats par similarité (du plus similaire au moins similaire)
    usort($results, function($a, $b) {
        if ($a['similarity'] == $b['similarity']) {
            // Si même similarité, trier par note (de la plus haute à la plus basse)
            $note_a = isset($a['note']) ? (float)$a['note'] : 0;
            $note_b = isset($b['note']) ? (float)$b['note'] : 0;
            
            if ($note_a == $note_b) {
                // Si même note, trier par distance si disponible
                if ($a['distance'] !== null && $b['distance'] !== null) {
                    return $a['distance'] <=> $b['distance'];
                }
                return 0;
            }
            
            return $note_b <=> $note_a;
        }
        return $b['similarity'] <=> $a['similarity'];
    });
    
    // Ajouter les données JSON pour la carte avec window. pour s'assurer qu'elles sont globales
    echo '<script>window.artisansData = ' . json_encode($map_data) . ';</script>';
    
    // Affichage des résultats
    if (count($results) > 0) {
        foreach ($results as $result) {
            echo '<div class="result-item">';
            
            // Nom et prénom
            echo '<h3 class="result-name">' . htmlspecialchars($result['civilite'] ?? '') . ' ' . 
                 htmlspecialchars($result['nom']) . ' ' . 
                 htmlspecialchars($result['prenom']) . '</h3>';
            
            // Métier
            echo '<p class="result-profession">' . htmlspecialchars($result['metier']) . '</p>';
            
            // Adresse (si disponible)
            if (!empty($result['adresse'])) {
                echo '<p class="result-address">' . htmlspecialchars($result['adresse']) . '</p>';
            } else {
                // Construire une adresse fictive pour la démonstration
                $adresse = '';
                if (!empty($result['entreprise'])) {
                    $adresse .= htmlspecialchars($result['entreprise']) . ', ';
                }
                $adresse .= rand(1, 50) . ' ' . ['Rue', 'Avenue', 'Boulevard', 'Allée'][rand(0, 3)] . ' ';
                $adresse .= ['des Lilas', 'de la Paix', 'Victor Hugo', 'de l\'Uhabia', 'des Roses'][rand(0, 4)] . ', ';
                $adresse .= '64210 Bidart';
                
                echo '<p class="result-address">' . $adresse . '</p>';
            }
            
            // Note
            $note = isset($result['note']) ? (float)$result['note'] : 0;
            echo '<p class="result-rating">Note: ';
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= floor($note)) {
                    echo '<span class="star">★</span>'; // Étoile pleine
                } elseif ($i - 0.5 <= $note) {
                    echo '<span class="half-star">✬</span>'; // Étoile à moitié pleine
                } else {
                    echo '<span class="empty-star">☆</span>'; // Étoile vide
                }
            }
            echo ' <span>(' . number_format($note, 1) . '/5)</span></p>';
            
            // Distance (si disponible)
            if ($result['distance'] !== null) {
                echo '<p class="result-distance">Distance: ' . $result['distance'] . ' km</p>';
            }
            
            // Bouton "En savoir plus"
            echo '<a href="artisant-detail.php?id=' . $result['id_user'] . '" class="more-info">En savoir plus</a>';
            
            echo '<div style="clear:both;"></div>';
            echo '</div>';
        }
    } else {
        echo '<div class="no-results">';
        echo '<p>Aucun résultat ne correspond à votre recherche.</p>';
        echo '<p>Essayez d\'élargir votre zone de recherche ou de modifier vos critères.</p>';
        echo '</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="message error">';
    echo '<h3>❌ Erreur de base de données</h3>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    logEvent("Erreur PDO: " . $e->getMessage());
}
?>
