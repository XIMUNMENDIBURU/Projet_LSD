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

// Récupérer les informations de l'utilisateur connecté
$id_user = isset($_GET['id']) ? $_GET['id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 1); // Par défaut utilisateur 1 pour test

// Requête pour récupérer les informations de l'utilisateur
$sql_user = "SELECT * FROM user WHERE id_user = $id_user";
$result_user = $conn->query($sql_user);
$user = $result_user && $result_user->num_rows > 0 ? $result_user->fetch_assoc() : null;

// Vérifier si la colonne date_creation existe dans la table avis
$date_creation_exists = false;
$result = $conn->query("SHOW COLUMNS FROM avis LIKE 'date_creation'");
if ($result && $result->num_rows > 0) {
    $date_creation_exists = true;
}

// Vérifier si la colonne note existe dans la table avis
$note_exists = false;
$result = $conn->query("SHOW COLUMNS FROM avis LIKE 'note'");
if ($result && $result->num_rows > 0) {
    $note_exists = true;
}

// Requête pour récupérer les avis sur l'utilisateur
$sql_avis = "SELECT * FROM avis WHERE id_user = $id_user";
// Ajouter ORDER BY seulement si la colonne date_creation existe
if ($date_creation_exists) {
    $sql_avis .= " ORDER BY date_creation DESC";
}
$result_avis = $conn->query($sql_avis);

// Calculer la note moyenne directement avec une requête SQL
$note_moyenne = 0;
$nombre_avis = 0;

if ($note_exists) {
    $sql_moyenne = "SELECT AVG(note) as moyenne, COUNT(*) as nombre FROM avis WHERE id_user = $id_user";
    $result_moyenne = $conn->query($sql_moyenne);
    
    if ($result_moyenne && $result_moyenne->num_rows > 0) {
        $row_moyenne = $result_moyenne->fetch_assoc();
        $note_moyenne = round($row_moyenne['moyenne'], 1);
        $nombre_avis = $row_moyenne['nombre'];
    }
} else {
    // Calcul manuel si la colonne note n'existe pas
    if ($result_avis && $result_avis->num_rows > 0) {
        // Réinitialiser le pointeur de résultat
        $result_avis->data_seek(0);
        $total_notes = 0;
        
        while ($avis = $result_avis->fetch_assoc()) {
            $total_notes += 5; // Note par défaut de 5
            $nombre_avis++;
        }
        
        if ($nombre_avis > 0) {
            $note_moyenne = round($total_notes / $nombre_avis, 1);
        }
        
        // Réinitialiser le pointeur de résultat pour l'utiliser à nouveau
        $result_avis->data_seek(0);
    }
}

// Si aucun avis n'est trouvé, utiliser une note moyenne fictive
if ($nombre_avis == 0) {
    $note_moyenne = 4.2;
}

// Données fictives pour les avis (à utiliser si la base de données est vide)
$fake_avis = [
    [
        'nom' => 'Dupont',
        'prenom' => 'Martin',
        'note' => 5,
        'texte' => 'Excellent travail ! Très professionnel et ponctuel. Je recommande vivement ce prestataire pour tous vos travaux de plomberie.',
        'date_creation' => '2023-06-15'
    ],
    [
        'nom' => 'Lefebvre',
        'prenom' => 'Sophie',
        'note' => 4,
        'texte' => 'Bon travail dans l\'ensemble. Quelques finitions auraient pu être meilleures, mais le résultat est satisfaisant.',
        'date_creation' => '2023-05-20'
    ],
    [
        'nom' => 'Moreau',
        'prenom' => 'Jean',
        'note' => 5,
        'texte' => 'Intervention rapide et efficace. Prix très raisonnable. Je ferai à nouveau appel à ses services.',
        'date_creation' => '2023-04-10'
    ],
    [
        'nom' => 'Lambert',
        'prenom' => 'Marie',
        'note' => 3,
        'texte' => 'Travail correct mais délai non respecté. Deux jours de retard sur la date prévue.',
        'date_creation' => '2023-03-05'
    ],
    [
        'nom' => 'Dubois',
        'prenom' => 'Pierre',
        'note' => 5,
        'texte' => 'Parfait ! Travail soigné et propre. Excellente communication tout au long du projet.',
        'date_creation' => '2023-02-18'
    ],
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - LSD.com</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        header {
            background-color: #4a69bd;
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .tabs {
            display: flex;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 15px 20px;
            text-decoration: none;
            color: #495057;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            color: #4a69bd;
            border-bottom: 3px solid #4a69bd;
        }
        
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .profile-info h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            color: #333;
        }
        
        .profile-info p {
            color: #666;
            margin-bottom: 5px;
        }
        
        .portfolio-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4a69bd;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .portfolio-button:hover {
            background-color: #3c5aa6;
        }
        
        .reviews-section {
            padding: 20px;
        }
        
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .reviews-header h3 {
            font-size: 1.5rem;
            color: #333;
        }
        
        .sort-container {
            display: flex;
            align-items: center;
        }
        
        .sort-container label {
            margin-right: 10px;
            color: #666;
        }
        
        .sort-container select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }
        
        .rating-summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .rating-summary p {
            font-size: 1.2rem;
            color: #333;
        }
        
        .rating-summary .fa-star {
            color: #ffc107;
        }
        
        .reviews-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .review {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #4a69bd;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .review-author {
            font-weight: bold;
            color: #333;
        }
        
        .review-stars {
            color: #ffc107;
        }
        
        .review-comment {
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        
        .review-text {
            color: #666;
            line-height: 1.5;
        }
        
        .help-icon {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background-color: #4a69bd;
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .help-icon:hover {
            background-color: #3c5aa6;
        }
    </style>
</head>
<body>
    <header>
        <h1>LSD.com</h1>
    </header>
    
    <div class="tabs">
        <a href="#" class="tab active">Modifier son profil</a>
        <a href="#" class="tab">Modifier son calendrier</a>
    </div>
    
    <div class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-info">
                    <h2><?php echo $user ? $user['nom'] . ' ' . $user['prenom'] : 'Ximun Mendiburu'; ?></h2>
                    <p><?php echo $user ? $user['metier'] : 'Fermier'; ?></p>
                    <p><?php echo $user ? $user['entreprise'] : 'Robot-Maker'; ?></p>
                </div>
                <div class="profile-actions">
                    <a href="portfolio.php?id=<?php echo $id_user; ?>" class="portfolio-button">Consulter mon portfolio</a>
                </div>
            </div>
            
            <div class="reviews-section">
                <div class="reviews-header">
                    <h3>Avis clients</h3>
                    <div class="sort-container">
                        <label for="sort">ordonnée par :</label>
                        <select id="sort">
                            <option value="highest">note la plus haute</option>
                            <option value="lowest">note la plus basse</option>
                            <?php if ($date_creation_exists): ?>
                            <option value="recent">plus récent</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="rating-summary">
                    <p>Vous avez <?php echo $note_moyenne; ?> <i class="fas fa-star"></i> (<?php echo $nombre_avis; ?> avis)</p>
                </div>
                
                <div class="reviews-container">
                    <?php 
                    if ($result_avis && $result_avis->num_rows > 0) {
                        while ($avis = $result_avis->fetch_assoc()) {
                            $id_avis = $avis['id_avis'];
                            $texte = $avis['texte'];
                            $note = isset($avis['note']) ? $avis['note'] : 5;
                            $date = isset($avis['date_creation']) ? $avis['date_creation'] : '';
                    ?>
                        <div class="review" data-note="<?php echo $note; ?>" <?php if ($date_creation_exists && $date): ?>data-date="<?php echo $date; ?>"<?php endif; ?>>
                            <div class="review-header">
                                <p class="review-author">Avis #<?php echo $id_avis; ?></p>
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $note): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="review-comment">Commentaire :</p>
                            <p class="review-text"><?php echo $texte; ?></p>
                        </div>
                    <?php
                        }
                    } else {
                        // Afficher les avis fictifs si aucun avis n'est trouvé
                        foreach ($fake_avis as $index => $avis) {
                    ?>
                        <div class="review" data-note="<?php echo $avis['note']; ?>" data-date="<?php echo $avis['date_creation']; ?>">
                            <div class="review-header">
                                <p class="review-author">Avis de "<?php echo $avis['prenom'] . ' ' . $avis['nom']; ?>"</p>
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $avis['note']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="review-comment">Commentaire :</p>
                            <p class="review-text"><?php echo $avis['texte']; ?></p>
                        </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="help-icon">
        <i class="fas fa-question-circle"></i>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        // Gestion du tri des avis
        const sortSelect = document.getElementById("sort");
        if (sortSelect) {
            sortSelect.addEventListener("change", function() {
                const reviewsContainer = document.querySelector(".reviews-container");
                const reviews = Array.from(reviewsContainer.querySelectorAll(".review"));
                
                if (this.value === "highest") {
                    // Trier par note la plus haute
                    reviews.sort((a, b) => {
                        const noteA = Number.parseInt(a.getAttribute("data-note")) || 0;
                        const noteB = Number.parseInt(b.getAttribute("data-note")) || 0;
                        return noteB - noteA;
                    });
                } else if (this.value === "lowest") {
                    // Trier par note la plus basse
                    reviews.sort((a, b) => {
                        const noteA = Number.parseInt(a.getAttribute("data-note")) || 0;
                        const noteB = Number.parseInt(b.getAttribute("data-note")) || 0;
                        return noteA - noteB;
                    });
                } else if (this.value === "recent") {
                    // Trier par date la plus récente
                    reviews.sort((a, b) => {
                        const dateA = a.getAttribute("data-date") || '';
                        const dateB = b.getAttribute("data-date") || '';
                        return dateB.localeCompare(dateA);
                    });
                }
                
                // Réinsérer les avis triés
                reviews.forEach(review => {
                    reviewsContainer.appendChild(review);
                });
            });
        }
        
        // Gestion de l'icône d'aide
        const helpIcon = document.querySelector(".help-icon");
        if (helpIcon) {
            helpIcon.addEventListener("click", () => {
                alert("Besoin d'aide ? Contactez-nous à support@lsd.com");
            });
        }
    });
    </script>
</body>
</html>