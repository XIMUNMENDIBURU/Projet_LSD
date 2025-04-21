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
$id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 1; // Par défaut utilisateur 1 pour test

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

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texte = $conn->real_escape_string($_POST['texte']);
    $lieu = $conn->real_escape_string($_POST['lieu']);
    
    // Insertion dans la base de données
    if ($lieu_exists) {
        $sql_insert = "INSERT INTO publication (texte, lieu, id_user) VALUES ('$texte', '$lieu', $id_user)";
    } else {
        $sql_insert = "INSERT INTO publication (texte, id_user) VALUES ('$texte', $id_user)";
    }
    
    if ($conn->query($sql_insert) === TRUE) {
        $id_publication = $conn->insert_id;
        
        // Traitement des images (si implémenté)
        // ...
        
        // Redirection vers la page portfolio avec un message de succès
        header("Location: portfolio.php?message=added");
        exit;
    } else {
        $error_message = "Erreur lors de l'ajout : " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Publication - LSD.com</title>
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
                <span>lsd.com/ajouter-publication.php</span>
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
                    <textarea id="texte" name="texte" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="lieu">Lieu :</label>
                    <input type="text" id="lieu" name="lieu" required>
                </div>
                
                <div class="image-preview">
                    <div class="placeholder-image">
                        <i class="fas fa-image"></i>
                    </div>
                    <div class="placeholder-image">
                        <i class="fas fa-image"></i>
                    </div>
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
