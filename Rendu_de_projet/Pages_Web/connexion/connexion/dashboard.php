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

// Récupérer les informations de l'utilisateur depuis la session
$user_id = $_SESSION['user_id'] ?? '';

// Récupérer la note de l'utilisateur et ses informations complètes
$note_utilisateur = 0;
$user_nom = '';
$user_prenom = '';
$avis = [];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les informations complètes de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        $user_nom = $user_data['nom'] ?? '';
        $user_prenom = $user_data['prenom'] ?? '';
        $note_utilisateur = $user_data['note'] ?? 4.7;
    }
    
    // Récupérer les avis depuis la table avis où id_user = user_id
    $order = isset($_GET['order']) ? $_GET['order'] : 'note_desc';
    
    $orderSql = "ORDER BY a.id_avis DESC"; // Par défaut, tri par ID
    
    // Vérifier si la table avis a une colonne note
    $stmt = $pdo->prepare("SHOW COLUMNS FROM avis LIKE 'note'");
    $stmt->execute();
    $hasNote = $stmt->rowCount() > 0;
    
    if ($hasNote) {
        $orderSql = "ORDER BY a.note DESC"; // Par défaut, tri par note la plus haute
        if ($order === 'note_asc') {
            $orderSql = "ORDER BY a.note ASC";
        }
    }
    
    // Vérifier si la table avis a une colonne date_creation
    $stmt = $pdo->prepare("SHOW COLUMNS FROM avis LIKE 'date_creation'");
    $stmt->execute();
    $hasDateCreation = $stmt->rowCount() > 0;
    
    
    // Requête pour récupérer les avis où id_user = user_id
    $stmt = $pdo->prepare("SELECT a.*, u.nom, u.prenom 
                          FROM avis a 
                          JOIN user u ON a.id_user = u.id_user 
                          WHERE a.id_user = ? 
                          $orderSql");
    $stmt->execute([$user_id]);
    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Gérer l'erreur silencieusement
    // En production, vous devriez logger cette erreur
    error_log("Erreur PDO: " . $e->getMessage());
}

