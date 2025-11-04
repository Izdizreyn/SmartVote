<?php
require_once 'config/database.php';
require_once 'classes/UserAuth.php';
require_once 'classes/ElectionManager.php';

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
            case 'create_election':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $election_type = $_POST['election_type'];
                $custom_type_name = isset($_POST['custom_type_name']) ? trim($_POST['custom_type_name']) : '';
                
                // Validate custom election type if selected
                if ($election_type === 'custom' && empty($custom_type_name)) {
                    $message = 'Custom election type name is required when selecting Custom Election.';
                    $messageType = 'error';
                } elseif (empty($title) || empty($start_date) || empty($end_date)) {
                    $message = 'Title, start date, and end date are required fields.';
                    $messageType = 'error';
                } elseif (strtotime($start_date) >= strtotime($end_date)) {
                    $message = 'End date must be after start date.';
                    $messageType = 'error';
                } else {
                    // Add custom type to description if it's a custom election
                    if ($election_type === 'custom' && !empty($custom_type_name)) {
                        $description = "Election Type: " . $custom_type_name . "\n\n" . $description;
                    }
                    
                    $result = $electionManager->createElection($title, $description, $start_date, $end_date, $_SESSION['admin_id']);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;
                
            case 'update_election':
                $election_id = $_POST['election_id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                if (empty($title) || empty($start_date) || empty($end_date)) {
                    $message = 'Title, start date, and end date are required fields.';
                    $messageType = 'error';
                } elseif (strtotime($start_date) >= strtotime($end_date)) {
                    $message = 'End date must be after start date.';
                    $messageType = 'error';
                } else {
                    $result = $electionManager->updateElection($election_id, $title, $description, $start_date, $end_date);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;
                
            case 'delete_election':
                $election_id = $_POST['election_id'];
                $result = $electionManager->deleteElection($election_id);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Get all elections
$all_elections = $electionManager->getAllElections();
$election_types = $electionManager->getElectionTypes();

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
    <title>Manage Elections - Smart Vote</title>
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
            padding: 2rem;
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
            padding: 2rem;
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

        .election-item {
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f9f9f9;
        }

        .election-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .election-title {
            font-weight: 600;
            color: #1c2143;
            font-size: 1.1rem;
        }

        .election-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-upcoming {
            background: #fff3cd;
            color: #856404;
        }

        .status-ended {
            background: #f8d7da;
            color: #721c24;
        }

        .election-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .election-actions {
            display: flex;
            gap: 0.5rem;
        }

        .election-type-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .election-type-info h4 {
            margin-bottom: 0.5rem;
            color: #1c2143;
        }

        .type-description {
            color: #666;
            font-size: 0.9rem;
        }

        #custom_election_type {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border: 2px dashed #43c3dd;
            margin-bottom: 1rem;
        }

        #custom_election_type label {
            color: #1c2143;
            font-weight: 700;
        }

        #custom_election_type input {
            border: 2px solid #43c3dd;
            background: white;
        }

        #custom_election_type input:focus {
            border-color: #004a94;
            box-shadow: 0 0 0 3px rgba(67, 195, 221, 0.1);
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .election-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .election-actions {
                flex-wrap: wrap;
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
            <h1>Manage Elections</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="content-card">
                <h3>Create New Election</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_election">
                    
                    <div class="form-group">
                        <label for="title">Election Title *</label>
                        <input type="text" id="title" name="title" required placeholder="e.g., SSG Elections 2024">
                    </div>
                    
                    <div class="form-group">
                        <label for="election_type">Election Type</label>
                        <select id="election_type" name="election_type" onchange="showElectionTypeInfo()">
                            <?php foreach ($election_types as $key => $type): ?>
                                <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="custom_election_type" class="form-group" style="display: none;">
                        <label for="custom_type_name">Custom Election Type *</label>
                        <input type="text" id="custom_type_name" name="custom_type_name" placeholder="e.g., Student Council, Class Officers, etc.">
                        <p style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                            Specify what type of custom election this is
                        </p>
                    </div>
                    
                    <div id="election-type-info" class="election-type-info">
                        <h4>Supreme Student Government (SSG)</h4>
                        <div class="type-description">
                            Positions: President, Vice President, Secretary, Treasurer, Auditor, Public Information Officer, Business Manager, Senator
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Describe the purpose and scope of this election"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="datetime-local" id="start_date" name="start_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date *</label>
                        <input type="datetime-local" id="end_date" name="end_date" required>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Create Election</button>
                </form>
            </div>

            <div class="content-card">
                <h3>Current Elections</h3>
                <?php if (!empty($all_elections)): ?>
                    <?php foreach ($all_elections as $election): ?>
                        <?php
                        // Determine actual status based on current date/time
                        $current_time = time();
                        $start_time = strtotime($election['start_date']);
                        $end_time = strtotime($election['end_date']);
                        
                        if ($current_time < $start_time) {
                            $actual_status = 'upcoming';
                            $status_text = '⏰ Upcoming';
                        } elseif ($current_time >= $start_time && $current_time <= $end_time) {
                            $actual_status = 'active';
                            $status_text = '🟢 Active';
                        } else {
                            $actual_status = 'ended';
                            $status_text = '🔴 Ended';
                        }
                        ?>
                        <div class="election-item">
                            <div class="election-header">
                                <div class="election-title"><?php echo htmlspecialchars($election['title']); ?></div>
                                <div class="election-status status-<?php echo $actual_status; ?>">
                                    <?php echo $status_text; ?>
                                </div>
                            </div>
                            <div class="election-details">
                                <strong>Start:</strong> <?php echo date('M j, Y g:i A', strtotime($election['start_date'])); ?><br>
                                <strong>End:</strong> <?php echo date('M j, Y g:i A', strtotime($election['end_date'])); ?><br>
                                <?php
                                // Add time information based on status
                                if ($actual_status === 'upcoming') {
                                    $time_diff = $start_time - $current_time;
                                    $days = floor($time_diff / (24 * 60 * 60));
                                    $hours = floor(($time_diff % (24 * 60 * 60)) / (60 * 60));
                                    echo "<strong>Starts in:</strong> {$days} days, {$hours} hours<br>";
                                } elseif ($actual_status === 'active') {
                                    $time_diff = $end_time - $current_time;
                                    $days = floor($time_diff / (24 * 60 * 60));
                                    $hours = floor(($time_diff % (24 * 60 * 60)) / (60 * 60));
                                    echo "<strong>Ends in:</strong> {$days} days, {$hours} hours<br>";
                                } elseif ($actual_status === 'ended') {
                                    $time_diff = $current_time - $end_time;
                                    $days = floor($time_diff / (24 * 60 * 60));
                                    $hours = floor(($time_diff % (24 * 60 * 60)) / (60 * 60));
                                    echo "<strong>Ended:</strong> {$days} days, {$hours} hours ago<br>";
                                }
                                ?>
                                <?php if ($election['description']): ?>
                                    <strong>Description:</strong> <?php echo htmlspecialchars($election['description']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="election-actions">
                                <a href="admin_candidates.php?election_id=<?php echo $election['id']; ?>" class="btn btn-secondary btn-small">
                                    Manage Candidates
                                </a>
                                <button class="btn btn-small" onclick="editElection(<?php echo htmlspecialchars(json_encode($election)); ?>)">
                                    Edit
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this election? This will also delete all candidates and votes.')">
                                    <input type="hidden" name="action" value="delete_election">
                                    <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 2rem;">
                        No elections found. Create your first election to get started.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Election Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 15px; width: 90%; max-width: 500px;">
            <h3 style="margin-bottom: 1rem; color: #1c2143;">Edit Election</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_election">
                <input type="hidden" name="election_id" id="edit_election_id">
                
                <div class="form-group">
                    <label for="edit_title">Election Title *</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_start_date">Start Date *</label>
                    <input type="datetime-local" id="edit_start_date" name="start_date" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_end_date">End Date *</label>
                    <input type="datetime-local" id="edit_end_date" name="end_date" required>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Election</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const electionTypes = <?php echo json_encode($election_types); ?>;
        
        function showElectionTypeInfo() {
            const select = document.getElementById('election_type');
            const infoDiv = document.getElementById('election-type-info');
            const customTypeDiv = document.getElementById('custom_election_type');
            const selectedType = electionTypes[select.value];
            
            // Show/hide custom election type field
            if (select.value === 'custom') {
                customTypeDiv.style.display = 'block';
                document.getElementById('custom_type_name').required = true;
            } else {
                customTypeDiv.style.display = 'none';
                document.getElementById('custom_type_name').required = false;
            }
            
            infoDiv.innerHTML = `
                <h4>${selectedType.name}</h4>
                <div class="type-description">
                    ${selectedType.positions.length > 0 ? 
                        'Positions: ' + selectedType.positions.join(', ') : 
                        'Custom positions can be defined when adding candidates.'}
                </div>
            `;
        }

        function editElection(election) {
            document.getElementById('edit_election_id').value = election.id;
            document.getElementById('edit_title').value = election.title;
            document.getElementById('edit_description').value = election.description || '';
            
            // Format dates for datetime-local input
            const startDate = new Date(election.start_date);
            const endDate = new Date(election.end_date);
            
            document.getElementById('edit_start_date').value = startDate.toISOString().slice(0, 16);
            document.getElementById('edit_end_date').value = endDate.toISOString().slice(0, 16);
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Initialize election type info
        showElectionTypeInfo();

        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>
