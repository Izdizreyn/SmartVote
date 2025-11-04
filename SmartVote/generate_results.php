<?php
require_once 'classes/ElectionManager.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header('Location: index.php');
    exit();
}

$electionManager = new ElectionManager();
$all_elections = $electionManager->getAllElections();

// Function to get position order
function getPositionOrder($position) {
    $position_lower = strtolower(trim($position));
    
    $order = [
        'president' => 1,
        'vice president' => 2,
        'vice-president' => 2,
        'secretary' => 3,
        'treasurer' => 4,
        'auditor' => 5,
        'business manager' => 6,
        'p.r.o' => 7,
        'pro' => 7,
        'public relations officer' => 7,
    ];
    
    // Check for exact match
    if (isset($order[$position_lower])) {
        return $order[$position_lower];
    }
    
    // Check for partial matches
    foreach ($order as $key => $value) {
        if (strpos($position_lower, $key) !== false) {
            return $value;
        }
    }
    
    // Unknown positions go last
    return 999;
}

// Function to sort positions
function sortPositions($positions) {
    uksort($positions, function($a, $b) {
        $order_a = getPositionOrder($a);
        $order_b = getPositionOrder($b);
        
        if ($order_a == $order_b) {
            return strcmp($a, $b);
        }
        
        return $order_a - $order_b;
    });
    
    return $positions;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Winners - Smart Vote</title>
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
            background: linear-gradient(135deg, #1c2143 0%, #2c3e70 100%);
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

        .page-header {
            background: linear-gradient(135deg, #43c3dd, #004a94);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin: 1rem 0 1.5rem 0;
            text-align: left;
        }

        .page-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            color: white;
            margin: 0;
        }

        .page-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .election-card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 2.5rem;
            border-top: 5px solid #43c3dd;
        }

        .election-header {
            border-bottom: 3px solid #43c3dd;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }

        .election-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: #1c2143;
            margin-bottom: 0.5rem;
        }

        .election-description {
            color: #666;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .stats-box {
            background: linear-gradient(135deg, #43c3dd 0%, #2ba3c1 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            box-shadow: 0 5px 15px rgba(67, 195, 221, 0.3);
        }

        .stats-box strong {
            font-size: 1.1rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .position-section {
            margin: 2.5rem 0;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #43c3dd;
        }

        .position-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            color: #1c2143;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .position-icon {
            background: #43c3dd;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .winner-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            padding: 2rem;
            border-radius: 15px;
            color: white;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            display: flex;
            align-items: center;
            gap: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .winner-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(40, 167, 69, 0.4);
        }

        .winner-badge {
            background: white;
            color: #28a745;
            padding: 1rem;
            border-radius: 50%;
            font-size: 2rem;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .winner-info {
            flex: 1;
        }

        .winner-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .winner-details {
            font-size: 1rem;
            opacity: 0.95;
            margin-bottom: 0.3rem;
        }

        .winner-party {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .winner-votes {
            background: rgba(255, 255, 255, 0.2);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            text-align: center;
            min-width: 120px;
        }

        .votes-number {
            font-size: 2.5rem;
            font-weight: bold;
            display: block;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .votes-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .no-results {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            padding: 3rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .no-winner {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            padding: 1.5rem;
            border-radius: 10px;
            color: white;
            text-align: center;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .winner-card {
                flex-direction: column;
                text-align: center;
            }
            
            .container {
                padding: 1rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .winner-name {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">Smart Vote - Admin</div>
            <div class="nav-buttons">
                <a href="admin_dashboard.php" class="nav-btn" style="background: white; color: #1c2143;">Dashboard</a>
                <a href="?logout=1" class="nav-btn logout-btn" onclick="return confirmLogout()">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Election Winners</h1>
        </div>

        <?php if (empty($all_elections)): ?>
            <div class="election-card">
                <div class="no-results">
                    <h2>No Elections Found</h2>
                    <p>There are currently no elections to display winners for.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($all_elections as $election): ?>
                <?php 
                $stats = $electionManager->getVotingStats($election['id']);

                // Build vote map by candidate ID (more reliable than matching by name)
                $vote_map = [];
                if (!empty($stats['candidate_votes'])) {
                    foreach ($stats['candidate_votes'] as $row) {
                        if (isset($row['id'])) {
                            $vote_map[(int)$row['id']] = (int)$row['vote_count'];
                        }
                    }
                }

                // Fetch all candidates directly (include inactive to reflect actual leaders)
                $database = new Database();
                $conn = $database->getConnection();
                $stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id = ? ORDER BY position, first_name, last_name");
                $stmt->execute([$election['id']]);
                $all_candidates = $stmt->fetchAll();

                // Group by normalized position similar to voting page
                $candidates = [];
                foreach ($all_candidates as $candidate) {
                    $name_parts = [$candidate['first_name']];
                    if (!empty($candidate['middle_name'])) { $name_parts[] = $candidate['middle_name']; }
                    $name_parts[] = $candidate['last_name'];
                    if (!empty($candidate['suffix'])) { $name_parts[] = $candidate['suffix']; }
                    $candidate['name'] = implode(' ', $name_parts);

                    $position = trim($candidate['position']);
                    $position_map = [
                        'vice president' => 'Vice President',
                        'vice-president' => 'Vice President',
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
                    $normalized_position = isset($position_map[strtolower($position)]) ? $position_map[strtolower($position)] : $position;
                    $candidates[$normalized_position][] = $candidate;
                }
                
                // Ensure stats array has all required keys
                $stats = array_merge([
                    'total_votes' => 0,
                    'total_voters' => 0,
                    'turnout_percentage' => 0,
                    'candidate_votes' => []
                ], $stats);
                
                // Sort positions by hierarchy
                $candidates = sortPositions($candidates);
                ?>
                <div class="election-card">
                    <div class="election-header">
                        <h2 class="election-title"><?php echo htmlspecialchars($election['title'] ?? 'Untitled Election'); ?></h2>
                        <?php if (!empty($election['description'])): ?>
                            <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    

                    <?php if (!empty($candidates)): ?>
                        <?php 
                        $position_number = 1;
                        foreach ($candidates as $position => $position_candidates): 
                        ?>
                            <div class="position-section">
                                <h3 class="position-title">
                                    <span class="position-icon"><?php echo $position_number; ?></span>
                                    <?php echo htmlspecialchars($position); ?>
                                </h3>
                                
                                <?php 
                                // Find the winner (candidate with most votes) using candidate ID mapping
                                $max_votes = 0;
                                $winner = null;
                                foreach ($position_candidates as $candidate) {
                                    $candidate_id = (int)$candidate['id'];
                                    $vote_count = isset($vote_map[$candidate_id]) ? (int)$vote_map[$candidate_id] : 0;
                                    if ($vote_count > $max_votes) {
                                        $max_votes = $vote_count;
                                        $winner = $candidate;
                                        $winner['votes'] = $vote_count;
                                    }
                                }
                                ?>
                                
                                <?php if ($winner && $max_votes > 0): ?>
                                    <div class="winner-card">
                                        <div class="winner-badge">
                                            <?php if (!empty($winner['photo'])): ?>
                                                <img src="<?php echo htmlspecialchars($winner['photo']); ?>" alt="Winner photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                                            <?php else: ?>
                                                👑
                                            <?php endif; ?>
                                        </div>
                                        <div class="winner-info">
                                            <div class="winner-name">
                                                <?php 
                                                echo htmlspecialchars($winner['first_name'] ?? '');
                                                if (!empty($winner['middle_name'])) {
                                                    echo ' ' . htmlspecialchars($winner['middle_name']);
                                                }
                                                echo ' ' . htmlspecialchars($winner['last_name'] ?? '');
                                                if (!empty($winner['suffix'])) {
                                                    echo ' ' . htmlspecialchars($winner['suffix']);
                                                }
                                                ?>
                                            </div>
                                            <div class="winner-details">
                                                Position: <?php echo htmlspecialchars($winner['position'] ?? 'Unknown'); ?>
                                            </div>
                                            <?php if (!empty($winner['party'])): ?>
                                                <span class="winner-party">
                                                    🎉 <?php echo htmlspecialchars($winner['party']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="winner-votes">
                                            <span class="votes-number"><?php echo $max_votes; ?></span>
                                            <span class="votes-label">Votes</span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="no-winner">
                                        No votes recorded for this position yet
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php $position_number++; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <p>No candidates found for this election.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>