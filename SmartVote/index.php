<?php
require_once 'classes/ElectionManager.php';

// Helper functions to order positions
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
		'public information officer' => 7,
		'p.r.o' => 7,
		'pro' => 7,
		'pio' => 7,
		'senator' => 8,
		'representative' => 9,
	];
	if (isset($order[$position_lower])) { return $order[$position_lower]; }
	foreach ($order as $key => $value) { 
		if (strpos($position_lower, $key) !== false) { return $value; } 
	}
	return 999;
}

function sortPositions($positions) {
	uksort($positions, function($a, $b) {
		$order_a = getPositionOrder($a);
		$order_b = getPositionOrder($b);
		if ($order_a == $order_b) { return strcmp($a, $b); }
		return $order_a - $order_b;
	});
	return $positions;
}

$electionManager = new ElectionManager();
$all_elections = $electionManager->getAllElections();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Vote - Secure Digital Voting Platform</title>
<link rel="icon" type="image/png" href="logo/icon.png">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Roboto', sans-serif;
    line-height: 1.6;
    color: #333;
    background: #1c2143;
    min-height: 100vh;
}

.navbar {
    background: #43c3dd;
    backdrop-filter: blur(10px);
    padding: 1rem 0;
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
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

.nav-links {
    display: flex;
    list-style: none;
    gap: 2rem;
}

.nav-links a {
    text-decoration: none;
    color: white;
    font-weight: 500;
    transition: color 0.3s ease;
    position: relative;
}

.nav-links a:hover {
    color: #1c2143;
}

.nav-links a::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -5px;
    left: 0;
    background: white;
    transition: width 0.3s ease;
}

.nav-links a:hover::after {
    width: 100%;
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
    border: 1px solid transparent;
}

.admin-btn {
    background: #244357;
    color: white;
}

.admin-btn:hover {
    background: white;
    color: #1c2143;
}

.login-btn {
    background: transparent;
    color: white;
    border: 1px solid white;
}

.login-btn:hover {
    background: white;
    color: #43c3dd;
}

.register-btn {
    background: white;
    color: #43c3dd;
}

.register-btn:hover {
    background: #004a94;
    color: white;
}

.hero {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    color: white;
    text-align: center;
}

.section {
    padding: 4rem 0;
    background: #f4f7fb;
}

.section .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.election-header-public {
    border-bottom: 3px solid #43c3dd;
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
}

.results-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 2rem;
    color: #1c2143;
    margin-bottom: 1rem;
    text-align: center;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, .table td {
    padding: 10px 12px;
    border-bottom: 1px solid #e9edf3;
    text-align: left;
}

