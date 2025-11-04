<?php
require_once 'config/database.php';
require_once 'classes/UserAuth.php';
require_once 'classes/ElectionManager.php';
require_once 'upload_handler.php';

// Start session before creating UserAuth instance
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$auth = new UserAuth();
$electionManager = new ElectionManager();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_party':
                $election_id = $_POST['election_id'];
                $party_name = trim($_POST['party_name']);
                $party_description = trim($_POST['party_description']);
                if (empty($election_id) || empty($party_name)) {
                    $message = 'Election and party name are required.';
                    $messageType = 'error';
                } else {
                    $result = $electionManager->addParty($election_id, $party_name, $party_description ?: null);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;
            case 'delete_party':
                $party_id = $_POST['party_id'];
                $result = $electionManager->deleteParty($party_id);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
            case 'add_candidate':
                $election_id = $_POST['election_id'];
                $first_name = trim($_POST['first_name']);
                $middle_name = trim($_POST['middle_name']);
                $last_name = trim($_POST['last_name']);
                $suffix = trim($_POST['suffix']);
                $description = trim($_POST['description']);
                $position = trim($_POST['position']);
                $party = trim($_POST['party']);
                $photo = '';
                
                // Handle file upload
                if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload_result = handleFileUpload($_FILES['photo_file']);
                    if ($upload_result['success']) {
                        $photo = $upload_result['url'];
                    } else {
                        $message = $upload_result['message'];
                        $messageType = 'error';
                        break;
                    }
                }
                
                if (empty($first_name) || empty($last_name) || empty($position) || empty($election_id)) {
                    $message = 'First name, last name, position, and election are required fields.';
                    $messageType = 'error';
                } else {
                    // Check if candidate with same name already exists in this election
                    if ($electionManager->candidateNameExists($election_id, $first_name, $last_name)) {
                        $message = 'A candidate with the name "' . htmlspecialchars($first_name . ' ' . $last_name) . '" already exists in this election.';
                        $messageType = 'error';
                    } else {
                        $result = $electionManager->addCandidate($election_id, $first_name, $middle_name, $last_name, $suffix, $description, $position, $party, $photo);
                        $message = $result['message'];
                        $messageType = $result['success'] ? 'success' : 'error';
                    }
                }
                break;
                
            case 'update_candidate':
                $candidate_id = $_POST['candidate_id'];
                $first_name = trim($_POST['first_name']);
                $middle_name = trim($_POST['middle_name']);
                $last_name = trim($_POST['last_name']);
                $suffix = trim($_POST['suffix']);
                $description = trim($_POST['description']);
                $position = trim($_POST['position']);
                $party = trim($_POST['party']);
                $photo = '';
                
                // Handle file upload
                if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload_result = handleFileUpload($_FILES['photo_file']);
                    if ($upload_result['success']) {
                        $photo = $upload_result['url'];
                    } else {
                        $message = $upload_result['message'];
                        $messageType = 'error';
                        break;
                    }
                }
                
                if (empty($first_name) || empty($last_name) || empty($position)) {
                    $message = 'First name, last name, and position are required fields.';
                    $messageType = 'error';
                } else {
                    // Get the election_id for this candidate
                    $candidate_info = $electionManager->getCandidateById($candidate_id);
                    if ($candidate_info) {
                        $election_id = $candidate_info['election_id'];
                        
                        // Check if candidate with same name already exists in this election (excluding current candidate)
                        if ($electionManager->candidateNameExists($election_id, $first_name, $last_name, $candidate_id)) {
                            $message = 'A candidate with the name "' . htmlspecialchars($first_name . ' ' . $last_name) . '" already exists in this election.';
                            $messageType = 'error';
                        } else {
                            $result = $electionManager->updateCandidate($candidate_id, $first_name, $middle_name, $last_name, $suffix, $description, $position, $party, $photo);
                            $message = $result['message'];
                            $messageType = $result['success'] ? 'success' : 'error';
                        }
                    } else {
                        $message = 'Candidate not found.';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete_candidate':
                $candidate_id = $_POST['candidate_id'];
                $result = $electionManager->deleteCandidate($candidate_id);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Get all elections and candidates
$all_elections = $electionManager->getAllElections();
$selected_election_id = isset($_GET['election_id']) ? $_GET['election_id'] : (count($all_elections) > 0 ? $all_elections[0]['id'] : null);
$candidates = $selected_election_id ? $electionManager->getCandidatesByElection($selected_election_id) : [];
$parties = $selected_election_id ? $electionManager->getPartiesByElection($selected_election_id) : [];

// Predefined election types and positions
$election_types = [
    'ssg' => [
        'name' => 'Supreme Student Government (SSG)',
        'positions' => [
            'President',
            'Vice President',
            'Secretary',
            'Treasurer',
            'Auditor',
            'Public Information Officer',
            'Business Manager',
            'Senator'
        ]
    ],
    'department' => [
        'name' => 'Department Officers',
        'positions' => [
            'Department President',
            'Department Vice President',
            'Department Secretary',
            'Department Treasurer',
            'Department Representative'
        ]
    ],
    'class' => [
        'name' => 'Class Officers',
        'positions' => [
            'Class President',
            'Class Vice President',
            'Class Secretary',
            'Class Treasurer',
            'Class Representative'
        ]
    ],
    'organization' => [
        'name' => 'Organization Officers',
        'positions' => [
            'President',
            'Vice President',
            'Secretary',
            'Treasurer',
            'Public Relations Officer',
            'Event Coordinator'
        ]
    ],
    'custom' => [
        'name' => 'Custom Election',
        'positions' => []
    ]
];

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - Smart Vote</title>
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

        .back-btn {
            background: white;
            color: #1c2143;
        }

        .back-btn:hover {
            background: #f0f0f0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, #43c3dd, #004a94);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .content-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .content-card h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
            color: #1c2143;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input[type="file"] {
            padding: 0.5rem;
            border: 2px dashed #43c3dd;
            background: #f8f9fa;
            cursor: pointer;
        }

        .form-group input[type="file"]:hover {
            border-color: #004a94;
            background: #f0f8ff;
        }

        .image-preview {
            max-width: 200px;
            max-height: 200px;
            display: none;
            margin: 0.5rem 0;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #43c3dd;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 12px 24px;
            background: #004a94;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem 0.5rem 0.5rem 0;
        }

        .btn:hover {
            background: #003366;
        }

        .btn-secondary {
            background: #43c3dd;
        }

        .btn-secondary:hover {
            background: #3ab0d1;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.9rem;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
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

        .candidate-item {
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f9f9f9;
        }

        .candidate-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .candidate-name {
            font-weight: 600;
            color: #1c2143;
            font-size: 1.1rem;
        }

        .candidate-position {
            background: #43c3dd;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .candidate-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .candidate-actions {
            display: flex;
            gap: 0.5rem;
        }

        .election-selector {
            margin-bottom: 2rem;
            color: white
        }

        .election-selector select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
        }

        .position-templates {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .position-templates h4 {
            margin-bottom: 0.5rem;
            color: #1c2143;
        }

        .position-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .position-btn {
            padding: 4px 8px;
            background: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .position-btn:hover {
            background: #43c3dd;
            color: white;
        }


 /* Horizontal layout for party groups and candidates */
        #candidatesList {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .party-group {
            background: #ffffff;
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid #e6e6e6;
        }

        .party-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.05rem;
            color: #1c2143;
            margin: 0 0 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #43c3dd;
        }

        .party-count {
            font-size: 0.9rem;
            color: #666;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        /* Make candidates display horizontally in a scrollable row */
        .candidates-row {
            display: flex;
            flex-direction: row;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .candidates-row::-webkit-scrollbar {
            height: 8px;
        }

        .candidates-row::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .candidates-row::-webkit-scrollbar-thumb {
            background: #43c3dd;
            border-radius: 4px;
        }

        .candidates-row::-webkit-scrollbar-thumb:hover {
            background: #3ab0d1;
        }

        .candidate-item {
            flex: 0 0 280px;
            min-width: 280px;
            display: flex;
            flex-direction: column;
        }

        .candidate-details img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 1200px) {
            #candidatesList {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
            }

            .candidate-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .candidate-actions {
                flex-wrap: wrap;
            }

            /* On small screens, make party groups full width stacked vertically */
            #candidatesList {
                grid-template-columns: 1fr;
            }

            .candidate-item {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">Smart Vote - Admin</div>
            <div class="nav-buttons">
                <a href="admin_dashboard.php" class="nav-btn back-btn">Back to Dashboard</a>
                <a href="?logout=1" class="nav-btn logout-btn" onclick="return confirmLogout()">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Manage Candidates</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Election Selector -->
        <div class="election-selector">
            <label for="election_select">Select Election to Manage Candidates:</label>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <select id="election_select" onchange="handleElectionChange()" style="flex: 1;">
                    <option value="">Select an election...</option>
                    <?php foreach ($all_elections as $election): ?>
                        <option value="<?php echo $election['id']; ?>" 
                                <?php echo ($selected_election_id == $election['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($election['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <a href="admin_elections.php" class="btn btn-secondary">
                    Manage Elections
                </a>
            </div>
        </div>

        <!-- Manage Parties and Candidates Section -->
        <div id="manageCandidatesSection">
            <div class="content-grid">
            <div class="content-card">
                <h3>Manage Parties</h3>
                <?php if ($selected_election_id): ?>
                    <form method="POST" action="" style="margin-bottom: 1rem;">
                        <input type="hidden" name="action" value="add_party">
                        <input type="hidden" name="election_id" value="<?php echo $selected_election_id; ?>">
                        <div class="form-group">
                            <label for="party_name">Party Name *</label>
                            <input type="text" id="party_name" name="party_name" required placeholder="Enter party name">
                        </div>
                        <div class="form-group">
                            <label for="party_description">Party Description</label>
                            <textarea id="party_description" name="party_description" placeholder="Party description or platform (optional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Add Party</button>
                    </form>

                    <?php if (!empty($parties)): ?>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                            <h4 style="margin-bottom: 0.5rem; color: #1c2143;">Existing Parties</h4>
                            <?php foreach ($parties as $party): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($party['name']); ?></strong>
                                        <?php if (!empty($party['description'])): ?>
                                            <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($party['description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" onsubmit="return confirm('Delete this party? Candidates referencing it will keep the party name.');">
                                        <input type="hidden" name="action" value="delete_party">
                                        <input type="hidden" name="party_id" value="<?php echo $party['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="message info" style="background:#fff3cd;color:#856404;border:1px solid #ffeaa7;">No parties yet. Add one above.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;">
                        <p style="color: #666;">Please select an election first to manage parties.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="content-card">
                <h3>Add New Candidate</h3>
                <?php if ($selected_election_id): ?>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_candidate">
                        <input type="hidden" name="election_id" value="<?php echo $selected_election_id; ?>">
                        
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required placeholder="Enter first name">
                        </div>
                        
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" placeholder="Enter middle name (optional)">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required placeholder="Enter last name">
                        </div>
                        
                        <div class="form-group">
                            <label for="suffix">Suffix</label>
                            <input type="text" id="suffix" name="suffix" placeholder="Jr., Sr., III, etc. (optional)">
                        </div>
                        
                        <div style="background: #f8f9fa; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem;">
                            <p style="font-size: 0.8rem; color: #666; margin: 0;">
                                ⚠️ Candidate names (first + last) must be unique within each election
                            </p>
                        </div>
                        
                        <div class="form-group">
                            <label for="position">Position *</label>
                            <input type="text" id="position" name="position" required placeholder="Enter position or select from templates below">
                        </div>
                        
                        <div class="position-templates">
                            <h4>Position Templates:</h4>
                            <div class="position-buttons">
                                <span class="position-btn" onclick="setPosition('President')">President</span>
                                <span class="position-btn" onclick="setPosition('Vice President')">Vice President</span>
                                <span class="position-btn" onclick="setPosition('Secretary')">Secretary</span>
                                <span class="position-btn" onclick="setPosition('Treasurer')">Treasurer</span>
                                <span class="position-btn" onclick="setPosition('Auditor')">Auditor</span>
                                <span class="position-btn" onclick="setPosition('Public Information Officer')">PIO</span>
                                <span class="position-btn" onclick="setPosition('Business Manager')">Business Manager</span>
                                <span class="position-btn" onclick="setPosition('Senator')">Senator</span>
                                <span class="position-btn" onclick="setPosition('Representative')">Representative</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="party">Party/Affiliation</label>
                            <?php if (!empty($parties)): ?>
                                <select id="party" name="party">
                                    <option value="">Independent / None</option>
                                    <?php foreach ($parties as $party): ?>
                                        <option value="<?php echo htmlspecialchars($party['name']); ?>"><?php echo htmlspecialchars($party['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div style="background:#fff3cd;color:#856404;border:1px solid #ffeaa7;padding:0.75rem;border-radius:8px;">
                                    No parties found for this election. Add a party in the left panel first.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="Candidate's background, platform, etc."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="photo_file">Candidate Photo</label>
                            <input type="file" id="photo_file" name="photo_file" accept="image/*" style="margin-bottom: 0.5rem;">
                            <p style="font-size: 0.8rem; color: #666; margin-bottom: 0.5rem;">
                                Upload an image file (JPEG, PNG, GIF) - Max 5MB
                            </p>
                            <img id="photo_preview" class="image-preview">
                        </div>
                        
                        <button type="submit" class="btn btn-success" <?php echo empty($parties) ? 'disabled' : ''; ?>>Add Candidate</button>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;">
                        <p style="color: #666; margin-bottom: 1rem;">
                            <?php if (empty($all_elections)): ?>
                                No elections found. Create your first election to start adding candidates.
                            <?php else: ?>
                                Please select an election first to add candidates.
                            <?php endif; ?>
                        </p>
                        <?php if (empty($all_elections)): ?>
                            <a href="admin_elections.php" class="btn btn-success">
                                Create Your First Election
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            </div>
            
            <!-- Current Candidates Section - Full Width -->
            <div class="content-card-wide" style="margin-top: 2rem;">
                <h3>Current Candidates</h3>
                <?php if ($selected_election_id && !empty($candidates)): ?>
                    
                    <div id="candidatesList">
                        <?php
                        // Group candidates by party (use "Independent" when empty)
                        $groupedCandidates = [];
                        foreach ($candidates as $candidate) {
                            $partyKey = !empty($candidate['party']) ? $candidate['party'] : 'Independent';
                            if (!isset($groupedCandidates[$partyKey])) {
                                $groupedCandidates[$partyKey] = [];
                            }
                            $groupedCandidates[$partyKey][] = $candidate;
                        }
                        ?>
                        
                        <?php foreach ($groupedCandidates as $partyName => $group): ?>
                            <div class="party-group">
                                <div class="party-title">
                                    <div><?php echo htmlspecialchars($partyName); ?></div>
                                    <div class="party-count"><?php echo count($group); ?></div>
                                </div>
                                <div class="candidates-row">
                                    <?php foreach ($group as $candidate): ?>
                                        <div class="candidate-item" data-party="<?php echo htmlspecialchars($partyName); ?>">
                                            <div class="candidate-header">
                                                <div class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                                                <div class="candidate-position"><?php echo htmlspecialchars($candidate['position']); ?></div>
                                            </div>
                                            <div class="candidate-details">
                                                <?php if ($candidate['photo']): ?>
                                                    <div>
                                                        <img src="<?php echo htmlspecialchars($candidate['photo']); ?>" alt="photo">
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($candidate['party']): ?>
                                                    <div><strong>Party:</strong> <?php echo htmlspecialchars($candidate['party']); ?></div>
                                                <?php endif; ?>
                                                <?php if ($candidate['description']): ?>
                                                    <div><strong>Description:</strong> <?php echo htmlspecialchars($candidate['description']); ?></div>
                                                <?php endif; ?>
                                                <div><strong>Status:</strong> <?php echo ucfirst($candidate['status']); ?></div>
                                            </div>
                                            <div class="candidate-actions" style="margin-top: 0.75rem;">
                                                <button class="btn btn-secondary btn-small" onclick='editCandidate(<?php echo htmlspecialchars(json_encode($candidate)); ?>)'>Edit</button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this candidate?')">
                                                    <input type="hidden" name="action" value="delete_candidate">
                                                    <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($selected_election_id): ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        No candidates found for this election.
                    </p>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        Please select an election to view candidates.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Candidate Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 15px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
            <h3 style="margin-bottom: 1rem; color: #1c2143;">Edit Candidate</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_candidate">
                <input type="hidden" name="candidate_id" id="edit_candidate_id">
                
                <div class="form-group">
                    <label for="edit_first_name">First Name *</label>
                    <input type="text" id="edit_first_name" name="first_name" required placeholder="Enter first name">
                </div>
                
                <div class="form-group">
                    <label for="edit_middle_name">Middle Name</label>
                    <input type="text" id="edit_middle_name" name="middle_name" placeholder="Enter middle name (optional)">
                </div>
                
                <div class="form-group">
                    <label for="edit_last_name">Last Name *</label>
                    <input type="text" id="edit_last_name" name="last_name" required placeholder="Enter last name">
                </div>
                
                <div class="form-group">
                    <label for="edit_suffix">Suffix</label>
                    <input type="text" id="edit_suffix" name="suffix" placeholder="Jr., Sr., III, etc. (optional)">
                </div>
                
                <div style="background: #f8f9fa; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem;">
                    <p style="font-size: 0.8rem; color: #666; margin: 0;">
                        ⚠️ Candidate names (first + last) must be unique within each election
                    </p>
                </div>
                
                <div class="form-group">
                    <label for="edit_position">Position *</label>
                    <input type="text" id="edit_position" name="position" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_party">Party/Affiliation</label>
                    <input type="text" id="edit_party" name="party">
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_photo_file">Candidate Photo</label>
                    <input type="file" id="edit_photo_file" name="photo_file" accept="image/*" style="margin-bottom: 0.5rem;">
                    <p style="font-size: 0.8rem; color: #666; margin-bottom: 0.5rem;">
                        Upload an image file (JPEG, PNG, GIF) - Max 5MB
                    </p>
                    <img id="edit_photo_preview" class="image-preview">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Candidate</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function setPosition(position) {
            document.getElementById('position').value = position;
        }

        function handleElectionChange() {
            const select = document.getElementById('election_select');
            if (select.value) {
                window.location.href = '?election_id=' + select.value;
            }
        }

        function editCandidate(candidate) {
            document.getElementById('edit_candidate_id').value = candidate.id;
            document.getElementById('edit_first_name').value = candidate.first_name || '';
            document.getElementById('edit_middle_name').value = candidate.middle_name || '';
            document.getElementById('edit_last_name').value = candidate.last_name || '';
            document.getElementById('edit_suffix').value = candidate.suffix || '';
            document.getElementById('edit_position').value = candidate.position;
            document.getElementById('edit_party').value = candidate.party || '';
            document.getElementById('edit_description').value = candidate.description || '';
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function sortAdminCandidates() {
            const sortBy = document.getElementById('adminSortBy').value;
            const candidatesList = document.getElementById('candidatesList');
            const candidateItems = Array.from(candidatesList.querySelectorAll('.candidate-item'));
            
            candidateItems.sort((a, b) => {
                const nameA = a.querySelector('.candidate-name').textContent.trim();
                const nameB = b.querySelector('.candidate-name').textContent.trim();
                const positionA = a.querySelector('.candidate-position').textContent.trim();
                const positionB = b.querySelector('.candidate-position').textContent.trim();
                
                // Get party and status from the details section
                const detailsA = a.querySelector('.candidate-details').textContent;
                const detailsB = b.querySelector('.candidate-details').textContent;
                const partyA = detailsA.includes('Party:') ? detailsA.split('Party:')[1].split('\n')[0].trim() : '';
                const partyB = detailsB.includes('Party:') ? detailsB.split('Party:')[1].split('\n')[0].trim() : '';
                const statusA = detailsA.includes('Status:') ? detailsA.split('Status:')[1].trim() : '';
                const statusB = detailsB.includes('Status:') ? detailsB.split('Status:')[1].trim() : '';
                
                switch(sortBy) {
                    case 'name':
                        return nameA.localeCompare(nameB);
                    case 'name-desc':
                        return nameB.localeCompare(nameA);
                    case 'position':
                        return positionA.localeCompare(positionB);
                    case 'party':
                        return partyA.localeCompare(partyB);
                    case 'status':
                        return statusA.localeCompare(statusB);
                    case 'created':
                        // For date sorting, we'd need to add data attributes
                        return 0; // Keep original order for now
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted candidates
            candidateItems.forEach(item => candidatesList.appendChild(item));
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // File upload preview functionality
        function setupFilePreview(inputId, previewId) {
            const fileInput = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            if (fileInput && preview) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
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
            }
        }

        // Check for duplicate candidate names
        function checkDuplicateName(inputElement, electionId) {
            const name = inputElement.value.trim();
            if (name.length < 2) return;
            
            // You could add AJAX call here to check in real-time
            // For now, we'll rely on server-side validation
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Setup file previews
            setupFilePreview('photo_file', 'photo_preview');
            setupFilePreview('edit_photo_file', 'edit_photo_preview');
            
            // Add name validation
            const nameInput = document.getElementById('name');
            if (nameInput) {
                nameInput.addEventListener('blur', function() {
                    checkDuplicateName(this, <?php echo $selected_election_id ?: 'null'; ?>);
                });
            }
        });

        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>
