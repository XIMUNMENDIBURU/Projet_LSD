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
$id_publication = isset($_GET['id']) ? $_GET['id'] : 1;

// Récupérer le message (s'il existe)
$message = isset($_GET['message']) ? $_GET['message'] : '';

// Vérifier si la colonne lieu existe
$lieu_exists = false;
$result = $conn->query("SHOW COLUMNS FROM publication LIKE 'lieu'");
if ($result && $result->num_rows > 0) {
    $lieu_exists = true;
}

// Requête pour récupérer les informations de la publication
$sql_publication = "SELECT p.*, u.nom, u.prenom, u.metier 
                    FROM publication p 
                    JOIN user u ON p.id_user = u.id_user 
                    WHERE p.id_publication = $id_publication";
$result_publication = $conn->query($sql_publication);
$publication = $result_publication && $result_publication->num_rows > 0 ? $result_publication->fetch_assoc() : null;

// Requête pour récupérer les images de la publication
$sql_images = "SELECT * FROM image WHERE id_publication = $id_publication";
$result_images = $conn->query($sql_images);

// Données fictives pour les publications (à utiliser si la base de données est vide)
$fake_publications = [
    1 => [
        'id_publication' => 1,
        'texte' => 'Mise en place d\'un tuyau d\'évacuation pour une maison individuelle',
        'lieu' => 'Bayonne',
        'nom' => 'Mendiburu',
        'prenom' => 'Ximun',
        'metier' => 'Fermier'
    ],
    2 => [
        'id_publication' => 2,
        'texte' => 'Mise en place d\'un tuyau de canalisation pour remplacer une canalisation défectueuse',
        'lieu' => 'Biarritz',
        'nom' => 'Mendiburu',
        'prenom' => 'Ximun',
        'metier' => 'Fermier'
    ],
    3 => [
        'id_publication' => 3,
        'texte' => 'Mise en place d\'un tuyau pour chauffage central',
        'lieu' => 'Anglet',
        'nom' => 'Mendiburu',
        'prenom' => 'Ximun',
        'metier' => 'Fermier'
    ],
    4 => [
        'id_publication' => 4,
        'texte' => 'Mise en place d\'un tuyau de ventilation cuisine professionnelle',
        'lieu' => 'Saint-Jean-de-Luz',
        'nom' => 'Mendiburu',
        'prenom' => 'Ximun',
        'metier' => 'Fermier'
    ],
    5 => [
        'id_publication' => 5,
        'texte' => 'Mise en place d\'un tuyau d\'irrigation automatique pour un jardin',
        'lieu' => 'Hendaye',
        'nom' => 'Mendiburu',
        'prenom' => 'Ximun',
        'metier' => 'Fermier'
    ],
    6 => [
        'id_publication' => 6,
        'texte' => 'Mise en place d\'un tuyau de drainage sous-sol',
        'lieu' => 'Bidart',
        'nom' => 'Mendiburu',
        'prenom' => 'Ximun',
        'metier' => 'Fermier'
    ]
];

// Si la publication n'est pas trouvée dans la base de données, utiliser les données fictives
if (!$publication && isset($fake_publications[$id_publication])) {
    $publication = $fake_publications[$id_publication];
}

// Extraire le texte et le lieu
$texte_complet = $publication['texte'];
$lieu = $lieu_exists && isset($publication['lieu']) && !empty($publication['lieu']) ? $publication['lieu'] : 'Bayonne';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publication - LSD.com</title>
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
        
        .post-detail {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .post-info {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .post-info h2 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.8rem;
        }
        
        .post-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .post-location {
            color: #666;
            font-style: italic;
        }
        
        .post-images {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .post-image-large, .post-image-small {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 300px;
        }
        
        .post-image-small {
            height: 150px;
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
        
        .post-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .edit-button, .delete-button {
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        
        .edit-button {
            background-color: #4a69bd;
            color: white;
        }
        
        .edit-button:hover {
            background-color: #3c5aa6;
        }
        
        .delete-button {
            background-color: #e74c3c;
            color: white;
        }
        
        .delete-button:hover {
            background-color: #c0392b;
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
        <div class="portfolio-title">POST</div>
    </div>
    
    <?php if ($message === 'updated'): ?>
    <div class="notification success">
        La publication a été mise à jour avec succès.
    </div>
    <?php endif; ?>
    
    <div class="post-detail">
        <div class="post-info">
            <h2>Post :</h2>
            <p class="post-description"><?php echo $texte_complet; ?></p>
            <p class="post-location">Lieu : <?php echo $lieu; ?></p>
        </div>
        
        <div class="post-images">
            <?php 
            if ($result_images && $result_images->num_rows > 0) {
                $first = true;
                while ($image = $result_images->fetch_assoc()) {
                    if ($first) {
                        echo '<div class="post-image-large">';
                        echo '<img src="' . $image['chemin'] . '" alt="Image de la publication">';
                        echo '</div>';
                        $first = false;
                    } else {
                        echo '<div class="post-image-small">';
                        echo '<img src="' . $image['chemin'] . '" alt="Image de la publication">';
                        echo '</div>';
                    }
                }
            } else {
                // Afficher des placeholders si aucune image n'est trouvée
            ?>
                <div class="post-image-large">
                    <div class="placeholder-image">
                        <i class="fas fa-image"></i>
                    </div>
                </div>
                <div class="post-image-small">
                    <div class="placeholder-image">
                        <i class="fas fa-image"></i>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
        
        <div class="post-actions">
            <a href="modifier-publication.php?id=<?php echo $id_publication; ?>" class="edit-button">Modifier</a>
            <a href="#" class="delete-button" data-id="<?php echo $id_publication; ?>">Supprimer ce post</a>
        </div>
    </div>
    
    <div class="help-icon">
        <i class="fas fa-question-circle"></i>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        // Gestion du bouton de suppression
        const deleteButton = document.querySelector(".delete-button");
        if (deleteButton) {
            deleteButton.addEventListener("click", function(e) {
                e.preventDefault();
                const postId = this.getAttribute("data-id");
                if (confirm("Êtes-vous sûr de vouloir supprimer ce post ?")) {
                    window.location.href = "supprimer-publication.php?id=" + postId;
                }
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
