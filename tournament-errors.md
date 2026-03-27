### League Mode

**Fatal error**: Uncaught mysqli_sql_exception: Unknown column 'm.group_number' in 'field list' in C:\xampp\htdocs\dart-club-project\pages\admin\show_tournament_details.php:94 Stack trace: #0 C:\xampp\htdocs\dart-club-project\pages\admin\show_tournament_details.php(94): mysqli->prepare('SELECT \n ...') #1 {main} thrown in **C:\xampp\htdocs\dart-club-project\pages\admin\show_tournament_details.php** on line **94**

### Group Mode

Not actually group mode, it should divide the players into groups. E.g., 2 groups and 2 players advancing from each group means that the players will be divided into 2 equal groups randomly, and each player will play only against the players in their group; after all the initial games, the top 2 players in each group will advance into a final round.

Also, group mode doesn’t need a “bracket” section, replace that to “groups” where all the groups are displayed with some sort of pagination if there are too many groups to fit in a row. 

Also, replace the Standings table with the final round standing, and move the Standings table to another section next to Match List.

### Elimination and Double Elimination Modes

Bracket display error:

**Warning**

: Undefined variable $rounds in

**C:\xampp\htdocs\dart-club-project\pages\admin\show_tournament_details.php**

on line

**529**

**Warning**

: foreach() argument must be of type array|object, null given in

**C:\xampp\htdocs\dart-club-project\pages\admin\show_tournament_details.php**

on line

**529**

Currently both double elimination and elimination modes are using the same logic for setting up matches, which is basically elimination mode with an added “bracket” which does nothing.