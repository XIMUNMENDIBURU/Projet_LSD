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

// Vérifier si la colonne lieu existe
$lieu_exists = false;
$result = $conn->query("SHOW COLUMNS FROM publication LIKE 'lieu'");
if ($result && $result->num_rows > 0) {
    $lieu_exists = true;
}

// Si la colonne lieu n'existe pas, essayer de la créer
if (!$lieu_exists) {
    $conn->query("ALTER TABLE publication ADD COLUMN lieu VARCHAR(255)");
    // Vérifier à nouveau si la colonne a été créée
    $result = $conn->query("SHOW COLUMNS FROM publication LIKE 'lieu'");
    if ($result && $result->num_rows > 0) {
        $lieu_exists = true;
    }
}

// Requête pour récupérer les informations de la publication
$sql_publication = "SELECT * FROM publication WHERE id_publication = $id_publication";
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
        'lieu' => 'Bayonne'
    ],
    2 => [
        'id_publication' => 2,
        'texte' => 'Mise en place d\'un tuyau de canalisation pour remplacer une canalisation défectueuse',
        'lieu' => 'Biarritz'
    ],
    3 => [
        'id_publication' => 3,
        'texte' => 'Mise en place d\'un tuyau pour chauffage central',
        'lieu' => 'Anglet'
    ],
    4 => [
        'id_publication' => 4,
        'texte' => 'Mise en place d\'un tuyau de ventilation cuisine professionnelle',
        'lieu' => 'Saint-Jean-de-Luz'
    ],
    5 => [
        'id_publication' => 5,
        'texte' => 'Mise en place d\'un tuyau d\'irrigation automatique pour un jardin',
        'lieu' => 'Hendaye'
    ],
    6 => [
        'id_publication' => 6,
        'texte' => 'Mise en place d\'un tuyau de drainage sous-sol',
        'lieu' => 'Bidart'
    ]
];

// Si la publication n'est pas trouvée dans la base de données, utiliser les données fictives
if (!$publication && isset($fake_publications[$id_publication])) {
    $publication = $fake_publications[$id_publication];
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texte = $conn->real_escape_string($_POST['texte']);
    $lieu = $conn->real_escape_string($_POST['lieu']);
    
    // Mise à jour dans la base de données
    if ($lieu_exists) {
        $sql_update = "UPDATE publication SET texte = '$texte', lieu = '$lieu' WHERE id_publication = $id_publication";
    } else {
        $sql_update = "UPDATE publication SET texte = '$texte' WHERE id_publication = $id_publication";
    }
    
    if ($conn->query($sql_update) === TRUE) {
        // Traitement des images (si implémenté)
        // ...
        
        // Redirection vers la page de la publication avec un message de succès
        header("Location: publication.php?id=$id_publication&message=updated");
        exit;
    } else {
        $error_message = "Erreur lors de la mise à jour : " . $conn->error;
    }
}

// Extraire le texte et le lieu
$texte = isset($publication['texte']) ? $publication['texte'] : '';
$lieu = isset($publication['lieu']) ? $publication['lieu'] : 'Bidart';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Publication - LSD.com</title>
    <link rel="stylesheet" href="styles_desktop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group textarea {
            height: 100px;
        }
        .image-preview {
            display: flex;
            margin-bottom: 20px;
        }
        .image-preview .placeholder-image {
            width: 150px;
            height: 150px;
            margin-right: 10px;
            background-color: #f0f0f0;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .add-photo-button {
            display: inline-block;
            padding: 8px 15px;
            background-color: #f0f0f0;
            color: #333;
            border-radius: 20px;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .submit-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-button:hover {
            background-color: #45a049;
        }
        .notification {
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="browser-container">
        <div class="browser-header">
            <div class="browser-controls">
                <div class="browser-button back"><i class="fas fa-arrow-left"></i></div>
                <div class="browser-button forward"><i class="fas fa-arrow-right"></i></div>
                <div class="browser-button refresh"><i class="fas fa-redo-alt"></i></div>
                <div class="browser-button home"><i class="fas fa-home"></i></div>
            </div>
            <div class="browser-address-bar">
                <span class="browser-protocol">https://</span>
                <span>lsd.com/modifier-publication.php?id=<?php echo $id_publication; ?></span>
            </div>
            <div class="browser-search">
                <i class="fas fa-search"></i>
            </div>
        </div>
        
        <div class="browser-content">
            <div class="portfolio-header">
                <div class="portfolio-title">POST</div>
            </div>
            
            <?php if (isset($error_message)): ?>
            <div class="notification error">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="post" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="texte">Post :</label>
                    <textarea id="texte" name="texte" required><?php echo $texte; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="lieu">Lieu :</label>
                    <input type="text" id="lieu" name="lieu" value="<?php echo $lieu; ?>" required>
                </div>
                
                <div class="image-preview">
                    <?php 
                    if ($result_images && $result_images->num_rows > 0) {
                        while ($image = $result_images->fetch_assoc()) {
                            echo '<div class="placeholder-image">';
                            echo '<img src="' . $image['chemin'] . '" alt="Image de la publication" style="max-width: 100%; max-height: 100%;">';
                            echo '</div>';
                        }
                    } else {
                        // Afficher des placeholders si aucune image n'est trouvée
                    ?>
                        <div class="placeholder-image">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="placeholder-image">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php
                    }
                    ?>
                </div>
                
                <a href="#" class="add-photo-button">Ajouter une photo</a>
                
                <div>
                    <button type="submit" class="submit-button">Valider</button>
                </div>
            </form>
            
            <div class="help-icon">
                <i class="fas fa-question-circle"></i>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>