.table th { background: #f2f6fb; color: #1c2143; }
.position-row { background: #f8f9fa; font-weight: 600; }

.hero-content {
    max-width: 800px;
    padding: 0 2rem;
}

.hero h1 {
    font-family: 'Montserrat', sans-serif;
    font-size: 3.5rem;
    margin-bottom: 1rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.hero p {
    font-size: 1.3rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 15px 30px;
    border: none;
    border-radius: 50px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #004a94;
    color: white;
    box-shadow: 0 4px 15px rgba(0, 74, 148, 0.4);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 74, 148, 0.6);
}

@media (max-width: 768px) {
    .nav-links {
        display: none;
    }
    .nav-buttons {
        gap: 0.3rem;
    }
    .nav-btn {
        padding: 6px 12px;
        font-size: 0.8rem;
    }
    .hero h1 {
        font-size: 2.5rem;
    }
    .hero p {
        font-size: 1.1rem;
    }
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    .btn {
        width: 200px;
    }
}
</style>
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">Smart Vote</div>
        <ul class="nav-links">
            <li><a href="#home">Home</a></li>
            <li><a href="voter_dashboard.php">Vote Now</a></li>
            <li><a href="#results">Results</a></li>
        </ul>
        <div class="nav-buttons">
            <a href="admin_login.php" class="nav-btn admin-btn">Admin</a>
            <a href="login.php" class="nav-btn login-btn">Login</a>
            <a href="register.php" class="nav-btn register-btn">Register</a>
        </div>
    </div>
</nav>

<section id="home" class="hero">
    <div class="hero-content">
        <h1>Welcome to Smart Vote</h1>
        <p>
            Smart Vote is a secure digital platform designed to modernize the election process by providing a fast,
            transparent, and reliable method of casting and counting votes.<br>
            The system allows voters to authenticate their identity,
            select candidates, and submit votes electronically, ensuring accuracy and eliminating manual errors.<br>
            It features real-time tallying, automated result generation, and administrative tools for managing voters and candidates.<br>
            By reducing paperwork, preventing duplicate voting, and increasing accessibility, the system enhances credibility,
            boosts participation, and ensures a more efficient and transparent election process.
        </p>
        <div class="cta-buttons">
            <a href="login.php" class="btn btn-primary">Start Voting</a>
        </div>
    </div>
</section>

<section id="results" class="section">
    <div class="container">
        <h2 class="results-title">Live Election Statistics</h2>
        <?php if (empty($all_elections)): ?>
            <div class="card" style="text-align:center;color:#666;">No elections available.</div>
        <?php else: ?>
            <?php foreach ($all_elections as $election): ?>
                <?php 
                $stats = $electionManager->getVotingStats($election['id']);
                $database = new Database();
                $conn = $database->getConnection();
                $stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id = ? AND status = 'active' ORDER BY position, first_name, last_name");
                $stmt->execute([$election['id']]);
                $all_candidates = $stmt->fetchAll();

                $vote_map = [];
                foreach ($stats['candidate_votes'] as $row) { 
                    $vote_map[(int)$row['id']] = (int)$row['vote_count']; 
                }

                $candidates_by_position = [];
                $map = [
                    'vice president' => 'Vice President','vice-president' => 'Vice President','vp' => 'Vice President',
                    'president' => 'President','pres' => 'President','secretary' => 'Secretary','sec' => 'Secretary',
                    'treasurer' => 'Treasurer','treas' => 'Treasurer','auditor' => 'Auditor','aud' => 'Auditor',
                    'public information officer' => 'Public Information Officer','pio' => 'Public Information Officer',
                    'business manager' => 'Business Manager','bm' => 'Business Manager','senator' => 'Senator','sen' => 'Senator',
                    'representative' => 'Representative','rep' => 'Representative'
                ];
                foreach ($all_candidates as $c) {
                    $pos = trim($c['position']);
                    $key = isset($map[strtolower($pos)]) ? $map[strtolower($pos)] : $pos;
                    $candidates_by_position[$key][] = $c;
                }

                // Sort positions by hierarchy
                $candidates_by_position = sortPositions($candidates_by_position);

                // Skip election if no candidates
                if (empty($candidates_by_position)) {
                    continue;
                }
                ?>
                <div class="card">
                    <div class="election-header-public">
                        <div>
                            <div style="font-size: 1.4rem; font-weight:700;color:#1c2143;"><?php echo htmlspecialchars($election['title']); ?></div>
                            <div style="font-size: 0.85rem; color: #666; margin-top: 0.3rem;">
                                <?php 
                                $start = !empty($election['start_date']) ? date('F j, Y g:i A', strtotime($election['start_date'])) : 'N/A';
                                $end = !empty($election['end_date']) ? date('F j, Y g:i A', strtotime($election['end_date'])) : 'N/A';
                                echo "Voting Period: {$start} - {$end}";
                                ?>
                            </div>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:28%;">Position</th>
                                <th style="width:42%;">Candidate</th>
                                <th style="width:20%;">Party</th>
                                <th style="width:10%;">Votes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidates_by_position as $position => $cands): ?>
                                <tr class="position-row"><td colspan="4"><?php echo htmlspecialchars($position); ?></td></tr>
                                <?php foreach ($cands as $c): ?>
                                    <?php 
                                    $name_parts = [$c['first_name']]; 
                                    if (!empty($c['middle_name'])) { $name_parts[] = $c['middle_name']; }
                                    $name_parts[] = $c['last_name']; 
                                    if (!empty($c['suffix'])) { $name_parts[] = $c['suffix']; }
                                    $name = trim(implode(' ', $name_parts));
                                    $votes = isset($vote_map[(int)$c['id']]) ? $vote_map[(int)$c['id']] : 0;
                                    ?>
                                    <tr>
                                        <td></td>
                                        <td><?php echo htmlspecialchars($name); ?></td>
                                        <td><?php echo htmlspecialchars($c['party'] ?? ''); ?></td>
                                        <td><?php echo $votes; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<script>
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Keep navbar color consistent on scroll (no dynamic color change)
</script>
</body>
</html>