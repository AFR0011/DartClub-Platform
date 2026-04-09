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
                 m.player1_score, m.player2_score, m.match_notes,
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

// Check if match is completed
if ($match['match_status'] != 'Completed') {
    header("Location: record_match_result.php?id=" . $match_id);
    exit;
}

// Get player statistics from tournament standings
$query_stats = "SELECT s.matches_played, s.matches_won, s.matches_lost, s.matches_drawn, 
                      s.points, s.leg_difference
               FROM tournament_standings s
               WHERE s.tour_id = ? AND s.player_id = ?";

$stmt_stats1 = $db->prepare($query_stats);
$stmt_stats1->bind_param("ii", $match['tour_id'], $match['player1_id']);
$stmt_stats1->execute();
$result_stats1 = $stmt_stats1->get_result();
$player1_stats = $result_stats1->fetch_assoc();

$stmt_stats2 = $db->prepare($query_stats);
$stmt_stats2->bind_param("ii", $match['tour_id'], $match['player2_id']);
$stmt_stats2->execute();
$result_stats2 = $stmt_stats2->get_result();
$player2_stats = $result_stats2->fetch_assoc();

// Determine winner
$winner = null;
if ($match['player1_score'] > $match['player2_score']) {
    $winner = 1;
} elseif ($match['player2_score'] > $match['player1_score']) {
    $winner = 2;
} else {
    $winner = 0; // Draw
}

// Get match leg details if available
$query_legs = "SELECT leg_number, player1_score, player2_score, winner_id, leg_notes
              FROM match_legs
              WHERE match_id = ?
              ORDER BY leg_number";

$stmt_legs = $db->prepare($query_legs);
$stmt_legs->bind_param("i", $match_id);
$stmt_legs->execute();
$result_legs = $stmt_legs->get_result();
$legs = $result_legs->fetch_all(MYSQLI_ASSOC);

// Get previous matches between these players
$query_history = "SELECT m.match_id, m.match_date, m.player1_score, m.player2_score,
                       (CASE 
                           WHEN m.player1_id = ? AND m.player2_id = ? THEN 1
                           ELSE 2
                       END) AS match_order
                  FROM matches m
                  WHERE m.match_status = 'Completed'
                  AND ((m.player1_id = ? AND m.player2_id = ?) 
                       OR (m.player1_id = ? AND m.player2_id = ?))
                  AND m.match_id != ?
                  ORDER BY m.match_date DESC
                  LIMIT 5";

$stmt_history = $db->prepare($query_history);
$stmt_history->bind_param("iiiiiii", $match['player1_id'], $match['player2_id'], 
                         $match['player1_id'], $match['player2_id'], 
                         $match['player2_id'], $match['player1_id'], 
                         $match_id);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
