// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Traitement du formulaire d'inscription
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupération des données du formulaire
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $entreprise = $_POST['entreprise'] ?? null;
        $siret = $_POST['siret'] ?? null;
        $code_naf = $_POST['code_naf'] ?? null;
        $email = $_POST['email'] ?? '';
        $mdp = $_POST['mdp'] ?? '';
        $metier = $_POST['metier'] ?? null;
        $loc_x = $_POST['loc_x'] ?? null;
        $loc_y = $_POST['loc_y'] ?? null;
        
        // Hachage du mot de passe
        $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
        
        // Insertion dans la base de données
        $sql = "INSERT INTO user (nom, prenom, entreprise, siret, code_naf, adresse_mail, mot_de_passe, metier, loc_x, loc_y) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            $nom, 
            $prenom, 
            $entreprise, 
            $siret, 
            $code_naf, 
            $email, 
            $mdp_hash, 
            $metier, 
            $loc_x, 
            $loc_y
        ]);
        
        // Récupération de l'ID généré
        $userId = $pdo->lastInsertId();
        
        if ($userId) {
            // Vérification que l'utilisateur a bien été créé
            $checkSql = "SELECT * FROM user WHERE id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$userId]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<div class='message success'>";
                echo "<h3>✅ Inscription réussie!</h3>";
                echo "<p>L'utilisateur a bien été enregistré dans la base de données.</p>";
                echo "<p><strong>ID:</strong> " . $userId . "</p>";
                echo "<p><strong>Nom:</strong> " . htmlspecialchars($user['nom']) . "</p>";
                echo "<p><strong>Prénom:</strong> " . htmlspecialchars($user['prenom']) . "</p>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($user['adresse_mail']) . "</p>";
                echo "</div>";
                
                if ($debug_mode) {
                    echo "<div class='debug-info'>";
                    echo "<h3>Informations de débogage</h3>";
                    echo "<pre>";
                    print_r($user);
                    echo "</pre>";
                    echo "</div>";
                }
            } else {
                echo "<div class='message error'>";
                echo "<h3>⚠️ Anomalie détectée</h3>";
                echo "<p>L'insertion a généré un ID ($userId) mais l'utilisateur n'a pas été trouvé lors de la vérification.</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='message error'>";
            echo "<h3>❌ Échec de l'inscription</h3>";
            echo "<p>Aucun ID n'a été généré. L'insertion a échoué.</p>";
            echo "</div>";
        }
    }
    
    // Affichage de la liste des utilisateurs
    $usersSql = "SELECT id, nom, prenom, adresse_mail FROM user ORDER BY id DESC";
    $usersStmt = $pdo->query($usersSql);
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Liste des utilisateurs enregistrés</h2>";
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($user['prenom']) . "</td>";
            echo "<td>" . htmlspecialchars($user['adresse_mail']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucun utilisateur trouvé dans la base de données.</p>";
    }
    
} catch (PDOException $e) {
    echo "<div class='message error'>";
    echo "<h3>❌ Erreur de base de données</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    
    if ($debug_mode) {
        echo "<div class='debug-info'>";
        echo "<h3>Détails de l'erreur</h3>";
        echo "<pre>";
        print_r($e);
        echo "</pre>";
        echo "</div>";
    }
}