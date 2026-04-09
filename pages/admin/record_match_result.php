<?php
require_once '../../services/app_bootstrap.php';
require_once '../../services/dbConnection.php';
require_once '../../services/auth.php';

app_start_session();
require_any_role(['admin', 'manager']);

// Check if match ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_tournaments.php");
    exit;
}

$match_id = $_GET['id'];

$db = $conn;

// Get match details
$query = "SELECT m.match_id, m.tour_id, m.match_date, m.match_time, m.match_status,
                 p1.plr_idNum AS player1_id, p1.plr_name AS player1_name, p1.plr_surname AS player1_surname,
                 p2.plr_idNum AS player2_id, p2.plr_name AS player2_name, p2.plr_surname AS player2_surname,
                 t.tour_title, t.tour_type
          FROM matches m
          JOIN players p1 ON m.player1_id = p1.plr_idNum
          JOIN players p2 ON m.player2_id = p2.plr_idNum
          JOIN tournaments t ON m.tour_id = t.tour_id
          WHERE m.match_id = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Match not found";
    exit;
}

$match = $result->fetch_assoc();

// Check if match is already completed
if ($match['match_status'] == 'Completed') {
    header("Location: view_match_details.php?id=" . $match_id);
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    $player1_score = isset($_POST['player1_score']) ? intval($_POST['player1_score']) : 0;
    $player2_score = isset($_POST['player2_score']) ? intval($_POST['player2_score']) : 0;
    
    // Validate scores are non-negative
    if ($player1_score < 0 || $player2_score < 0) {
        $_SESSION['error'] = "Scores cannot be negative.";
    } 
    // Validate at least one player has a positive score
    elseif ($player1_score == 0 && $player2_score == 0) {
        $_SESSION['error'] = "At least one player must score.";
    }
    // All validations passed, update the match
    else {
        // Start transaction
        $db->begin_transaction();
        
        try {
            // Update match result
            $update_query = "UPDATE matches 
                            SET player1_score = ?, player2_score = ?, match_status = 'Completed'
                            WHERE match_id = ?";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bind_param("iii", $player1_score, $player2_score, $match_id);
            $update_stmt->execute();
            
            // Determine winner
            $winner_id = null;
            $is_draw = false;
            
            if ($player1_score > $player2_score) {
                $winner_id = $match['player1_id'];
            } elseif ($player2_score > $player1_score) {
                $winner_id = $match['player2_id'];
            } else {
                $is_draw = true;
            }
            
            // Update tournament standings for league or group tournaments
            if ($match['tour_type'] == 'League' || $match['tour_type'] == 'Group') {
                // Update player 1 standings
                updatePlayerStanding($db, $match['tour_id'], $match['player1_id'], $player1_score, $player2_score, $winner_id == $match['player1_id'], $is_draw);
                
                // Update player 2 standings
                updatePlayerStanding($db, $match['tour_id'], $match['player2_id'], $player2_score, $player1_score, $winner_id == $match['player2_id'], $is_draw);
            }
            
            // For elimination tournaments, update the bracket
            if ($match['tour_type'] == 'Elimination') {
                // Fetch next match details directly from the matches table
                $next_match_query = "SELECT next_match_id, position_in_next FROM matches 
                                    WHERE match_id = ?";
                $next_match_stmt = $db->prepare($next_match_query);
                $next_match_stmt->bind_param("i", $match_id);
                $next_match_stmt->execute();
                $next_match_result = $next_match_stmt->get_result();
                
                if ($next_match_result->num_rows > 0) {
                    $next_match = $next_match_result->fetch_assoc();
                    $next_match_id = $next_match['next_match_id'];
                    $position = $next_match['position_in_next'];
                    
                    // Update the next match if there’s a winner and a valid next match
                    if (!$is_draw && $next_match_id !== null) {
                        if ($position == 1) {
                            $update_next_query = "UPDATE matches 
                                                SET player1_id = ? 
                                                WHERE match_id = ?";
                        } else {
                            $update_next_query = "UPDATE matches 
                                                SET player2_id = ? 
                                                WHERE match_id = ?";
                        }
                        $update_next_stmt = $db->prepare($update_next_query);
                        $update_next_stmt->bind_param("ii", $winner_id, $next_match_id);
                        $update_next_stmt->execute();
                    }
                }
            }
            
            // Commit transaction
            $db->commit();
            
            // Set success message
            $_SESSION['success'] = "Match result recorded successfully.";
            
            // Redirect to tournament details
            header("Location: show_tournament_details.php?id=" . $match['tour_id']);
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            $_SESSION['error'] = "Error recording match result: " . $e->getMessage();
        }
    }
}

