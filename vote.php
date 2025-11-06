<?php
require_once 'classes/UserAuth.php';
require_once 'classes/ElectionManager.php';

// Prevent caching to ensure fresh data
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$auth = new UserAuth();
$electionManager = new ElectionManager();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $auth->getCurrentUserId();
$user_info = $auth->getUserInfo($user_id);

// Get election ID from URL parameter
$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

if (!$election_id) {
    header('Location: voter_dashboard.php');
    exit();
}

// Get election details
$election = $electionManager->getElectionById($election_id);
if (!$election) {
    header('Location: voter_dashboard.php');
    exit();
}

// Check if election is active for voting
$is_currently_active = (strtotime($election['start_date']) <= time() && strtotime($election['end_date']) >= time());
$has_voted = $auth->hasVoted($user_id, $election_id);

// Initialize vote message variables
$vote_message = null;
$vote_message_type = null;

// Get candidates for this election
// Force fresh data by using a new database connection
$database = new Database();
$fresh_conn = $database->getConnection();

// Get candidates with fresh connection
$query = "SELECT * FROM candidates WHERE election_id = ? AND status = 'active' 
         ORDER BY position, first_name, last_name";
$stmt = $fresh_conn->prepare($query);
$stmt->execute([$election_id]);
$raw_candidates = $stmt->fetchAll();

// Process candidates manually to ensure fresh data
$candidates = [];
$seen_candidates = [];

foreach ($raw_candidates as $candidate) {
    // Prevent duplicate candidates
    if (in_array($candidate['id'], $seen_candidates)) {
        continue;
    }
    $seen_candidates[] = $candidate['id'];
    
    // Add full_name
    $name_parts = [$candidate['first_name']];
    if (!empty($candidate['middle_name'])) {
        $name_parts[] = $candidate['middle_name'];
    }
    $name_parts[] = $candidate['last_name'];
    if (!empty($candidate['suffix'])) {
        $name_parts[] = $candidate['suffix'];
    }
    $candidate['full_name'] = implode(' ', $name_parts);
    
    // Normalize position name
    $position = trim($candidate['position']);
    $position_map = [
        'vice president' => 'Vice President',
        'vice-pres' => 'Vice President',
        'vp' => 'Vice President',
        'president' => 'President',
        'pres' => 'President',
        'secretary' => 'Secretary',
        'sec' => 'Secretary',
        'treasurer' => 'Treasurer',
        'treas' => 'Treasurer',
        'auditor' => 'Auditor',
        'aud' => 'Auditor',
        'public information officer' => 'Public Information Officer',
        'pio' => 'Public Information Officer',
        'business manager' => 'Business Manager',
        'bm' => 'Business Manager',
        'senator' => 'Senator',
        'sen' => 'Senator',
        'representative' => 'Representative',
        'rep' => 'Representative'
    ];
    
    $normalized_position = isset($position_map[strtolower($position)]) ? 
        $position_map[strtolower($position)] : $position;
    
    $candidates[$normalized_position][] = $candidate;
}

// Define the correct order for positions
$position_order = [
    'President',
    'Vice President', 
    'Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer',
    'Business Manager',
    'Senator',
    'Representative'
];

// Reorder candidates according to the defined order
$ordered_candidates = [];
foreach ($position_order as $position) {
    if (isset($candidates[$position])) {
        $ordered_candidates[$position] = $candidates[$position];
    }
}

// Add any remaining positions not in the predefined order
foreach ($candidates as $position => $position_candidates) {
    if (!isset($ordered_candidates[$position])) {
        $ordered_candidates[$position] = $position_candidates;
    }
}

$candidates = $ordered_candidates;

