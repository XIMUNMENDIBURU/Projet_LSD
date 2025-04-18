<?php
session_start();
require_once 'db_connect.php';

// Clear any existing session data
if (!isset($_POST['submit_email'])) {
    unset($_SESSION['email']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['verified']);
}

$message = "";

if (isset($_POST['submit_email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Store email in session
        $_SESSION['email'] = $email;
        
        // Generate a random 6-digit verification code
        $verification_code = sprintf("%06d", mt_rand(1, 999999));
        $_SESSION['verification_code'] = $verification_code;
        
        // In a real application, you would send this code via email
        // For this example, we'll just display it
        $message = "Un code de vérification a été généré: <strong>$verification_code</strong><br>
                   (Dans une application réelle, ce code serait envoyé par email)";
        
        // Redirect to verification page
        header("Location: verify_code.php");
        exit;
    } else {
        $message = "Veuillez entrer une adresse email valide.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laisser un avis</title>
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
        input[type="email"] {
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
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Laisser un avis</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'valide') !== false ? 'error' : ''; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email">Adresse email :</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <button type="submit" name="submit_email">Continuer</button>
        </form>
    </div>
</body>
</html>