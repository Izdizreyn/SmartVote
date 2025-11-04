<?php
require_once 'config/database.php';

class ElectionManager {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Get all elections
     */
    public function getAllElections() {
        try {
            $query = "SELECT * FROM elections ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get active elections (elections that are currently open for voting)
     */
    public function getActiveElections() {
        try {
            $query = "SELECT * FROM elections WHERE (status = 'active' OR status = 'upcoming') 
                     AND start_date <= NOW() AND end_date >= NOW() 
                     ORDER BY start_date ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get elections available for voting (active + upcoming elections)
     */
    public function getElectionsForVoting() {
        try {
            $query = "SELECT * FROM elections WHERE (status = 'active' OR status = 'upcoming') 
                     AND end_date >= NOW() 
                     ORDER BY start_date ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get election by ID
     */
    public function getElectionById($election_id) {
        try {
            $query = "SELECT * FROM elections WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get candidates for an election
     */
    public function getCandidatesByElection($election_id) {
        try {
            $query = "SELECT * FROM candidates WHERE election_id = ? AND status = 'active' 
                     ORDER BY position, first_name, last_name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id]);
            $candidates = $stmt->fetchAll();
            
            // Clean up full_name (remove extra spaces)
            foreach ($candidates as &$candidate) {
                $candidate['full_name'] = $this->getFullName($candidate['first_name'], $candidate['middle_name'], $candidate['last_name'], $candidate['suffix']);
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
            
            // Sort candidates by position order
            usort($candidates, function($a, $b) use ($position_order) {
                $pos_a = trim($a['position']);
                $pos_b = trim($b['position']);
                
                // Normalize position names
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
                
                $normalized_pos_a = isset($position_map[strtolower($pos_a)]) ? $position_map[strtolower($pos_a)] : $pos_a;
                $normalized_pos_b = isset($position_map[strtolower($pos_b)]) ? $position_map[strtolower($pos_b)] : $pos_b;
                
                $index_a = array_search($normalized_pos_a, $position_order);
                $index_b = array_search($normalized_pos_b, $position_order);
                
                // If both positions are in the predefined order, sort by their index
                if ($index_a !== false && $index_b !== false) {
                    return $index_a - $index_b;
                }
                
                // If only one is in the predefined order, prioritize it
                if ($index_a !== false) return -1;
                if ($index_b !== false) return 1;
                
                // If neither is in the predefined order, sort alphabetically
                return strcmp($normalized_pos_a, $normalized_pos_b);
            });
            
            return $candidates;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get candidates grouped by position
     */
    public function getCandidatesByPosition($election_id) {
        try {
            $query = "SELECT DISTINCT * FROM candidates WHERE election_id = ? AND status = 'active' 
                     ORDER BY position, first_name, last_name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id]);
            $candidates = $stmt->fetchAll();
            
            // Add full_name and name to each candidate
            foreach ($candidates as &$candidate) {
                $candidate['full_name'] = $this->getFullName($candidate['first_name'], $candidate['middle_name'], $candidate['last_name'], $candidate['suffix']);
                $candidate['name'] = $candidate['full_name']; // For consistency with voting stats
            }
            
            $grouped = [];
            $seen_candidates = []; // Track seen candidates to prevent duplicates
            
            foreach ($candidates as $candidate) {
                // Prevent duplicate candidates
                $candidate_key = $candidate['id'];
                if (in_array($candidate_key, $seen_candidates)) {
                    continue;
                }
                $seen_candidates[] = $candidate_key;
                
                // Normalize position name (trim and handle case variations)
                $position = trim($candidate['position']);
                
                // Handle common position name variations
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
                
                $grouped[$normalized_position][] = $candidate;
            }
            
            return $grouped;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Add new candidate
     */
    public function addCandidate($election_id, $first_name, $middle_name, $last_name, $suffix, $description, $position, $party, $photo = null) {
        try {
            $query = "INSERT INTO candidates (election_id, first_name, middle_name, last_name, suffix, description, position, party, photo) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$election_id, $first_name, $middle_name, $last_name, $suffix, $description, $position, $party, $photo]);
            
            return $result ? ['success' => true, 'message' => 'Candidate added successfully'] : 
                            ['success' => false, 'message' => 'Failed to add candidate'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update candidate
     */
    public function updateCandidate($candidate_id, $first_name, $middle_name, $last_name, $suffix, $description, $position, $party, $photo = null) {
        try {
            $query = "UPDATE candidates SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, description = ?, position = ?, party = ?, photo = ? 
                     WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $description, $position, $party, $photo, $candidate_id]);
            
            return $result ? ['success' => true, 'message' => 'Candidate updated successfully'] : 
                            ['success' => false, 'message' => 'Failed to update candidate'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete candidate
     */
    public function deleteCandidate($candidate_id) {
        try {
            $query = "DELETE FROM candidates WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$candidate_id]);
            
            return $result ? ['success' => true, 'message' => 'Candidate deleted successfully'] : 
                            ['success' => false, 'message' => 'Failed to delete candidate'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Parties: CRUD and queries
     */
    public function getPartiesByElection($election_id) {
        try {
            $query = "SELECT id, election_id, name, description, created_at FROM parties WHERE election_id = ? ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function partyExists($election_id, $name) {
        try {
            $query = "SELECT id FROM parties WHERE election_id = ? AND name = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id, $name]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function addParty($election_id, $name, $description = null) {
        try {
            if ($this->partyExists($election_id, $name)) {
                return ['success' => false, 'message' => 'Party already exists for this election'];
            }
            $query = "INSERT INTO parties (election_id, name, description) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $ok = $stmt->execute([$election_id, $name, $description]);
            return $ok ? ['success' => true, 'message' => 'Party added successfully'] : ['success' => false, 'message' => 'Failed to add party'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function deleteParty($party_id) {
        try {
            $query = "DELETE FROM parties WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $ok = $stmt->execute([$party_id]);
            return $ok ? ['success' => true, 'message' => 'Party deleted successfully'] : ['success' => false, 'message' => 'Failed to delete party'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get voting statistics for an election
     */
    public function getVotingStats($election_id) {
        try {
            // Total votes
            $query = "SELECT COUNT(*) as total_votes FROM votes WHERE election_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id]);
            $total_votes = $stmt->fetch()['total_votes'];

            // Votes by candidate
            $query = "SELECT c.id, c.first_name, c.middle_name, c.last_name, c.suffix, c.position, c.party, COUNT(v.id) as vote_count 
                     FROM candidates c 
                     LEFT JOIN votes v ON c.id = v.candidate_id 
                     WHERE c.election_id = ? 
                     GROUP BY c.id, c.first_name, c.middle_name, c.last_name, c.suffix, c.position, c.party 
                     ORDER BY vote_count DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id]);
            $candidate_votes = $stmt->fetchAll();
            
            // Add full_name to each candidate vote record
            foreach ($candidate_votes as &$vote) {
                $name_parts = [$vote['first_name']];
                if (!empty($vote['middle_name'])) {
                    $name_parts[] = $vote['middle_name'];
                }
                $name_parts[] = $vote['last_name'];
                if (!empty($vote['suffix'])) {
                    $name_parts[] = $vote['suffix'];
                }
                $vote['name'] = implode(' ', $name_parts);
            }

            // Total eligible voters
            $query = "SELECT COUNT(*) as total_voters FROM users WHERE status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $total_voters = $stmt->fetch()['total_voters'];

            $turnout_percentage = $total_voters > 0 ? round(($total_votes / $total_voters) * 100, 2) : 0;

            return [
                'total_votes' => $total_votes,
                'total_voters' => $total_voters,
                'turnout_percentage' => $turnout_percentage,
                'candidate_votes' => $candidate_votes
            ];
        } catch (PDOException $e) {
            return [
                'total_votes' => 0,
                'total_voters' => 0,
                'turnout_percentage' => 0,
                'candidate_votes' => []
            ];
        }
    }

    /**
     * Get winners for each position
     */
    public function getWinners($election_id) {
        try {
            $query = "SELECT c.position, c.first_name, c.middle_name, c.last_name, c.suffix, c.party, COUNT(v.id) as vote_count 
                     FROM candidates c 
                     LEFT JOIN votes v ON c.id = v.candidate_id 
                     WHERE c.election_id = ? 
                     GROUP BY c.id, c.position, c.first_name, c.middle_name, c.last_name, c.suffix, c.party 
                     HAVING vote_count = (
                         SELECT MAX(vote_count) 
                         FROM (
                             SELECT COUNT(v2.id) as vote_count 
                             FROM candidates c2 
                             LEFT JOIN votes v2 ON c2.id = v2.candidate_id 
                             WHERE c2.election_id = ? AND c2.position = c.position 
                             GROUP BY c2.id
                         ) as max_votes
                     )
                     ORDER BY c.position";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id, $election_id]);
            $winners = $stmt->fetchAll();
            
            // Add full_name to each winner record
            foreach ($winners as &$winner) {
                $name_parts = [$winner['first_name']];
                if (!empty($winner['middle_name'])) {
                    $name_parts[] = $winner['middle_name'];
                }
                $name_parts[] = $winner['last_name'];
                if (!empty($winner['suffix'])) {
                    $name_parts[] = $winner['suffix'];
                }
                $winner['name'] = implode(' ', $name_parts);
            }
            
            return $winners;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get recent voting activity
     */
    public function getRecentVotingActivity($limit = 50) {
        try {
            $query = "SELECT v.vote_timestamp, u.first_name, u.last_name, c.name as candidate_name, 
                            c.position, e.title as election_title 
                     FROM votes v 
                     JOIN users u ON v.user_id = u.id 
                     JOIN candidates c ON v.candidate_id = c.id 
                     JOIN elections e ON v.election_id = e.id 
                     ORDER BY v.vote_timestamp DESC 
                     LIMIT ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$limit]);
            $results = $stmt->fetchAll();
            
            // Format the full name
            foreach ($results as &$result) {
                $result['full_name'] = trim($result['first_name'] . ' ' . $result['last_name']);
            }
            
            return $results;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Create new election
     */
    public function createElection($title, $description, $start_date, $end_date, $created_by = null) {
        try {
            $query = "INSERT INTO elections (title, description, start_date, end_date, created_by) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$title, $description, $start_date, $end_date, $created_by]);
            
            return $result ? ['success' => true, 'message' => 'Election created successfully', 'id' => $this->conn->lastInsertId()] : 
                            ['success' => false, 'message' => 'Failed to create election'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update election
     */
    public function updateElection($election_id, $title, $description, $start_date, $end_date) {
        try {
            $query = "UPDATE elections SET title = ?, description = ?, start_date = ?, end_date = ? 
                     WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$title, $description, $start_date, $end_date, $election_id]);
            
            return $result ? ['success' => true, 'message' => 'Election updated successfully'] : 
                            ['success' => false, 'message' => 'Failed to update election'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete election
     */
    public function deleteElection($election_id) {
        try {
            // First delete all candidates for this election
            $query = "DELETE FROM candidates WHERE election_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id]);
            
            // Then delete all votes for this election
            $query = "DELETE FROM votes WHERE election_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$election_id]);
            
            // Finally delete the election
            $query = "DELETE FROM elections WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$election_id]);
            
            return $result ? ['success' => true, 'message' => 'Election deleted successfully'] : 
                            ['success' => false, 'message' => 'Failed to delete election'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get candidate by ID
     */
    public function getCandidateById($candidate_id) {
        try {
            $query = "SELECT * FROM candidates WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$candidate_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check if a candidate name already exists in an election
     */
    public function candidateNameExists($election_id, $first_name, $last_name, $exclude_candidate_id = null) {
        try {
            $query = "SELECT id FROM candidates WHERE election_id = ? AND first_name = ? AND last_name = ?";
            $params = [$election_id, $first_name, $last_name];
            
            // Exclude current candidate when updating
            if ($exclude_candidate_id) {
                $query .= " AND id != ?";
                $params[] = $exclude_candidate_id;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Helper method to construct full name from separate name fields
     */
    private function getFullName($first_name, $middle_name, $last_name, $suffix) {
        $name_parts = [$first_name];
        
        if (!empty($middle_name)) {
            $name_parts[] = $middle_name;
        }
        
        $name_parts[] = $last_name;
        
        if (!empty($suffix)) {
            $name_parts[] = $suffix;
        }
        
        return implode(' ', $name_parts);
    }

    /**
     * Get election types and positions
     */
    public function getElectionTypes() {
        return [
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
    }
}
?>
