<?php
require_once 'classes/UserAuth.php';
require_once 'classes/ElectionManager.php';

$auth = new UserAuth();
$electionManager = new ElectionManager();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $auth->getCurrentUserId();
$user_info = $auth->getUserInfo($user_id);
$elections_for_voting = $electionManager->getElectionsForVoting();
$voting_history = $auth->getAllVotingHistory($user_id);

// Handle profile update
$update_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    require_once 'upload_handler.php';
    
    $profile_picture = null;
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleFileUpload($_FILES['profile_picture']);
        if ($upload_result['success']) {
            $profile_picture = $upload_result['url'];
        } else {
            $update_message = '<div class="message error">' . $upload_result['message'] . '</div>';
        }
    }
    
    if (!$update_message) { // Only proceed if no upload error
        $result = $auth->updateUser(
            $user_id,
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['suffix'],
            $_POST['email'],
            $_POST['phone'],
            $profile_picture
        );
        
        if ($result['success']) {
            $update_message = '<div class="message success">' . $result['message'] . '</div>';
            // Refresh user info
            $user_info = $auth->getUserInfo($user_id);
        } else {
            $update_message = '<div class="message error">' . $result['message'] . '</div>';
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - Smart Vote</title>
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
        }

        .navbar {
            background: #43c3dd;
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
        }

        .nav-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .nav-btn {
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .logout-btn {
            background: #1c2143;
            color: white;
        }

        .logout-btn:hover {
            background: white;
            color: #1c2143;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-card {
            background: linear-gradient(135deg, #43c3dd, #004a94);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .welcome-card h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .elections-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .election-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .election-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .election-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.2rem;
            color: #1c2143;
            margin-bottom: 0.8rem;
            line-height: 1.3;
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .candidate-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .candidate-card:hover {
            border-color: #43c3dd;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(67, 195, 221, 0.2);
        }

        .candidate-card.selected {
            border-color: #004a94;
            background: #f0f8ff;
            box-shadow: 0 4px 16px rgba(0, 74, 148, 0.2);
        }

        .candidate-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1c2143;
            margin-bottom: 0.5rem;
        }

        .candidate-position {
            color: #43c3dd;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .candidate-party {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .candidate-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .vote-btn {
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
            margin-top: 1.5rem;
        }

        .vote-btn:hover {
            background: #003366;
        }

        .vote-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
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

        .no-elections {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            padding: 2rem;
        }

        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            margin-bottom: 0.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            width: 120px;
        }

        .info-value {
            color: #666;
        }

        .edit-profile-btn {
            background: #43c3dd;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 1rem;
        }

        .edit-profile-btn:hover {
            background: #3ab0d1;
        }

        .profile-form {
            display: none;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #43c3dd;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #004a94;
            color: white;
        }

        .btn-primary:hover {
            background: #003366;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .profile-picture-container {
            text-align: center;
            margin-bottom: 1rem;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #43c3dd;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .profile-picture:hover {
            transform: scale(1.05);
        }

        .profile-picture-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #43c3dd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            border: 3px solid #43c3dd;
            margin: 0 auto 1rem auto;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .profile-picture-placeholder:hover {
            transform: scale(1.05);
        }

        /* Small floating button for opening statistics */
        .stats-float-btn {
            position: fixed;
            right: 60px;
            top: 80px; /* sit just below the sticky navbar */
            background: #004a94;
            color: #ffffff;
            padding: 20px 20px;
            border-radius: 18px;
            font-size: 1.rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 6px 18px rgba(0, 74, 148, 0.35);
            z-index: 1100;
        }

        .stats-float-btn:hover {
            background: #003366;
        }

        .user-profile-layout {
            display: flex;
            align-items: flex-start;
            gap: 2rem;
            margin-bottom: 1rem;
        }

        .profile-picture-section {
            flex-shrink: 0;
        }

        .user-details-section {
            flex: 1;
        }

        .form-group input[type="file"] {
            padding: 0.5rem;
            border: 2px dashed #43c3dd;
            background: #f8f9fa;
            cursor: pointer;
            border-radius: 8px;
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

        @media (max-width: 1200px) {
            .elections-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .elections-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .elections-grid {
                grid-template-columns: 1fr;
            }
            
            .candidates-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }

            .user-profile-layout {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 1rem;
            }

            .profile-picture {
                width: 100px;
                height: 100px;
            }

            .profile-picture-placeholder {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">Smart Vote</div>
            <div class="nav-buttons">
                <a href="?logout=1" class="nav-btn logout-btn" onclick="return confirmLogout()">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h1>Welcome, <?php echo htmlspecialchars($user_info['full_name']); ?>!</h1>
            <?php echo $update_message; ?>
            <div class="user-info">
                <div class="user-profile-layout">
                    <div class="profile-picture-section">
                        <div class="profile-picture-container">
                            <?php if ($user_info['profile_picture']): ?>
                                <img src="<?php echo htmlspecialchars($user_info['profile_picture']); ?>" alt="Profile Picture" class="profile-picture" onclick="toggleEditForm()">
                            <?php else: ?>
                                <div class="profile-picture-placeholder" onclick="toggleEditForm()">
                                    <?php echo strtoupper(substr($user_info['first_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="user-details-section">
                        <div class="info-row">
                            <span class="info-label">Voter ID:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user_info['voter_id']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user_info['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user_info['phone'] ?: 'Not provided'); ?></span>
                        </div>
                        <button class="edit-profile-btn" onclick="toggleEditForm()">✏️ Edit Profile</button>
                    </div>
                </div>
                
                <div class="profile-form" id="profileForm">
                    <h3 style="margin-bottom: 1rem; color: #1c2143;">Edit Profile Information</h3>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_info['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($user_info['middle_name'] ?: ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_info['last_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="suffix">Suffix</label>
                            <input type="text" id="suffix" name="suffix" value="<?php echo htmlspecialchars($user_info['suffix'] ?: ''); ?>" placeholder="Jr., Sr., III, etc.">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_info['phone'] ?: ''); ?>" placeholder="Enter phone number">
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_picture">Profile Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="margin-bottom: 0.5rem;">
                            <p style="font-size: 0.8rem; color: #666; margin-bottom: 0.5rem;">
                                Upload an image file (JPEG, PNG, GIF) - Max 5MB
                            </p>
                            <img id="profile_preview" class="image-preview">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary" onclick="toggleEditForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <?php if (empty($elections_for_voting)): ?>
            <div class="election-card">
                <div class="no-elections">
                    <h2>No Elections Available</h2>
                    <p>There are currently no elections available for voting.</p>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: #999;">
                        Check back later or contact your administrator for upcoming elections.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="election-card" style="background: linear-gradient(135deg, #43c3dd, #004a94); color: white; margin-bottom: 2rem;">
                <h2 style="color: white; margin-bottom: 0.5rem;">Available Elections</h2>
                <p style="color: rgba(255,255,255,0.9);">
                    You can participate in <?php echo count($elections_for_voting); ?> election<?php echo count($elections_for_voting) > 1 ? 's' : ''; ?>:
                </p>
            </div>
            <div class="elections-grid">
                <?php foreach ($elections_for_voting as $election): ?>
                    <?php 
                    $candidates = $electionManager->getCandidatesByPosition($election['id']);
                    $has_voted = $auth->hasVoted($user_id, $election['id']);
                    $is_currently_active = (strtotime($election['start_date']) <= time() && strtotime($election['end_date']) >= time());
                    $election_status = $is_currently_active ? 'active' : 'upcoming';
                    ?>
                    <div class="election-card">
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                            <h2 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h2>
                            <div style="background: <?php echo $election_status == 'active' ? '#28a745' : '#ffc107'; ?>; color: <?php echo $election_status == 'active' ? 'white' : '#212529'; ?>; padding: 3px 8px; border-radius: 15px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">
                                <?php echo ucfirst($election_status); ?>
                            </div>
                        </div>
                        <?php if ($election['description']): ?>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.8rem; line-height: 1.3;"><?php echo htmlspecialchars($election['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem;">
                        <div style="font-size: 0.8rem;">
                            <div style="margin-bottom: 0.5rem;">
                                <strong style="color: #1c2143;">Start:</strong>
                                <span style="color: #666;"> <?php echo date('M j, Y', strtotime($election['start_date'])); ?></span>
                            </div>
                            <div>
                                <strong style="color: #1c2143;">End:</strong>
                                <span style="color: #666;"> <?php echo date('M j, Y', strtotime($election['end_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($has_voted): ?>
                        <div class="message success" style="margin-bottom: 1rem; padding: 0.8rem; font-size: 0.9rem;">
                            <strong>✓ Vote Recorded</strong><br>
                            <span style="font-size: 0.8rem;">Thank you for participating!</span>
                        </div>
                    <?php elseif (!$is_currently_active): ?>
                        <div class="message" style="background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; margin-bottom: 1rem; padding: 0.8rem; font-size: 0.9rem;">
                            <strong>⏰ Not Yet Started</strong><br>
                            <span style="font-size: 0.8rem;">Begins <?php echo date('M j', strtotime($election['start_date'])); ?></span>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; margin: 1rem 0;">
                            <a href="vote.php?election_id=<?php echo $election['id']; ?>" 
                               target="_blank" 
                               class="vote-btn" 
                               style="display: inline-block; text-decoration: none; padding: 10px 20px; background: #004a94; color: white; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: background 0.3s ease;">
                                🗳️ Vote Now
                            </a>
                        </div>
                    <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Live Statistics Shortcut (floating small button at right) -->
        <a href="voter_statistics.php" class="stats-float-btn">Open Statistics</a><br>

        <!-- Voting History Section -->
        <?php if (!empty($voting_history)): ?>
            <div class="election-card" style="background: linear-gradient(135deg, #43c3dd, #004a94); color: white; margin-bottom: 2rem;">
                <h2 style="color: white; margin-bottom: 0.5rem;">Your Voting History</h2>
                <p style="color: rgba(255,255,255,0.9);">
                    You have participated in <?php echo count(array_unique(array_column($voting_history, 'election_id'))); ?> election<?php echo count(array_unique(array_column($voting_history, 'election_id'))) > 1 ? 's' : ''; ?>:
                </p>
            </div>
            
            <div class="elections-grid">
                <?php 
                $grouped_history = [];
                foreach ($voting_history as $vote) {
                    $grouped_history[$vote['election_id']][] = $vote;
                }
                ?>
                <?php foreach ($grouped_history as $election_id => $election_votes): ?>
                    <?php 
                    $election_title = $election_votes[0]['election_title'];
                    $vote_date = $election_votes[0]['vote_timestamp'];
                    ?>
                    <div class="election-card" style="border-left: 4px solid #28a745;">
                        <div class="election-header">
                            <h3 style="color: #1c2143; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($election_title); ?></h3>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                                Voted on: <?php echo date('M j, Y g:i A', strtotime($vote_date)); ?>
                            </p>
                        </div>
                        
                        <div class="election-candidates" style="margin-bottom: 1rem;">
                            <h4 style="color: #1c2143; margin-bottom: 0.75rem; font-size: 1rem;">Your Votes:</h4>
                            <?php foreach ($election_votes as $vote): ?>
                                <div style="background: #f8f9fa; padding: 0.75rem; border-radius: 6px; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.75rem;">
                                    <?php if ($vote['photo']): ?>
                                        <img src="<?php echo htmlspecialchars($vote['photo']); ?>" 
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #43c3dd;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: #43c3dd; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.9rem;">
                                            <?php echo strtoupper(substr($vote['candidate_full_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #1c2143; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($vote['candidate_full_name']); ?>
                                        </div>
                                        <div style="color: #43c3dd; font-size: 0.8rem; font-weight: 500;">
                                            <?php echo htmlspecialchars($vote['position']); ?>
                                        </div>
                                        <?php if ($vote['party']): ?>
                                            <div style="color: #666; font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($vote['party']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 8px; font-size: 0.7rem; font-weight: 600;">
                                        ✓
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleEditForm() {
            const form = document.getElementById('profileForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
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

        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>
