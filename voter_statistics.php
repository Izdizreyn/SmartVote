<?php
require_once 'classes/UserAuth.php';
require_once 'classes/ElectionManager.php';

$auth = new UserAuth();
if (!$auth->isLoggedIn()) {
	header('Location: login.php');
	exit();
}

$electionManager = new ElectionManager();
$user_id = $auth->getCurrentUserId();

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

// We'll show all elections for transparency
$all_elections = $electionManager->getAllElections();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Live Statistics - Smart Vote</title>
	<link rel="icon" type="image/png" href="logo/icon.png">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400&display=swap" rel="stylesheet">
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body { font-family: 'Roboto', sans-serif; background: #1c2143; min-height: 100vh; }
		.navbar { background: #43c3dd; padding: 1rem 0; box-shadow: 0 2px 20px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
		.nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; }
		.logo { font-family: 'Montserrat', sans-serif; font-size: 1.6rem; font-weight: 700; color: white; }
		.nav-btn { padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.3s ease; }
		.back-btn { background: #1c2143; color: white; }
		.back-btn:hover { background: white; color: #1c2143; }
		.container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
		.card { background: white; padding: 1.5rem; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 1.5rem; }
		.header { display:flex; justify-content:space-between; align-items:center; border-bottom: 3px solid #43c3dd; padding-bottom: 0.75rem; margin-bottom: 1rem; }
		.table { width: 100%; border-collapse: collapse; }
		.table th, .table td { padding: 10px 12px; border-bottom: 1px solid #e9edf3; text-align: left; }
		.table th { background: #f2f6fb; color: #1c2143; }
		.position-row { background: #f8f9fa; font-weight: 600; }
	</style>
</head>
<body>
	<nav class="navbar">
		<div class="nav-container">
			<div class="logo">Smart Vote</div>
			<a href="voter_dashboard.php" class="nav-btn back-btn">← Back to Dashboard</a>
		</div>
	</nav>

	<div class="container">
		<div class="card" style="background: linear-gradient(135deg, #43c3dd, #004a94); color: white;">
			<h2 style="margin-bottom: 0.25rem;">Live Statistics</h2>
		</div>

		<?php if (empty($all_elections)): ?>
			<div class="card" style="text-align:center;color:#666;">No elections available.</div>
		<?php else: ?>
			<?php foreach ($all_elections as $election): ?>
				<?php 
				$stats = $electionManager->getVotingStats($election['id']);
				$vote_map = [];
				foreach ($stats['candidate_votes'] as $row) { $vote_map[(int)$row['id']] = (int)$row['vote_count']; }
				$database = new Database();
				$conn = $database->getConnection();
				$stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id = ? AND status = 'active' ORDER BY position, first_name, last_name");
				$stmt->execute([$election['id']]);
				$all_candidates = $stmt->fetchAll();
				$map = [
					'vice president' => 'Vice President','vice-president' => 'Vice President','vp' => 'Vice President',
					'president' => 'President','pres' => 'President','secretary' => 'Secretary','sec' => 'Secretary',
					'treasurer' => 'Treasurer','treas' => 'Treasurer','auditor' => 'Auditor','aud' => 'Auditor',
					'public information officer' => 'Public Information Officer','pio' => 'Public Information Officer',
					'business manager' => 'Business Manager','bm' => 'Business Manager','senator' => 'Senator','sen' => 'Senator',
					'representative' => 'Representative','rep' => 'Representative'
				];
				$candidates_by_position = [];
				foreach ($all_candidates as $c) {
					$pos = trim($c['position']);
					$key = isset($map[strtolower($pos)]) ? $map[strtolower($pos)] : $pos;
					$candidates_by_position[$key][] = $c;
				}
				// Sort positions by hierarchy
				$candidates_by_position = sortPositions($candidates_by_position);
				?>
				<div class="card">
					<div class="header">
						<div>
							<div style="font-size: 1.6rem; font-weight:bold;color:#1c2143;"><?php echo htmlspecialchars($election['title']); ?></div>
							<div style="font-size: 0.85rem; color: #666; margin-top: 0.3rem;">
								<?php 
								$start = !empty($election['start_date']) ? date('F j, Y g:i A', strtotime($election['start_date'])) : 'N/A';
								$end = !empty($election['end_date']) ? date('F j, Y g:i A', strtotime($election['end_date'])) : 'N/A';
								echo "Voting Period: {$start} - {$end}";
								?>
							</div>
						</div>
					</div>
					<?php if (!empty($candidates_by_position)): ?>
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
										$name_parts = [$c['first_name']]; if (!empty($c['middle_name'])) { $name_parts[] = $c['middle_name']; }
										$name_parts[] = $c['last_name']; if (!empty($c['suffix'])) { $name_parts[] = $c['suffix']; }
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
					<?php else: ?>
						<div style="color:#666;">No candidates found for this election.</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</body>
</html>