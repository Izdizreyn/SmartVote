<?php
require_once 'classes/ElectionManager.php';

// Simple admin gate: reuse session check like admin_dashboard
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
	header('Location: admin_login.php');
	exit();
}

$electionManager = new ElectionManager();

// Helpers to order positions similar to generate_results
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
	if (isset($order[$position_lower])) { return $order[$position_lower]; }
	foreach ($order as $key => $value) { if (strpos($position_lower, $key) !== false) { return $value; } }
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

$all_elections = $electionManager->getAllElections();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Statistical Report - Smart Vote</title>
	<link rel="icon" type="image/png" href="logo/icon.png">
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Roboto:wght@400&display=swap" rel="stylesheet">
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body { font-family: 'Roboto', sans-serif; background: #1c2143; min-height: 100vh; }
		.navbar { background: #43c3dd; padding: 1rem 0; box-shadow: 0 2px 20px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
		.nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 2rem; }
		.logo { font-family: 'Montserrat', sans-serif; font-size: 1.8rem; font-weight: 700; color: white; }
		.nav-buttons { display: flex; gap: 0.5rem; align-items: center; }
		.nav-btn { padding: 8px 16px; border-radius: 20px; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.3s ease; }
		.logout-btn { background: #1c2143; color: white; }
		.logout-btn:hover { background: white; color: #1c2143; }
		.container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
		.page-header { background: linear-gradient(135deg, #43c3dd, #004a94); color: white; padding: 1.5rem; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin: 1rem 0 1.5rem 0; text-align: left; }
		.page-header h1 { font-family: 'Montserrat', sans-serif; font-size: 1.8rem; color: white; margin: 0; }
		.election-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); margin-bottom: 2.5rem; border-top: 5px solid #43c3dd; }
		.election-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #43c3dd; padding-bottom: 1rem; margin-bottom: 1.5rem; }
		.election-title { font-family: 'Montserrat', sans-serif; font-size: 1.6rem; color: #1c2143; }
		.btn { padding: 10px 16px; background: #004a94; color: white; border: none; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: background 0.3s ease; text-decoration: none; display: inline-block; }
		.btn:hover { background: #003366; }
		.table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
		.table th, .table td { padding: 12px; border-bottom: 1px solid #e0e0e0; text-align: left; }
		.table th { background: #f2f6fb; color: #1c2143; font-weight: 700; }
		.position-row { background: #f8f9fa; font-weight: 600; }
		.no-results { text-align: center; color: #666; font-size: 1.1rem; padding: 2rem; background: #f8f9fa; border-radius: 10px; }
	</style>
</head>
<body>
	<nav class="navbar">
		<div class="nav-container">
			<div class="logo">Smart Vote - Admin</div>
			<div class="nav-buttons">
				<a href="admin_dashboard.php" class="nav-btn" style="background: white; color: #1c2143;">Dashboard</a>
				<a href="?logout=1" class="nav-btn logout-btn" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
			</div>
		</div>
	</nav>

	<div class="container">
		<div class="page-header">
			<h1>Statistical Report</h1>
		</div>

		<?php if (empty($all_elections)): ?>
			<div class="election-card">
				<div class="no-results">No elections found.</div>
			</div>
		<?php else: ?>
			<?php foreach ($all_elections as $election): ?>
				<?php 
				$stats = $electionManager->getVotingStats($election['id']);
				// Fetch candidates directly to avoid any unintended de-duplication
				$database = new Database();
				$conn = $database->getConnection();
				$stmt = $conn->prepare("SELECT * FROM candidates WHERE election_id = ? AND status = 'active' ORDER BY position, first_name, last_name");
				$stmt->execute([$election['id']]);
				$all_candidates = $stmt->fetchAll();
				$candidates_by_position = [];
				foreach ($all_candidates as $c) {
					$pos = trim($c['position']);
					$map = [
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
					$key = isset($map[strtolower($pos)]) ? $map[strtolower($pos)] : $pos;
					$candidates_by_position[$key][] = $c;
				}
				$candidates_by_position = sortPositions($candidates_by_position);
				$vote_map = [];
				foreach ($stats['candidate_votes'] as $row) { $vote_map[(int)$row['id']] = (int)$row['vote_count']; }
				?>
				<div class="election-card">
	<div class="election-header">
		<div>
			<div class="election-title"><?php echo htmlspecialchars($election['title'] ?? 'Untitled Election'); ?></div>
			<div style="font-size: 0.95rem; color: #666; margin-top: 0.5rem;">
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
				<?php foreach ($candidates_by_position as $position => $candidates): ?>
					<tr class="position-row">
						<td colspan="4"><?php echo htmlspecialchars($position); ?></td>
					</tr>
					<?php foreach ($candidates as $c): ?>
						<?php 
						$full_name_parts = [$c['first_name']];
						if (!empty($c['middle_name'])) { $full_name_parts[] = $c['middle_name']; }
						$full_name_parts[] = $c['last_name'];
						if (!empty($c['suffix'])) { $full_name_parts[] = $c['suffix']; }
						$name = trim(implode(' ', $full_name_parts));
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
		<div class="no-results">No candidates found for this election.</div>
	<?php endif; ?>
</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</body>
</html>


