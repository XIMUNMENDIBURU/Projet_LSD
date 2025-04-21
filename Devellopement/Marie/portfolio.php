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

// Récupérer l'ID de l'utilisateur
$id_user = isset($_GET['id']) ? $_GET['id'] : (isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 1);

// Récupérer le message de notification (s'il existe)
$message = isset($_GET['message']) ? $_GET['message'] : '';

// Requête pour récupérer les informations de l'utilisateur
$sql_user = "SELECT * FROM user WHERE id_user = $id_user";
$result_user = $conn->query($sql_user);
$user = $result_user && $result_user->num_rows > 0 ? $result_user->fetch_assoc() : null;

// Vérifier si la colonne lieu existe
$lieu_exists = false;
$result = $conn->query("SHOW COLUMNS FROM publication LIKE 'lieu'");
if ($result && $result->num_rows > 0) {
    $lieu_exists = true;
}

// Requête pour récupérer les publications de l'utilisateur
$sql_publications = "SELECT * FROM publication WHERE id_user = $id_user";
// Ajouter ORDER BY date_creation DESC si la colonne existe
$result_check = $conn->query("SHOW COLUMNS FROM publication LIKE 'date_creation'");
if ($result_check && $result_check->num_rows > 0) {
    $sql_publications .= " ORDER BY date_creation DESC";
}
$result_publications = $conn->query($sql_publications);

// Données fictives pour les publications (à utiliser si la base de données est vide)
$fake_publications = [
    [
        'id_publication' => 1,
        'texte' => 'Mise en place d\'un tuyau d\'évacuation pour une maison individuelle',
        'lieu' => 'Bayonne'
    ],
    [
        'id_publication' => 2,
        'texte' => 'Mise en place d\'un tuyau de canalisation pour remplacer une canalisation défectueuse',
        'lieu' => 'Biarritz'
    ],
    [
        'id_publication' => 3,
        'texte' => 'Mise en place d\'un tuyau pour chauffage central',
        'lieu' => 'Anglet'
    ],
    [
        'id_publication' => 4,
        'texte' => 'Mise en place d\'un tuyau de ventilation cuisine professionnelle',
        'lieu' => 'Saint-Jean-de-Luz'
    ],
    [
        'id_publication' => 5,
        'texte' => 'Mise en place d\'un tuyau d\'irrigation automatique pour un jardin',
        'lieu' => 'Hendaye'
    ],
    [
        'id_publication' => 6,
        'texte' => 'Mise en place d\'un tuyau de drainage sous-sol',
        'lieu' => 'Bidart'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - LSD.com</title>
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
        
        .portfolio-header {
            background-color: #4a69bd;
            color: white;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .portfolio-title {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .notification {
            max-width: 1200px;
            margin: 0 auto 20px;
            padding: 15px 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .post-item {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        
        .post-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .post-content {
            padding: 20px;
        }
        
        .post-content h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .post-content p {
            color: #666;
            margin-bottom: 5px;
        }
        
        .post-image {
            height: 150px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .placeholder-image {
            width: 100%;
            height: 100%;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 24px;
        }
        
        .add-post-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            text-align: center;
        }
        
        .add-post-button {
            display: inline-block;
            padding: 12px 25px;
            background-color: #4a69bd;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        
        .add-post-button:hover {
            background-color: #3c5aa6;
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
    <div class="portfolio-header">
        <div class="portfolio-title">PORTFOLIO</div>
    </div>
    
    <?php if ($message === 'success'): ?>
    <div class="notification success">
        La publication a été supprimée avec succès.
    </div>
    <?php elseif ($message === 'error'): ?>
    <div class="notification error">
        Une erreur est survenue lors de la suppression de la publication.
    </div>
    <?php elseif ($message === 'added'): ?>
    <div class="notification success">
        La publication a été ajoutée avec succès.
    </div>
    <?php endif; ?>
    
    <div class="posts-grid">
        <?php 
        if ($result_publications && $result_publications->num_rows > 0) {
            $count = 1;
            while ($publication = $result_publications->fetch_assoc()) {
                $texte = $publication['texte'];
                $lieu = $lieu_exists && isset($publication['lieu']) && !empty($publication['lieu']) ? $publication['lieu'] : 'Bayonne';
        ?>
            <div class="post-item" data-id="<?php echo $publication['id_publication']; ?>">
                <div class="post-content">
                    <h3>Post <?php echo $count; ?> :</h3>
                    <p><?php echo substr($texte, 0, 50); ?>...</p>
                    <p>Lieu : <?php echo $lieu; ?></p>
                </div>
                <div class="post-image">
                    <div class="placeholder-image">
                        <i class="fas fa-image"></i>
                    </div>
                </div>
            </div>
        <?php
                $count++;
            }
        } else {
            // Afficher les publications fictives si aucune publication n'est trouvée
            foreach ($fake_publications as $index => $publication) {
        ?>
            <div class="post-item" data-id="<?php echo $publication['id_publication']; ?>">
                <div class="post-content">
                    <h3>Post <?php echo $index + 1; ?> :</h3>
                    <p><?php echo substr($publication['texte'], 0, 50); ?>...</p>
                    <p>Lieu : <?php echo isset($publication['lieu']) ? $publication['lieu'] : 'Bayonne'; ?></p>
                </div>
                <div class="post-image">
                    <div class="placeholder-image">
                        <i class="fas fa-image"></i>
                    </div>
                </div>
            </div>
        <?php
            }
        }
        ?>
    </div>
    
    <div class="add-post-container">
        <a href="ajouter-publication.php" class="add-post-button">Ajouter un post</a>
    </div>
    
    <div class="help-icon">
        <i class="fas fa-question-circle"></i>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        // Gestion des publications dans la grille
        const postItems = document.querySelectorAll(".post-item");
        postItems.forEach(item => {
            item.addEventListener("click", function() {
                const postId = this.getAttribute("data-id") || "1";
                window.location.href = `publication.php?id=${postId}`;
            });
        });
        
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
