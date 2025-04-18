<?php
session_start();

// Redirect if email is not set
if (!isset($_SESSION['email']) || !isset($_SESSION['verification_code'])) {
    header("Location: index.php");
    exit;
}

$message = "";
$error = false;

if (isset($_POST['submit_code'])) {
    $entered_code = trim($_POST['verification_code']);
    
    if ($entered_code === $_SESSION['verification_code']) {
        // Code is correct, mark as verified
        $_SESSION['verified'] = true;
        
        // Redirect to review form
        header("Location: review_form.php");
        exit;
    } else {
        // Code is incorrect, generate a new one
        $error = true;
        $_SESSION['verification_code'] = sprintf("%06d", mt_rand(1, 999999));
        $message = "Code incorrect. Un nouveau code a été généré: <strong>{$_SESSION['verification_code']}</strong>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification du code</title>
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
            max-width: 600px;
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
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
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
            padding: 10px;
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
        <h1>Vérification du code</h1>
        
        <div class="email-display">
            Code envoyé à : <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $error ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php else: ?>
            <div class="message success">
                Un code de vérification a été envoyé à votre adresse email.<br>
                Pour cet exemple, le code est : <strong><?php echo $_SESSION['verification_code']; ?></strong>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="verification_code">Entrez le code de vérification :</label>
                <input type="text" id="verification_code" name="verification_code" required>
            </div>
            
            <button type="submit" name="submit_code">Vérifier</button>
        </form>
        
        <a href="index.php" class="back-link">Retour à la page précédente</a>
    </div>
</body>
</html>