// Handle vote submission (after candidates are loaded)
if ($_POST && isset($_POST['cast_vote'])) {
    if (isset($_POST['candidate_votes']) && is_array($_POST['candidate_votes'])) {
        $candidate_votes = $_POST['candidate_votes'];
        
        // Validate that we have votes for all positions
        $all_positions = array_keys($candidates);
        if (count($candidate_votes) !== count($all_positions)) {
            $vote_message = "Please select a candidate for each position.";
            $vote_message_type = 'error';
        } else {
            // Cast votes for all positions at once
            $result = $auth->castMultiPositionVote($user_id, $election_id, $candidate_votes, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            $vote_message = $result['message'];
            $vote_message_type = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $has_voted = true;
            }
        }
    } else {
        $vote_message = "No candidates selected. Please select a candidate for each position.";
        $vote_message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - <?php echo htmlspecialchars($election['title']); ?> - Smart Vote</title>
    <link rel="icon" type="image/png" href="logo/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .back-btn {
            background: #1c2143;
            color: white;
        }

        .back-btn:hover {
            background: white;
            color: #1c2143;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .election-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .election-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: #1c2143;
            margin-bottom: 1rem;
        }

        .election-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            font-size: 0.9rem;
        }

        .info-label {
            font-weight: 600;
            color: #1c2143;
        }

        .info-value {
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #28a745;
            color: white;
        }

        .status-upcoming {
            background: #ffc107;
            color: #212529;
        }

        .voting-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .position-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
            color: #1c2143;
            margin: 2rem 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #43c3dd;
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
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
            background: #82c2e3;    
            box-shadow: 0 4px 16px rgba(0, 74, 148, 0.2);
        }

        .candidate-card.selected .candidate-position {
            color: #004a94;
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

        .message.info {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .voter-info {
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

        @media (max-width: 768px) {
            .candidates-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .election-title {
                font-size: 1.5rem !important;
            }
            
            .status-badge {
                font-size: 0.7rem !important;
                padding: 3px 8px !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">Smart Vote</div>
            <div class="nav-buttons">
                <a href="voter_dashboard.php" class="nav-btn back-btn">← Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="election-header">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                <h1 class="election-title" style="margin: 0;"><?php echo htmlspecialchars($election['title']); ?></h1>
                <span class="status-badge <?php echo $is_currently_active ? 'status-active' : 'status-upcoming'; ?>">
                    <?php echo $is_currently_active ? 'Active' : 'Upcoming'; ?>
                </span>
            </div>
            
            <?php if ($election['description']): ?>
                <p style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem; color: #666; margin-bottom: 1rem;"><?php echo htmlspecialchars($election['description']); ?></p>
            <?php endif; ?>
            
            
            <div class="election-info">
                <div class="info-grid">
                    <div>
                        <div class="info-label">Start Date:</div>
                        <div class="info-value"><?php echo date('M j, Y g:i A', strtotime($election['start_date'])); ?></div>
                    </div>
                    <div>
                        <div class="info-label">End Date:</div>
                        <div class="info-value"><?php echo date('M j, Y g:i A', strtotime($election['end_date'])); ?></div>
                    </div>
                </div>
            </div>

            <div class="voter-info">
                <div class="info-row">
                    <span class="info-label">Voter:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['full_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Voter ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user_info['voter_id']); ?></span>
                </div>
            </div>
        </div>

        <?php if (isset($vote_message)): ?>
            <div class="message <?php echo $vote_message_type; ?>" style="margin: 1rem 0; padding: 1rem; border-radius: 8px; text-align: center; font-weight: 600;">
                <?php if ($vote_message_type === 'success'): ?>
                    <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i>
                <?php elseif ($vote_message_type === 'error'): ?>
                    <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($vote_message); ?>
            </div>
        <?php endif; ?>

        <div class="voting-section">
            <?php if ($has_voted): ?>
                <div class="message success">
                    <strong>✓ Vote Recorded Successfully!</strong><br>
                    You have already voted in this election. Thank you for participating!
                </div>
            <?php elseif (!$is_currently_active): ?>
                <div class="message info">
                    <strong>⏰ Election Not Yet Started</strong><br>
                    This election will begin on <?php echo date('M j, Y g:i A', strtotime($election['start_date'])); ?>. Please check back then to vote.
                </div>
            <?php else: ?>
                <h2 style="text-align: center; color: #1c2143; margin-bottom: 2rem; font-family: 'Montserrat', sans-serif;">
                    Cast Your Vote
                </h2>
                
                <!-- Sorting Controls -->
                <div style="text-align: center; margin-bottom: 2rem;">
                    <label for="sortBy" style="font-weight: 600; color: #1c2143; margin-right: 0.5rem;">Sort by:</label>
                    <select id="sortBy" onchange="sortCandidates()" style="padding: 8px 12px; border: 2px solid #43c3dd; border-radius: 8px; background: white; color: #1c2143; font-weight: 500;">
                        <option value="position">Position</option>
                        <option value="name">Name (A-Z)</option>
                        <option value="name-desc">Name (Z-A)</option>
                        <option value="party">Party</option>
                    </select>
                </div>
                
                <form method="POST" id="voteForm">
                    <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                    
                    <?php foreach ($candidates as $position => $position_candidates): ?>
                        <h3 class="position-title">
                            <?php echo htmlspecialchars($position); ?>
                        </h3>
                        <div class="candidates-grid" data-position="<?php echo htmlspecialchars($position); ?>">
                            <?php foreach ($position_candidates as $candidate): ?>
                                <div class="candidate-card" 
                                     data-candidate-id="<?php echo $candidate['id']; ?>" 
                                     data-position="<?php echo htmlspecialchars($position); ?>"
                                     onclick="selectCandidate(this, <?php echo $candidate['id']; ?>, '<?php echo htmlspecialchars($position); ?>')">
                                    <div class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                                    <div class="candidate-position"><?php echo htmlspecialchars($candidate['position']); ?></div>
                                    <?php if ($candidate['party']): ?>
                                        <div class="candidate-party"><?php echo htmlspecialchars($candidate['party']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($candidate['description']): ?>
                                        <div class="candidate-description"><?php echo htmlspecialchars($candidate['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" name="cast_vote" class="vote-btn" disabled>
                            Submit Vote
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Voting History Section -->
        <?php if ($has_voted): ?>
            <?php 
            $voting_history = $auth->getVotingHistory($user_id, $election_id);
            ?>
            <div class="voting-section">
                <h2 style="text-align: center; color: #1c2143; margin-bottom: 2rem; font-family: 'Montserrat', sans-serif;">
                    Your Voting History
                </h2>
                
                <?php if (!empty($voting_history)): ?>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 1rem;">
                        <h3 style="color: #1c2143; margin-bottom: 1rem; font-family: 'Montserrat', sans-serif;">
                            Election: <?php echo htmlspecialchars($election['title']); ?>
                        </h3>
                        <p style="color: #666; margin-bottom: 1.5rem;">
                            <strong>Voted on:</strong> <?php echo date('M j, Y g:i A', strtotime($voting_history[0]['vote_timestamp'])); ?>
                        </p>
                        
                        <div style="display: grid; gap: 1rem;">
                            <?php foreach ($voting_history as $vote): ?>
                                <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #43c3dd; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <?php if ($vote['photo']): ?>
                                            <img src="<?php echo htmlspecialchars($vote['photo']); ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 2px solid #43c3dd;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #43c3dd; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">
                                                <?php echo strtoupper(substr($vote['candidate_full_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div style="flex: 1;">
                                            <h4 style="color: #1c2143; margin: 0 0 0.25rem 0; font-family: 'Montserrat', sans-serif;">
                                                <?php echo htmlspecialchars($vote['candidate_full_name']); ?>
                                            </h4>
                                            <p style="color: #43c3dd; margin: 0 0 0.25rem 0; font-weight: 600;">
                                                <?php echo htmlspecialchars($vote['position']); ?>
                                            </p>
                                            <?php if ($vote['party']): ?>
                                                <p style="color: #666; margin: 0; font-size: 0.9rem;">
                                                    Party: <?php echo htmlspecialchars($vote['party']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="text-align: right;">
                                            <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                                ✓ VOTED
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <p>No voting history found for this election.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let selectedCandidates = {};
        let selectedElection = <?php echo $election['id']; ?>;
        let originalCandidates = <?php echo json_encode($candidates); ?>;

        function selectCandidate(element, candidateId, position) {
            // Remove previous selection for this position
            const positionCards = document.querySelectorAll(`[data-position="${position}"]`);
            positionCards.forEach(card => card.classList.remove('selected'));
            
            // Select current candidate
            element.classList.add('selected');
            selectedCandidates[position] = candidateId;

            // Check if all positions have been selected
            const allPositions = <?php echo json_encode(array_keys($candidates)); ?>;
            const allSelected = allPositions.every(pos => selectedCandidates[pos]);
            
            // Enable/disable vote button
            const voteBtn = document.querySelector('.vote-btn');
            
            if (allSelected) {
                voteBtn.disabled = false;
            } else {
                voteBtn.disabled = true;
            }
        }

        function sortCandidates() {
            const sortBy = document.getElementById('sortBy').value;
            const container = document.querySelector('.voting-section');
            
            // Get all position sections
            const positionSections = container.querySelectorAll('.position-title');
            
            positionSections.forEach(positionSection => {
                const positionName = positionSection.textContent.trim();
                const candidatesGrid = positionSection.nextElementSibling;
                const candidateCards = Array.from(candidatesGrid.querySelectorAll('.candidate-card'));
                
                // Sort candidates based on selected criteria
                candidateCards.sort((a, b) => {
                    const nameA = a.querySelector('.candidate-name').textContent.trim();
                    const nameB = b.querySelector('.candidate-name').textContent.trim();
                    const partyA = a.querySelector('.candidate-party') ? a.querySelector('.candidate-party').textContent.trim() : '';
                    const partyB = b.querySelector('.candidate-party') ? b.querySelector('.candidate-party').textContent.trim() : '';
                    
                    switch(sortBy) {
                        case 'name':
                            return nameA.localeCompare(nameB);
                        case 'name-desc':
                            return nameB.localeCompare(nameA);
                        case 'party':
                            return partyA.localeCompare(partyB);
                        case 'position':
                        default:
                            return 0; // Keep original order
                    }
                });
                
                // Re-append sorted candidates
                candidateCards.forEach(card => candidatesGrid.appendChild(card));
            });
        }

        // Handle form submission
        document.getElementById('voteForm').addEventListener('submit', function(e) {
            const allPositions = <?php echo json_encode(array_keys($candidates)); ?>;
            const allSelected = allPositions.every(pos => selectedCandidates[pos]);
            
            if (!allSelected) {
                e.preventDefault();
                alert('Please select a candidate for each position before submitting your vote.');
                return;
            }
            
            // Create hidden inputs for each selected candidate
            allPositions.forEach(position => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'candidate_votes[]';
                input.value = selectedCandidates[position];
                this.appendChild(input);
            });
            
            // Allow form to submit normally - don't prevent default
        });

        // Auto-scroll to message if it exists
        document.addEventListener('DOMContentLoaded', function() {
            const messageElement = document.querySelector('.message');
            if (messageElement) {
                setTimeout(function() {
                    messageElement.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 100);
            }
        });
    </script>
</body>
</html>
