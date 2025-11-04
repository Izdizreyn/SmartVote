<?php
require_once 'config/database.php';

class UserAuth {
    private $conn;
    private $table_name = "users";
    private $session_started = false;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->startSession();
    }

    private function startSession() {
        if (!$this->session_started && session_status() == PHP_SESSION_NONE) {
            session_start();
            $this->session_started = true;
        }
    }

    /**
     * Register a new student voter
     */
    public function register($voter_id, $first_name, $middle_name, $last_name, $suffix, $email, $phone, $password, $profile_picture = null) {
        try {
            if ($this->voterIdExists($voter_id)) {
                return ['success' => false, 'message' => 'Student ID already exists'];
            }

            if ($this->emailExists($email)) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Hash the password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO " . $this->table_name . " 
                     (voter_id, first_name, middle_name, last_name, suffix, email, phone, profile_picture, password_hash, user_type) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$voter_id, $first_name, $middle_name, $last_name, $suffix, $email, $phone, $profile_picture, $password_hash]);

            if ($result) {
                return ['success' => true, 'message' => 'Student voter account created successfully! You can now login with your credentials.', 'user_id' => $this->conn->lastInsertId()];
            }

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Authenticate student voter login
     */
    public function login($voter_id, $password) {
        try {
            $query = "SELECT id, voter_id, first_name, middle_name, last_name, suffix, email, password_hash, user_type, status 
                     FROM " . $this->table_name . " 
                     WHERE voter_id = ? AND status = 'active'";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$voter_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if user is already logged in from another session
                if ($this->isUserAlreadyLoggedIn($user['id'])) {
                    return ['success' => false, 'message' => 'You are already logged in from another session. Please logout from other devices first.'];
                }

                // Generate unique session token
                $session_token = $this->generateSessionToken();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['voter_id'] = $user['voter_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['middle_name'] = $user['middle_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['suffix'] = $user['suffix'];
                $_SESSION['full_name'] = $this->getFullName($user['first_name'], $user['middle_name'], $user['last_name'], $user['suffix']);
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time();
                $_SESSION['session_token'] = $session_token;

                // Store session info in database for tracking
                $this->storeActiveSession($user['id'], $session_token);

                return ['success' => true, 'message' => 'Login successful', 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Invalid Student ID or password'];
            }

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check if user has already voted in an election
     */
    public function hasVoted($user_id, $election_id) {
        try {
            $query = "SELECT id FROM votes WHERE user_id = ? AND election_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $election_id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check if user has already voted for a specific position in an election
     */
    public function hasVotedForPosition($user_id, $election_id, $position) {
        try {
            $query = "SELECT v.id FROM votes v 
                     JOIN candidates c ON v.candidate_id = c.id 
                     WHERE v.user_id = ? AND v.election_id = ? AND c.position = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $election_id, $position]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Record multiple votes for different positions in an election
     */
    public function castMultiPositionVote($user_id, $election_id, $candidate_votes, $ip_address = null, $user_agent = null) {
        try {
            // Check if user has already voted in this election
            if ($this->hasVoted($user_id, $election_id)) {
                return ['success' => false, 'message' => 'You have already voted in this election'];
            }

            if (!$this->isElectionActive($election_id)) {
                return ['success' => false, 'message' => 'Election is not currently active'];
            }

            // Start transaction
            $this->conn->beginTransaction();

            $success_count = 0;
            $error_messages = [];

            foreach ($candidate_votes as $candidate_id) {
                $query = "INSERT INTO votes (user_id, election_id, candidate_id, ip_address, user_agent) 
                         VALUES (?, ?, ?, ?, ?)";

                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([$user_id, $election_id, $candidate_id, $ip_address, $user_agent]);

                if ($result) {
                    $success_count++;
                } else {
                    $error_messages[] = "Failed to record vote for candidate ID: $candidate_id";
                }
            }

            if ($success_count === count($candidate_votes)) {
                $this->conn->commit();
                return ['success' => true, 'message' => 'All votes recorded successfully'];
            } else {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Some votes failed: ' . implode(', ', $error_messages)];
            }

        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollback();
            }
            return ['success' => false, 'message' => 'Vote failed: ' . $e->getMessage()];
        }
    }

    /**
     * Record a vote (prevents duplicate voting)
     */
    public function castVote($user_id, $election_id, $candidate_id, $ip_address = null, $user_agent = null) {
        try {
            if ($this->hasVoted($user_id, $election_id)) {
                return ['success' => false, 'message' => 'You have already voted in this election'];
            }

            if (!$this->isElectionActive($election_id)) {
                return ['success' => false, 'message' => 'Election is not currently active'];
            }

            $query = "INSERT INTO votes (user_id, election_id, candidate_id, ip_address, user_agent) 
                     VALUES (?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$user_id, $election_id, $candidate_id, $ip_address, $user_agent]);

            if ($result) {
                return ['success' => true, 'message' => 'Vote recorded successfully'];
            }

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Vote failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get user information
     */
    public function getUserInfo($user_id) {
        try {
            $query = "SELECT id, voter_id, first_name, middle_name, last_name, suffix, email, phone, profile_picture, user_type, status, created_at 
                     FROM " . $this->table_name . " WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $user['full_name'] = $this->getFullName($user['first_name'], $user['middle_name'], $user['last_name'], $user['suffix']);
            }
            
            return $user;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get all users for admin
     */
    public function getAllUsers() {
        try {
            $query = "SELECT id, voter_id, first_name, middle_name, last_name, suffix, email, phone, profile_picture, user_type, status, created_at 
                     FROM " . $this->table_name . " ORDER BY created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            // Add full_name to each user
            foreach ($users as &$user) {
                $user['full_name'] = $this->getFullName($user['first_name'], $user['middle_name'], $user['last_name'], $user['suffix']);
            }
            
            return $users;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Update user profile information including profile picture
     */
    public function updateUser($user_id, $first_name, $middle_name, $last_name, $suffix, $email, $phone, $profile_picture = null) {
        try {
            // Check if email already exists for another user
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? AND id != ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already exists for another user'];
            }

            // Build the update query dynamically based on whether profile picture is provided
            if ($profile_picture !== null) {
                $query = "UPDATE " . $this->table_name . " 
                         SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, phone = ?, profile_picture = ?, updated_at = NOW() 
                         WHERE id = ?";
                $params = [$first_name, $middle_name, $last_name, $suffix, $email, $phone, $profile_picture, $user_id];
            } else {
                $query = "UPDATE " . $this->table_name . " 
                         SET first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, phone = ?, updated_at = NOW() 
                         WHERE id = ?";
                $params = [$first_name, $middle_name, $last_name, $suffix, $email, $phone, $user_id];
            }
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute($params);

            if ($result) {
                // Update session data
                $_SESSION['first_name'] = $first_name;
                $_SESSION['middle_name'] = $middle_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['suffix'] = $suffix;
                $_SESSION['full_name'] = $this->getFullName($first_name, $middle_name, $last_name, $suffix);
                
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update profile'];
            }

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Update user status
     */
    public function updateUserStatus($user_id, $status) {
        try {
            $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$status, $user_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function voterIdExists($voter_id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE voter_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$voter_id]);
        return $stmt->rowCount() > 0;
    }

    private function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

    private function isElectionActive($election_id) {
        $query = "SELECT status FROM elections WHERE id = ? AND (status = 'active' OR status = 'upcoming') 
                 AND start_date <= NOW() AND end_date >= NOW()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$election_id]);
        return $stmt->rowCount() > 0;
    }

    public function logout() {
        // Remove session from database if user_id exists
        if (isset($_SESSION['user_id'])) {
            $this->removeUserSessions($_SESSION['user_id']);
        }
        
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function isLoggedIn() {
        // Check if session exists and is valid
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Check for session timeout (optional - 24 hours)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 86400)) {
            $this->logout();
            return false;
        }
        
        // Validate session token if it exists
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
            if (!$this->validateSessionToken($_SESSION['user_id'], $_SESSION['session_token'])) {
                $this->logout();
                return false;
            }
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        return true;
    }

    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get user's voting history for a specific election
     */
    public function getVotingHistory($user_id, $election_id) {
        try {
            $query = "SELECT v.*, c.first_name, c.middle_name, c.last_name, c.suffix, c.position, c.party, c.photo,
                             e.title as election_title, e.start_date, e.end_date
                     FROM votes v
                     JOIN candidates c ON v.candidate_id = c.id
                     JOIN elections e ON v.election_id = e.id
                     WHERE v.user_id = ? AND v.election_id = ?
                     ORDER BY v.vote_timestamp DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $election_id]);
            $votes = $stmt->fetchAll();
            
            // Add full_name to each vote
            foreach ($votes as &$vote) {
                $vote['candidate_full_name'] = $this->getFullName($vote['first_name'], $vote['middle_name'], $vote['last_name'], $vote['suffix']);
            }
            
            return $votes;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get user's complete voting history across all elections
     */
    public function getAllVotingHistory($user_id) {
        try {
            $query = "SELECT v.*, c.first_name, c.middle_name, c.last_name, c.suffix, c.position, c.party, c.photo,
                             e.title as election_title, e.start_date, e.end_date
                     FROM votes v
                     JOIN candidates c ON v.candidate_id = c.id
                     JOIN elections e ON v.election_id = e.id
                     WHERE v.user_id = ?
                     ORDER BY v.vote_timestamp DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $votes = $stmt->fetchAll();
            
            // Add full_name to each vote
            foreach ($votes as &$vote) {
                $vote['candidate_full_name'] = $this->getFullName($vote['first_name'], $vote['middle_name'], $vote['last_name'], $vote['suffix']);
            }
            
            return $votes;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Check if user is already logged in from another session
     */
    private function isUserAlreadyLoggedIn($user_id) {
        try {
            $query = "SELECT id FROM active_sessions WHERE user_id = ? AND expires_at > NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Generate a unique session token
     */
    private function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Store active session in database
     */
    private function storeActiveSession($user_id, $session_token) {
        try {
            // First, remove any existing sessions for this user
            $this->removeUserSessions($user_id);
            
            // Get IP address and user agent
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Insert new session
            $query = "INSERT INTO active_sessions (user_id, session_token, created_at, expires_at, ip_address, user_agent) 
                     VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR), ?, ?)";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$user_id, $session_token, $ip_address, $user_agent]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Remove all sessions for a specific user
     */
    private function removeUserSessions($user_id) {
        try {
            $query = "DELETE FROM active_sessions WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Validate current session token
     */
    private function validateSessionToken($user_id, $session_token) {
        try {
            $query = "SELECT id FROM active_sessions 
                     WHERE user_id = ? AND session_token = ? AND expires_at > NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $session_token]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions() {
        try {
            $query = "DELETE FROM active_sessions WHERE expires_at < NOW()";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Force logout from all devices for a user
     */
    public function forceLogoutAllDevices($user_id) {
        try {
            $query = "DELETE FROM active_sessions WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$user_id]);
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
}
?>
