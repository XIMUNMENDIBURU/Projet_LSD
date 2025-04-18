<?php
session_start();
require_once 'db_connect.php';

// Redirect if not verified
if (!isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
    header("Location: index.php");
    exit;
}

$message = "";
$success = false;

if (isset($_POST['submit_review'])) {
    // Get form data
    $nom = filter_var($_POST['nom'], FILTER_SANITIZE_STRING);
    $prenom = filter_var($_POST['prenom'], FILTER_SANITIZE_STRING);
    $rating = intval($_POST['rating']);
    $texte = filter_var($_POST['texte'], FILTER_SANITIZE_STRING);
    $email = $_SESSION['email']; // Get email from session
    
    // Validate data
    if (empty($nom) || empty($prenom) || empty($texte) || $rating < 1 || $rating > 5) {
        $message = "Veuillez remplir tous les champs correctement.";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Check if user exists with this email
            $stmt = $conn->prepare("SELECT id_user FROM user WHERE adresse_mail = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // User exists, get id
                $row = $result->fetch_assoc();
                $id_user = $row['id_user'];
            } else {
                // Create new user
                $stmt = $conn->prepare("INSERT INTO user (nom, prenom, adresse_mail) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $nom, $prenom, $email);
                $stmt->execute();
                $id_user = $conn->insert_id;
            }
            
            // Insert review
            $stmt = $conn->prepare("INSERT INTO avis (texte, id_user) VALUES (?, ?)");
            $stmt->bind_param("si", $texte, $id_user);
            $stmt->execute();
            $id_avis = $conn->insert_id;
            
            // Handle image uploads if any
            if (!empty($_FILES['images']['name'][0])) {
                // Create a publication for the images
                $stmt = $conn->prepare("INSERT INTO publication (texte, id_user) VALUES (?, ?)");
                $publication_text = "Images pour avis #" . $id_avis;
                $stmt->bind_param("si", $publication_text, $id_user);
                $stmt->execute();
                $id_publication = $conn->insert_id;
                
                // Process each uploaded image
                $upload_dir = "uploads/";
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === 0) {
                        $file_type = $_FILES['images']['type'][$key];
                        $file_size = $_FILES['images']['size'][$key];
                        
                        // Validate file type and size
                        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                            $file_name = uniqid() . '_' . $_FILES['images']['name'][$key];
                            $file_path = $upload_dir . $file_name;
                            
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $file_path)) {
                                // Save image path to database
                                $stmt = $conn->prepare("INSERT INTO image (chemin, id_publication) VALUES (?, ?)");
                                $stmt->bind_param("si", $file_path, $id_publication);
                                $stmt->execute();
                            }
                        }
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $success = true;
            $message = "Votre avis a été enregistré avec succès. Merci pour votre contribution !";
            
            // Clear session data
            unset($_SESSION['email']);
            unset($_SESSION['verification_code']);
            unset($_SESSION['verified']);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "Une erreur est survenue lors de l'enregistrement de votre avis. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire d'avis</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 100%;
        }
        button:hover {
            background-color: #2980b9;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            width: 40px;
            height: 40px;
            margin: 0;
            padding: 0;
            font-size: 30px;
            color: #ddd;
        }
        .star-rating label:before {
            content: '\f005';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
        }
        .star-rating input:checked ~ label {
            color: #ffcc00;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffcc00;
        }
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .preview-item {
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .email-display {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .email-display strong {
            color: #3498db;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Formulaire d'avis</h1>
        
        <?php if ($success): ?>
            <div class="message success">
                <?php echo $message; ?>
                <p><a href="index.php" class="back-link">Retour à l'accueil</a></p>
            </div>
        <?php else: ?>
            <?php if (!empty($message)): ?>
                <div class="message error">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="email-display">
                Email vérifié : <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong>
            </div>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" id="nom" name="nom" required>
                </div>
                
                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" id="prenom" name="prenom" required>
                </div>
                
                <div class="form-group">
                    <label>Note :</label>
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" required />
                        <label for="star5" title="5 étoiles"></label>
                        <input type="radio" id="star4" name="rating" value="4" />
                        <label for="star4" title="4 étoiles"></label>
                        <input type="radio" id="star3" name="rating" value="3" />
                        <label for="star3" title="3 étoiles"></label>
                        <input type="radio" id="star2" name="rating" value="2" />
                        <label for="star2" title="2 étoiles"></label>
                        <input type="radio" id="star1" name="rating" value="1" />
                        <label for="star1" title="1 étoile"></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="texte">Commentaire :</label>
                    <textarea id="texte" name="texte" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="images">Images (optionnel) :</label>
                    <input type="file" id="images" name="images[]" multiple accept="image/*">
                    <div class="image-preview" id="imagePreview"></div>
                </div>
                
                <button type="submit" name="submit_review">Soumettre l'avis</button>
            </form>
            
            <a href="index.php" class="back-link">Annuler et retourner à l'accueil</a>
        <?php endif; ?>
    </div>
    
    <script>
        // Image preview functionality
        document.getElementById('images').addEventListener('change', function(event) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            const files = event.target.files;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                if (!file.type.match('image.*')) {
                    continue;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    
                    div.appendChild(img);
                    preview.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            }
        });
        
        // Star rating hover effect
        const stars = document.querySelectorAll('.star-rating label');
        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                this.classList.add('hover');
            });
            star.addEventListener('mouseout', function() {
                this.classList.remove('hover');
            });
        });
    </script>
</body>
</html>
