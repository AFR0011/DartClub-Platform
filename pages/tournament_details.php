<?php $tourId = isset($_GET['id']) ? (int) $_GET['id'] : 0; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Details - Famagusta Dart Club</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="shortcut icon" href="../files/media/images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .detail-shell {
            padding: 7rem 0 3rem;
            display: grid;
            gap: 1.5rem;
        }

        .surface,
        .match-card,
        .team-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 1.4rem;
        }

        .hero-grid,
        .meta-grid,
        .results-grid,
        .roster-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-align: left;
        }

        .match-grid,
        .team-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            background: rgba(255, 107, 53, 0.18);
            color: #ffd7ca;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body>
    <header class="header" id="header">
        <nav class="nav container">
            <a href="main.php" class="nav__logo">
                <img src="../files/media/images/logo.png" alt="logo"> Famagusta Dart Club
            </a>
            <div class="nav__menu" id="nav-menu">
                <ul class="nav__list"></ul>
                <div class="nav__close" id="nav-close">
                    <i class="ri-close-line"></i>
                </div>
            </div>
            <div class="nav__toggle" id="nav-toggle">
                <i class="ri-menu-line"></i>
            </div>
        </nav>
    </header>

    <main class="main">
        <section class="detail-shell container" id="detail-shell">
            <div class="surface">Loading tournament details...</div>
        </section>
    </main>

    <a href="#top" class="scrollup" id="scroll-up">
        <i class="ri-arrow-up-line"></i>
    </a>

    <script src="../js/scrollreveal.min.js"></script>
    <script src="../js/behaviour.js"></script>
    <script>
        const tournamentId = <?php echo $tourId; ?>;

        function renderStandingsTable(rows, entityLabel = 'Player') {
            if (!rows || rows.length === 0) {
                return '<p style="color: var(--text-color);">No standings are available yet.</p>';
            }

            const nameKey = entityLabel === 'Team' ? 'team_name' : null;
            return `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>${entityLabel}</th>
                            <th>Played</th>
                            <th>Won</th>
                            <th>Lost</th>
                            <th>Drawn</th>
                            <th>Points</th>
                            <th>Leg Diff</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map((row) => `
                            <tr>
                                <td>${nameKey ? row[nameKey] : `${row.plr_name} ${row.plr_surname}`}</td>
                                <td>${row.matches_played}</td>
                                <td>${row.matches_won}</td>
                                <td>${row.matches_lost}</td>
                                <td>${row.matches_drawn}</td>
                                <td>${row.points}</td>
                                <td>${row.leg_difference}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        function renderIndividualMatches(matches) {
            if (!matches || matches.length === 0) {
                return '<p style="color: var(--text-color);">No fixtures yet.</p>';
            }

            return `
                <div class="match-grid">
                    ${matches.map((match) => `
                        <article class="match-card">
                            <div class="pill">${match.bracket || (match.group_number ? `Group ${match.group_number}` : 'Fixture')}</div>
                            <h3>${match.player1_name ? `${match.player1_name} ${match.player1_surname}` : 'TBD'} vs ${match.player2_name ? `${match.player2_name} ${match.player2_surname}` : 'TBD'}</h3>
                            <p style="color: var(--text-color); margin-top: 0.75rem;">
                                ${match.match_date} at ${match.match_time}
                            </p>
                            <p style="margin-top: 0.5rem;">
                                ${match.match_status === 'Completed' ? `<strong>${match.player1_score} - ${match.player2_score}</strong>` : 'Scheduled'}
                            </p>
                        </article>
                    `).join('')}
                </div>
            `;
        }

        function renderTeamMatches(matches) {
            if (!matches || matches.length === 0) {
                return '<p style="color: var(--text-color);">No team fixtures yet.</p>';
            }

            return `
                <div class="match-grid">
                    ${matches.map((match) => `
                        <article class="match-card">
                            <div class="pill">Team Fixture</div>
                            <h3>${match.team1_name || 'TBD'} vs ${match.team2_name || 'TBD'}</h3>
                            <p style="color: var(--text-color); margin-top: 0.75rem;">
                                ${match.match_date} at ${match.match_time}
                            </p>
                            <p style="margin-top: 0.5rem;">
                                ${match.match_status === 'Completed' ? `<strong>${match.team1_score} - ${match.team2_score}</strong>` : 'Scheduled'}
                            </p>
                        </article>
                    `).join('')}
                </div>
            `;
        }

        function renderLeagueGroupTables(groups) {
            const entries = Object.entries(groups || {});
            if (entries.length === 0) {
                return '<p style="color: var(--text-color);">Group-stage standings will appear once matches are underway.</p>';
            }

            return entries.map(([groupNumber, rows]) => `
                <div class="surface">
                    <h3>Group ${groupNumber}</h3>
                    ${renderStandingsTable(rows)}
                </div>
            `).join('');
        }

        function renderTeams(teams) {
            if (!teams || teams.length === 0) {
                return '<p style="color: var(--text-color);">Teams have not been generated yet.</p>';
            }

            return `
                <div class="team-grid">
                    ${teams.map((team) => `
                        <article class="team-card">
                            <h3>${team.team_name}</h3>
                            <ul style="margin-top: 0.85rem; color: var(--text-color);">
                                ${team.players.map((player) => `<li>${player.plr_name} ${player.plr_surname}</li>`).join('')}
                            </ul>
                        </article>
                    `).join('')}
                </div>
            `;
        }

        function renderTournamentPage(data) {
            const tournament = data.tournament;
            const shell = document.getElementById('detail-shell');

            shell.innerHTML = `
                <section class="surface">
                    <div class="hero-grid">
                        <div>
                            <div class="pill">${tournament.status.replaceAll('_', ' ')}</div>
                            <h1>${tournament.tour_title}</h1>
                            <p style="color: var(--text-color); margin-top: 1rem;">Format: ${tournament.tour_type}</p>
                        </div>
                        <div class="meta-grid">
                            <div>
                                <strong>Start</strong>
                                <p>${new Date(tournament.tour_creationDate).toLocaleDateString()}</p>
                            </div>
                            <div>
                                <strong>End</strong>
                                <p>${new Date(tournament.tour_endDate).toLocaleDateString()}</p>
                            </div>
                            <div>
                                <strong>Visibility</strong>
                                <p>${Number(tournament.is_public) === 1 ? 'Public' : 'Private'}</p>
                            </div>
                            <div>
                                <strong>Winner</strong>
                                <p>${tournament.winner_label || 'TBD'}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="results-grid">
                    <div class="surface">
                        <h2>Participants</h2>
                        <p style="color: var(--text-color); margin-bottom: 1rem;">${data.players.length} registered players</p>
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Group</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.players.map((player) => `
                                        <tr>
                                            <td>${player.plr_name} ${player.plr_surname}</td>
                                            <td>${player.player_status}</td>
                                            <td>${player.group_number ?? '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="surface">
                        <h2>Recent Results</h2>
                        ${
                          data.recent_results.length === 0
                            ? '<p style="color: var(--text-color);">No results reported yet.</p>'
                            : data.recent_results.map((result) => `
                                <p style="margin-bottom: 0.75rem;">
                                    ${result.team1_name ? `${result.team1_name} ${result.team1_score} - ${result.team2_score} ${result.team2_name}` : `${result.player1_name || 'TBD'} ${result.player1_score} - ${result.player2_score} ${result.player2_name || 'TBD'}`}
                                </p>
                            `).join('')
                        }
                    </div>
                </section>

                <section class="surface">
                    <h2>Competition View</h2>
                    ${
                      tournament.tour_type === 'Round Robin'
                        ? renderStandingsTable(data.standings)
                        : tournament.tour_type === 'League'
                            ? renderLeagueGroupTables(data.group_standings)
                            : tournament.tour_type === 'Group'
                                ? renderStandingsTable(data.team_standings, 'Team')
                                : '<p style="color: var(--text-color);">Elimination brackets update through the fixture list below.</p>'
                    }
                </section>

                ${
                  tournament.tour_type === 'Group'
                    ? `
                        <section class="surface">
                            <h2>Teams</h2>
                            ${renderTeams(data.teams)}
                        </section>
                        <section class="surface">
                            <h2>Team Fixtures</h2>
                            ${renderTeamMatches(data.team_matches)}
                        </section>
                      `
                    : `
                        <section class="surface">
                            <h2>Fixtures & Bracket</h2>
                            ${renderIndividualMatches(data.matches)}
                        </section>
                      `
                }
            `;
        }

        async function loadTournamentDetails() {
            if (!tournamentId) {
                throw new Error('Tournament id is missing.');
            }

            const response = await fetch(`../services/get_tournament_details.php?id=${tournamentId}`);
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to load the tournament.');
            }

            renderTournamentPage(data);
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadTournamentDetails().catch((error) => {
                document.getElementById('detail-shell').innerHTML = `<div class="surface">${error.message}</div>`;
            });
        });
    </script>
</body>
</html>