// Fonction pour générer les étoiles HTML en fonction d'une note
function generateStars($note, $max = 5) {
    $html = '';
    $note = min(max(0, $note), $max); // S'assurer que la note est entre 0 et max
    
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $note) {
            $html .= '<span class="star filled">★</span>'; // Étoile pleine
        } else {
            $html .= '<span class="star empty">☆</span>'; // Étoile vide
        }
    }
    
    return $html;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tableau de bord - LSD.com</title>
    <style>
        /* Réinitialisation des styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Conteneur principal */
        .page-container {
            width: 100%;
            max-width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        /* En-tête */
        .site-header {
            background-color: #808080;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        
        .logo {
            color: black;
            font-size: 28px;
            margin: 0;
            font-weight: bold;
        }
        
        /* Nom d'utilisateur en haut à droite */
        .user-info {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: black;
            font-weight: bold;
        }
        
        /* Navigation */
        .dashboard-nav {
            display: flex;
            background-color: #8a7dc4;
        }
        
        .nav-item {
            padding: 10px 15px;
            color: black;
            text-decoration: none;
            flex: 1;
            text-align: center;
            border-right: 1px solid #fff;
        }
        
        .nav-item:last-child {
            border-right: none;
        }
        
        .nav-item.active {
            background-color: #7a6db4;
        }
        
        /* Contenu du tableau de bord */
        .dashboard-container {
            padding: 15px;
            flex-grow: 1;
        }
        
        /* Résumé de la note */
        .rating-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .average-rating {
            display: flex;
            align-items: center;
        }
        
        /* Styles pour les étoiles */
        .star {
            font-size: 20px;
            margin-left: 2px;
        }
        
        .star.filled {
            color: #FFD700; /* Jaune doré pour les étoiles pleines */
        }
        
        .star.empty {
            color: #ccc;
        }
        
        .sort-options {
            display: flex;
            align-items: center;
        }
        
        .sort-options select {
            margin-left: 5px;
            padding: 5px;
            border: 1px solid black;
        }
        
        /* Liste des avis */
        .reviews-list {
            margin-bottom: 20px;
        }
        
        .review-item {
            margin-bottom: 15px;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .rating-stars {
            display: flex;
        }
        
        .review-content {
            margin-bottom: 10px;
        }
        
        .review-content p {
            margin-top: 5px;
            color: #666;
        }
        
        /* Message quand aucun avis */
        .no-reviews {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
        /* Bouton de portfolio */
        .portfolio-action {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        
        .portfolio-btn {
            padding: 10px 20px;
            background-color: #c2e0c2;
            border: 1px solid black;
            border-radius: 20px;
            text-decoration: none;
            color: black;
            font-size: 16px;
        }
        
        /* Bouton de déconnexion */
        .logout-action {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        
        .logout-btn {
            padding: 10px 20px;
            background-color: #f8d7da;
            border: 1px solid black;
            border-radius: 20px;
            text-decoration: none;
            color: black;
            font-size: 16px;
        }
        
        /* Icône d'aide */
        .help-icon {
            position: absolute;
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
            .user-info {
                position: static;
                display: block;
                text-align: right;
                margin-top: 5px;
                transform: none;
            }
            
            .rating-summary {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .sort-options {
                margin-top: 10px;
            }
            
            .review-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .rating-stars {
                margin-top: 5px;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-nav {
                flex-direction: column;
            }
            
            .nav-item {
                border-right: none;
                border-bottom: 1px solid #fff;
            }
            
            .nav-item:last-child {
                border-bottom: none;
            }
            
            .portfolio-btn, .logout-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <header class="site-header">
            <h1 class="logo">LSD.com</h1>
            <div class="user-info"><?php echo htmlspecialchars($user_prenom . ' ' . $user_nom); ?></div>
        </header>
        
        <nav class="dashboard-nav">
            <a href="modifier-profil.php" class="nav-item active">Modifier son profil</a>
            <a href="modifier-calendrier.php" class="nav-item">Modifier son calendrier</a>
        </nav>
        
        <div class="dashboard-container">
            <div class="rating-summary">
                <div class="average-rating">
                    Vous avez <?php echo number_format($note_utilisateur, 1); ?> 
                    <?php echo generateStars($note_utilisateur); ?>
                </div>
                
                <div class="sort-options">
                    <span>ordonnée par :</span>
                    <select id="sort-order" onchange="changeOrder(this.value)">
                        <option value="note_desc" <?php echo isset($_GET['order']) && $_GET['order'] === 'note_desc' ? 'selected' : ''; ?>>note la plus haute</option>
                        <option value="note_asc" <?php echo isset($_GET['order']) && $_GET['order'] === 'note_asc' ? 'selected' : ''; ?>>note la plus basse</option>
                    </select>
                </div>
            </div>
            
            <div class="reviews-list">
                <?php if (empty($avis)): ?>
                <div class="no-reviews">
                    <p>Vous n'avez pas encore d'avis.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($avis as $avis_item): ?>
                    <div class="review-item">
                        <div class="review-header">
                            Avis de "<?php echo htmlspecialchars($avis_item['nom']); ?>"
                            <div class="rating-stars">
                                <?php 
                                // Utiliser la note si elle existe, sinon utiliser une valeur par défaut
                                $note = isset($avis_item['note']) ? $avis_item['note'] : 3;
                                echo generateStars($note);
                                ?>
                            </div>
                        </div>
                        <div class="review-content">
                            Commentaire :
                            <p><?php echo htmlspecialchars($avis_item['texte'] ?? '......'); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="portfolio-action">
                <a href="portfolio.php" class="portfolio-btn">Consulter mon portfolio</a>
            </div>
            
            <div class="logout-action">
                <a href="deconnexion.php" class="logout-btn">Se déconnecter</a>
            </div>
        </div>
        
        <div class="help-icon">?</div>
    </div>
    
    <script>
        function changeOrder(order) {
            window.location.href = 'dashboard.php?order=' + order;
        }
    </script>
</body>
</html>
