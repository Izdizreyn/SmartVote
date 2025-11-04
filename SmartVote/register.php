<?php
require_once 'classes/UserAuth.php';

$auth = new UserAuth();
$message = '';
$message_type = '';

if ($_POST && isset($_POST['register'])) {
    require_once 'upload_handler.php';
    
    $voter_id = trim($_POST['voter_id']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $profile_picture = null;
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleFileUpload($_FILES['profile_picture']);
        if ($upload_result['success']) {
            $profile_picture = $upload_result['url'];
        } else {
            $message = $upload_result['message'];
            $message_type = 'error';
        }
    } else {
        $message = 'Profile picture is required';
        $message_type = 'error';
    }
    
    if (!$message) { // Only proceed if no upload error
        if (empty($voter_id) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            $message = 'Please fill in all required fields';
            $message_type = 'error';
        } elseif ($password !== $confirm_password) {
            $message = 'Passwords do not match';
            $message_type = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters long';
            $message_type = 'error';
        } else {
            $result = $auth->register($voter_id, $first_name, $middle_name, $last_name, $suffix, $email, $phone, $password, $profile_picture);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Show success message and redirect after 3 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php?registered=1';
                    }, 3000);
                </script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Vote</title>
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
            padding: 2rem 0;
        }

        .register-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input[type="file"] {
            padding: 8px;
            border: 2px dashed #43c3dd;
            background: #f8f9fa;
            cursor: pointer;
        }

        .image-preview {
            max-width: 150px;
            max-height: 150px;
            display: none;
            margin: 0.5rem auto;
            border-radius: 50%;
            border: 3px solid #43c3dd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #43c3dd;
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
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
            animation: slideIn 0.5s ease-out;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-notification {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }

        .success-notification::before {
            content: "✓ ";
            font-weight: bold;
            color: #28a745;
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

        .required {
            color: #e74c3c;
        }

        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">← Back to Home</a>
    
    <div class="register-container">
        <div class="logo">
            <h1>Smart Vote</h1>
            <p>Create Your Student Voter Account</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="voter_id">Student ID <span class="required">*</span></label>
                <input type="text" id="voter_id" name="voter_id" 
                       placeholder="Enter your Student ID" 
                       value="<?php echo isset($_POST['voter_id']) ? htmlspecialchars($_POST['voter_id']) : ''; ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" 
                           placeholder="Enter your first name" 
                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" 
                           placeholder="Enter your middle name (optional)" 
                           value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" id="last_name" name="last_name" 
                           placeholder="Enter your last name" 
                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="suffix">Suffix</label>
                    <select id="suffix" name="suffix">
                        <option value="">Select suffix (optional)</option>
                        <option value="Jr." <?php echo (isset($_POST['suffix']) && $_POST['suffix'] == 'Jr.') ? 'selected' : ''; ?>>Jr.</option>
                        <option value="Sr." <?php echo (isset($_POST['suffix']) && $_POST['suffix'] == 'Sr.') ? 'selected' : ''; ?>>Sr.</option>
                        <option value="II" <?php echo (isset($_POST['suffix']) && $_POST['suffix'] == 'II') ? 'selected' : ''; ?>>II</option>
                        <option value="III" <?php echo (isset($_POST['suffix']) && $_POST['suffix'] == 'III') ? 'selected' : ''; ?>>III</option>
                        <option value="IV" <?php echo (isset($_POST['suffix']) && $_POST['suffix'] == 'IV') ? 'selected' : ''; ?>>IV</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" 
                           placeholder="your.email@example.com" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="09123456789" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="profile_picture">Profile Picture <span class="required">*</span></label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required>
                <p style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                    Upload a clear photo of yourself (JPEG, PNG, GIF) - Max 5MB
                </p>
                <img id="profile_preview" class="image-preview">
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" 
                       placeholder="Enter your password (min 6 characters)" 
                       minlength="6" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       placeholder="Confirm your password" 
                       minlength="6" required>
            </div>

            <button type="submit" name="register" class="btn">Register Account</button>
        </form>

        <div class="links">
            <a href="login.php">Already have an account? Login</a>
            <br><br>
            <a href="index.php">Back to Home</a>
        </div>
    </div>

    <script>
        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('profile_preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>
