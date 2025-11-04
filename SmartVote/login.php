<?php
require_once 'classes/UserAuth.php';

$auth = new UserAuth();
$message = '';
$message_type = '';

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    header('Location: voter_dashboard.php');
    exit();
}

if ($_POST && isset($_POST['login'])) {
    $voter_id = trim($_POST['voter_id']);
    $password = $_POST['password'];
    
    if (empty($voter_id) || empty($password)) {
        $message = 'Please fill in all fields';
        $message_type = 'error';
    } else {
        $result = $auth->login($voter_id, $password);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            header('Location: voter_dashboard.php');
            exit();
        }
    }
}

// Check if user was redirected from registration
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $message = 'Registration successful! Please login with your Student ID and password.';
    $message_type = 'success';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Vote</title>
    <link rel="icon" type="image/png" href="logo/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #1c2143;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #43c3dd;
            margin-bottom: 0.5rem;
        }

        .logo p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #43c3dd;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #004a94;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #003366;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .links {
            text-align: center;
            margin-top: 1.5rem;
        }

        .links a {
            color: #43c3dd;
            text-decoration: none;
            margin: 0 10px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        .back-home:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">← Back to Home</a>
    
    <div class="login-container">
        <div class="logo">
            <h1>Smart Vote</h1>
            <p>Student Login Portal</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="voter_id">Student ID</label>
                <input type="text" id="voter_id" name="voter_id" 
                       placeholder="Enter your Student ID" 
                       value="<?php echo isset($_POST['voter_id']) ? htmlspecialchars($_POST['voter_id']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" 
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" name="login" class="btn">Login</button>
        </form>

        <div class="links">
            <a href="register.php">Don't have an account? Register</a>
            <br><br>
            <a href="admin_login.php">Admin Login</a>
        </div>
    </div>
</body>
</html>