$match_history = $result_history->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Details</title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <script src="../../js/admin_nav.js"></script>
    <style>
        .match-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .player-card {
            width: 45%;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .player-card.winner {
            background-color: #e8f5e9;
            border-left: 5px solid #4caf50;
        }
        
        .player-card.loser {
            background-color: #ffebee;
            border-left: 5px solid #f44336;
        }
        
        .player-card.draw {
            background-color: #e3f2fd;
            border-left: 5px solid #2196f3;
        }
        
        .vs-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .score {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .match-info {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .match-info h2 {
            margin-top: 0;
        }
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .stats-table th, .stats-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .stats-table th {
            background-color: #f2f2f2;
        }
        
        .legs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .legs-table th, .legs-table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .legs-table th {
            background-color: #f2f2f2;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .history-table th, .history-table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .history-table th {
            background-color: #f2f2f2;
        }
    </style>
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
        <h1>Match Details</h1>

        <div class="success-message" style="background:#eef5ff;color:#1d4ed8;border-left-color:#1d4ed8;">
            Legacy fallback page. Use the consolidated tournament detail workspace for the primary admin match workflow.
        </div>
        
        <div class="match-info">
            <h2><?php echo htmlspecialchars($match['tour_title']); ?></h2>
            <p>
                <strong>Date:</strong> <?php echo date('Y-m-d', strtotime($match['match_date'])); ?> 
                <strong>Time:</strong> <?php echo $match['match_time']; ?>
            </p>
            <p>
                <strong>Tournament Type:</strong> <?php echo $match['tour_type']; ?>
                <strong>Status:</strong> <?php echo $match['match_status']; ?>
            </p>
            <p>
                <a href="show_tournament_details.php?id=<?php echo $match['tour_id']; ?>" class="back-btn">Back to Tournament</a>
            </p>
        </div>
        
        <div class="match-container">
            <div class="player-card <?php echo $winner === 1 ? 'winner' : ($winner === 0 ? 'draw' : 'loser'); ?>">
                <h3><?php echo htmlspecialchars($match['player1_name'] . ' ' . $match['player1_surname']); ?></h3>
                <div class="score"><?php echo $match['player1_score']; ?></div>
                
                <?php if ($player1_stats): ?>
                <h4>Tournament Statistics</h4>
                <table class="stats-table">
                    <tr>
                        <th>Matches Played</th>
                        <td><?php echo $player1_stats['matches_played']; ?></td>
                    </tr>
                    <tr>
                        <th>Wins</th>
                        <td><?php echo $player1_stats['matches_won']; ?></td>
                    </tr>
                    <tr>
                        <th>Losses</th>
                        <td><?php echo $player1_stats['matches_lost']; ?></td>
                    </tr>
                    <tr>
                        <th>Draws</th>
                        <td><?php echo $player1_stats['matches_drawn']; ?></td>
                    </tr>
                    <tr>
                        <th>Points</th>
                        <td><?php echo $player1_stats['points']; ?></td>
                    </tr>
                    <tr>
                        <th>Leg Difference</th>
                        <td><?php echo $player1_stats['leg_difference']; ?></td>
                    </tr>
                </table>
                <?php endif; ?>
            </div>
            
            <div class="vs-container">
                <div>VS</div>
                <?php if ($winner === 1): ?>
                    <div>Winner</div>
                <?php elseif ($winner === 2): ?>
                    <div>Winner</div>
                <?php else: ?>
                    <div>Draw</div>
                <?php endif; ?>
            </div>
            
            <div class="player-card <?php echo $winner === 2 ? 'winner' : ($winner === 0 ? 'draw' : 'loser'); ?>">
                <h3><?php echo htmlspecialchars($match['player2_name'] . ' ' . $match['player2_surname']); ?></h3>
                <div class="score"><?php echo $match['player2_score']; ?></div>
                
                <?php if ($player2_stats): ?>
                <h4>Tournament Statistics</h4>
                <table class="stats-table">
                    <tr>
                        <th>Matches Played</th>
                        <td><?php echo $player2_stats['matches_played']; ?></td>
                    </tr>
                    <tr>
                        <th>Wins</th>
                        <td><?php echo $player2_stats['matches_won']; ?></td>
                    </tr>
                    <tr>
                        <th>Losses</th>
                        <td><?php echo $player2_stats['matches_lost']; ?></td>
                    </tr>
                    <tr>
                        <th>Draws</th>
                        <td><?php echo $player2_stats['matches_drawn']; ?></td>
                    </tr>
                    <tr>
                        <th>Points</th>
                        <td><?php echo $player2_stats['points']; ?></td>
                    </tr>
                    <tr>
                        <th>Leg Difference</th>
                        <td><?php echo $player2_stats['leg_difference']; ?></td>
                    </tr>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($match['match_notes'])): ?>
        <div class="match-notes">
            <h3>Match Notes</h3>
            <p><?php echo nl2br(htmlspecialchars($match['match_notes'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($legs)): ?>
        <h3>Match Legs</h3>
        <table class="legs-table">
            <tr>
                <th>Leg</th>
                <th><?php echo htmlspecialchars($match['player1_name']); ?></th>
                <th><?php echo htmlspecialchars($match['player2_name']); ?></th>
                <th>Winner</th>
                <th>Notes</th>
            </tr>
            <?php foreach ($legs as $leg): ?>
            <tr>
                <td><?php echo $leg['leg_number']; ?></td>
                <td><?php echo $leg['player1_score']; ?></td>
                <td><?php echo $leg['player2_score']; ?></td>
                <td>
                    <?php 
                    if ($leg['winner_id'] == $match['player1_id']) {
                        echo htmlspecialchars($match['player1_name']);
                    } elseif ($leg['winner_id'] == $match['player2_id']) {
                        echo htmlspecialchars($match['player2_name']);
                    } else {
                        echo "Draw";
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($leg['leg_notes']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        
        <?php if (!empty($match_history)): ?>
        <h3>Previous Matches</h3>
        <table class="history-table">
            <tr>
                <th>Date</th>
                <th><?php echo htmlspecialchars($match['player1_name']); ?></th>
                <th><?php echo htmlspecialchars($match['player2_name']); ?></th>
                <th>Result</th>
            </tr>
            <?php foreach ($match_history as $history): ?>
            <tr>
                <td><?php echo date('Y-m-d', strtotime($history['match_date'])); ?></td>
                <?php if ($history['match_order'] == 1): ?>
                    <td><?php echo $history['player1_score']; ?></td>
                    <td><?php echo $history['player2_score']; ?></td>
                    <td>
                        <?php 
                        if ($history['player1_score'] > $history['player2_score']) {
                            echo htmlspecialchars($match['player1_name']) . " won";
                        } elseif ($history['player2_score'] > $history['player1_score']) {
                            echo htmlspecialchars($match['player2_name']) . " won";
                        } else {
                            echo "Draw";
                        }
                        ?>
                    </td>
                <?php else: ?>
                    <td><?php echo $history['player2_score']; ?></td>
                    <td><?php echo $history['player1_score']; ?></td>
                    <td>
                        <?php 
                        if ($history['player2_score'] > $history['player1_score']) {
                            echo htmlspecialchars($match['player1_name']) . " won";
                        } elseif ($history['player1_score'] > $history['player2_score']) {
                            echo htmlspecialchars($match['player2_name']) . " won";
                        } else {
                            echo "Draw";
                        }
                        ?>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        
        <div class="actions">
            <a href="show_tournament_details.php?id=<?php echo $match['tour_id']; ?>" class="back-btn">Back to Tournament</a>
            <a href="record_match_result.php?id=<?php echo $match_id; ?>" class="edit-btn">Open legacy score entry</a>
        </div>
    </div>
    
    <script>
        // Add any JavaScript functionality here
    </script>
</body>
</html>