// Function to update player standings
function updatePlayerStanding($db, $tour_id, $player_id, $player_score, $opponent_score, $is_winner, $is_draw) {
    // Check if standing record exists
    $check_query = "SELECT * FROM tournament_standings 
                   WHERE tour_id = ? AND player_id = ?";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bind_param("ii", $tour_id, $player_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $standing = $check_result->fetch_assoc();
        
        $matches_played = $standing['matches_played'] + 1;
        $matches_won = $standing['matches_won'] + ($is_winner ? 1 : 0);
        $matches_lost = $standing['matches_lost'] + (!$is_winner && !$is_draw ? 1 : 0);
        $matches_drawn = $standing['matches_drawn'] + ($is_draw ? 1 : 0);
        
        // Get points system from tournament settings (default: win=3, draw=1, loss=0)
        $points_win = 3;
        $points_draw = 1;
        $points_loss = 0;
        
        // Calculate points
        $points = $standing['points'];
        if ($is_winner) {
            $points += $points_win;
        } elseif ($is_draw) {
            $points += $points_draw;
        } else {
            $points += $points_loss;
        }
        
        // Calculate leg difference
        $leg_difference = $standing['leg_difference'] + ($player_score - $opponent_score);
        
        // Update standing
        $update_query = "UPDATE tournament_standings 
                        SET matches_played = ?, matches_won = ?, matches_lost = ?, 
                            matches_drawn = ?, points = ?, leg_difference = ?
                        WHERE tour_id = ? AND player_id = ?";
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bind_param("iiiiiiii", $matches_played, $matches_won, $matches_lost, 
                                $matches_drawn, $points, $leg_difference, $tour_id, $player_id);
        $update_stmt->execute();
    } else {
        // Create new record
        $matches_played = 1;
        $matches_won = $is_winner ? 1 : 0;
        $matches_lost = !$is_winner && !$is_draw ? 1 : 0;
        $matches_drawn = $is_draw ? 1 : 0;
        
        // Get points system from tournament settings (default: win=3, draw=1, loss=0)
        $points_win = 3;
        $points_draw = 1;
        $points_loss = 0;
        
        // Calculate points
        $points = 0;
        if ($is_winner) {
            $points = $points_win;
        } elseif ($is_draw) {
            $points = $points_draw;
        } else {
            $points = $points_loss;
        }
        
        // Calculate leg difference
        $leg_difference = $player_score - $opponent_score;
        
        // Insert standing
        $insert_query = "INSERT INTO tournament_standings 
                        (tour_id, player_id, matches_played, matches_won, matches_lost, 
                         matches_drawn, points, leg_difference)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bind_param("iiiiiiii", $tour_id, $player_id, $matches_played, $matches_won, 
                                $matches_lost, $matches_drawn, $points, $leg_difference);
        $insert_stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Match Result</title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <script src="../../js/admin_nav.js"></script>
</head>
<body>
    <!-- Sidenav -->
    <div class="sidenav" id="sidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <a href="manage_players.php">Manage Players</a>
        <a href="manage_tournaments.php">Manage Tournaments</a>
        <a href="../main.php">Back to Website</a>
    </div>

    <div class="container">
        <span style="font-size:30px;cursor:pointer" onclick="openNav()">&#9776;</span>
        
        <div class="header-actions">
            <h1>Record Match Result</h1>
            <a href="show_tournament_details.php?id=<?php echo $match['tour_id']; ?>" class="back-btn">Back to Tournament</a>
        </div>

        <div class="success-message" style="background:#eef5ff;color:#1d4ed8;border-left-color:#1d4ed8;">
            Legacy fallback page. Use the consolidated tournament detail workspace for the primary admin scoring flow.
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="match-info">
            <h2><?php echo htmlspecialchars($match['tour_title']); ?></h2>
            <p>
                <strong>Date:</strong> <?php echo date('Y-m-d', strtotime($match['match_date'])); ?> 
                <strong>Time:</strong> <?php echo $match['match_time']; ?>
            </p>
            <p>
                <strong>Tournament Type:</strong> <?php echo htmlspecialchars($match['tour_type']); ?>
            </p>
        </div>
        
        <form action="record_match_result.php?id=<?php echo $match_id; ?>" method="post" id="recordResultForm" class="record-result-form">
            <div class="match-players">
                <div class="player player1">
                    <h3><?php echo htmlspecialchars($match['player1_name'] . ' ' . $match['player1_surname']); ?></h3>
                    <div class="score-input">
                        <label for="player1_score">Score:</label>
                        <input type="number" name="player1_score" id="player1_score" min="0" required>
                    </div>
                </div>
                
                <div class="vs">VS</div>
                
                <div class="player player2">
                    <h3><?php echo htmlspecialchars($match['player2_name'] . ' ' . $match['player2_surname']); ?></h3>
                    <div class="score-input">
                        <label for="player2_score">Score:</label>
                        <input type="number" name="player2_score" id="player2_score" min="0" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="match_notes">Match Notes (Optional):</label>
                <textarea name="match_notes" id="match_notes" rows="4" placeholder="Enter any notes about the match..."></textarea>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="submit-btn">Record Result</button>
                <a href="show_tournament_details.php?id=<?php echo $match['tour_id']; ?>" class="cancel-btn">Cancel</a>
            </div>
        </form>
        
        <div class="match-help">
            <h3>Recording Instructions</h3>
            <ul>
                <li>Enter the final score for each player</li>
                <li>Scores must be non-negative numbers</li>
                <li>At least one player must have a score greater than zero</li>
                <li>Equal scores will be recorded as a draw (if allowed in the tournament type)</li>
                <li>For elimination tournaments, the player with the higher score will advance to the next round</li>
            </ul>
        </div>
    </div>
    
    <script>
        function openNav() {
            document.getElementById("sidenav").style.width = "250px";
        }

        function closeNav() {
            document.getElementById("sidenav").style.width = "0";
        }
        
        // Validate form before submission
        document.getElementById('recordResultForm').addEventListener('submit', function(event) {
            const player1Score = parseInt(document.getElementById('player1_score').value);
            const player2Score = parseInt(document.getElementById('player2_score').value);
            
            if (isNaN(player1Score) || isNaN(player2Score)) {
                alert('Please enter valid scores for both players.');
                event.preventDefault();
                return;
            }
            
            if (player1Score < 0 || player2Score < 0) {
                alert('Scores cannot be negative.');
                event.preventDefault();
                return;
            }
            
            if (player1Score === 0 && player2Score === 0) {
                alert('At least one player must score.');
                event.preventDefault();
                return;
            }
            
            // Confirm submission
            if (!confirm('Are you sure you want to record this result? This action cannot be undone.')) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
