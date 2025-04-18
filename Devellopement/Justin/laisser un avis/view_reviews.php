<?php
require_once 'db_connect.php';

// Get all reviews with user information
$query = "SELECT a.id_avis, a.texte, u.nom, u.prenom, u.adresse_mail, 
          (SELECT GROUP_CONCAT(i.chemin) FROM image i 
           JOIN publication p ON i.id_publication = p.id_publication 
           WHERE p.texte LIKE CONCAT('Images pour avis #', a.id_avis, '%')) AS images
          FROM avis a
          JOIN user u ON a.id_user = u.id_user
          ORDER BY a.id_avis DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les avis</title>
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
            max-width: 1000px;
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
        .reviews {
            display: grid;
            gap: 20px;
        }
        .review {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .user-info {
            font-weight: bold;
        }
        .review-content {
            margin-bottom: 15px;
        }
        .review-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .review-image {
            width: 150px;
            height: 150px;
            border-radius: 4px;
            object-fit: cover;
            cursor: pointer;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 80%;
            max-height: 80%;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .add-review-btn {
            display: block;
            width: 200px;
            margin: 30px auto 0;
            padding: 12px 20px;
            background-color: #3498db;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .add-review-btn:hover {
            background-color: #2980b9;
        }
        .no-reviews {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tous les avis</h1>
        
        <div class="reviews">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="review">
                        <div class="review-header">
                            <div class="user-info">
                                <?php echo htmlspecialchars($row['prenom'] . ' ' . $row['nom']); ?>
                            </div>
                        </div>
                        <div class="review-content">
                            <?php echo nl2br(htmlspecialchars($row['texte'])); ?>
                        </div>
                        
                        <?php if (!empty($row['images'])): ?>
                            <div class="review-images">
                                <?php 
                                $image_paths = explode(',', $row['images']);
                                foreach ($image_paths as $path): 
                                    if (!empty($path)):
                                ?>
                                    <img src="<?php echo htmlspecialchars($path); ?>" class="review-image" onclick="openModal(this.src)">
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-reviews">
                    Aucun avis n'a été publié pour le moment.
                </div>
            <?php endif; ?>
        </div>
        
        <a href="index.php" class="add-review-btn">Laisser un avis</a>
    </div>
    
    <!-- Modal for image preview -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImg">
    </div>
    
    <script>
        // Image modal functionality
        function openModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImg');
            modal.style.display = "flex";
            modalImg.src = src;
        }
        
        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
        }
        
        // Close modal when clicking outside the image
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>