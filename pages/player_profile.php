<?php $playerId = isset($_GET['id']) ? (int) $_GET['id'] : 0; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Profile - Famagusta Dart Club</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="shortcut icon" href="../files/media/images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-shell {
            padding: 7rem 0 3rem;
            display: grid;
            gap: 1.5rem;
        }

        .surface,
        .stat-card,
        .list-card,
        .highlight-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 1.4rem;
        }

        .hero-grid,
        .stats-grid,
        .panel-grid,
        .highlight-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .ghost-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem 1.1rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .membership-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            background: rgba(255, 107, 53, 0.18);
            color: #ffd7ca;
        }

        .metric-label,
        .muted-copy {
            color: var(--text-color);
        }

        .placement-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.14);
            color: #bfd5ff;
            font-size: 0.85rem;
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
                <ul id="nav-list" class="nav__list"></ul>
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
        <section class="profile-shell container" id="profile-shell">
            <div class="surface">Loading player profile...</div>
        </section>
    </main>

    <a href="#top" class="scrollup" id="scroll-up">
        <i class="ri-arrow-up-line"></i>
    </a>

    <script src="../js/scrollreveal.min.js"></script>
    <script src="../js/behaviour.js?v=20260409-1"></script>
    <script>
        const playerId = <?php echo $playerId; ?>;

        function placementValue(label, fallbackRank) {
            if (label) {
                return label;
            }

            if (fallbackRank) {
                return `#${fallbackRank}`;
            }

            return "No recorded finish";
        }

        function renderTournamentCards(tournaments) {
            if (!tournaments || tournaments.length === 0) {
                return '<p class="muted-copy">No tournament history is available yet.</p>';
            }

            return tournaments.map((tournament) => {
                const finish = placementValue(tournament.placement_label, tournament.final_rank);
                return `
                    <article class="list-card">
                        <h3>${tournament.tour_title}</h3>
                        <p class="muted-copy" style="margin-top: 0.5rem;">${tournament.tour_type} - ${tournament.status}</p>
                        <p class="muted-copy" style="margin-top: 0.35rem;">Entry status: ${tournament.player_status}</p>
                        ${tournament.final_rank !== null || tournament.placement_label ? `<div class="placement-pill" style="margin-top: 0.75rem;">Finish: ${finish}</div>` : ''}
                        <a href="tournament_details.php?id=${tournament.tour_id}" class="ghost-button" style="margin-top: 0.85rem;">Open tournament</a>
                    </article>
                `;
            }).join('');
        }

        function renderPlacementHighlights(highlights) {
            if (!highlights || highlights.length === 0) {
                return '<p class="muted-copy">No completed placements are recorded yet.</p>';
            }

            return highlights.map((highlight) => `
                <article class="highlight-card">
                    <div class="placement-pill">${placementValue(highlight.placement_label, highlight.final_rank)}</div>
                    <h3 style="margin-top: 0.8rem;">${highlight.tour_title}</h3>
                    <p class="muted-copy" style="margin-top: 0.45rem;">${highlight.tour_type}</p>
                    ${highlight.winner_label ? `<p class="muted-copy" style="margin-top: 0.35rem;">Winner: ${highlight.winner_label}</p>` : ''}
                    <a href="tournament_details.php?id=${highlight.tour_id}" class="ghost-button" style="margin-top: 0.9rem;">View tournament</a>
                </article>
            `).join('');
        }

        function renderRecentResults(results) {
            if (!results || results.length === 0) {
                return '<p class="muted-copy">No completed public match history is available yet.</p>';
            }

            return results.map((result) => `
                <article class="list-card">
                    <strong>${result.player1_name || 'TBD'} ${result.player1_surname || ''} ${result.player1_score} - ${result.player2_score} ${result.player2_name || 'TBD'} ${result.player2_surname || ''}</strong>
                    <p class="muted-copy" style="margin-top: 0.45rem;">${result.tour_title}</p>
                    <p class="muted-copy">${result.match_date} at ${String(result.match_time || '').slice(0, 5)}</p>
                </article>
            `).join('');
        }

        function renderProfile(data) {
            const shell = document.getElementById('profile-shell');
            const player = data.player;
            const displayName = `${player.plr_name || ''} ${player.plr_surname || ''}`.trim() || 'Club Player';
            const membershipLabel = (player.membership_status || 'not_submitted').replaceAll('_', ' ');
            const bestFinish = data.stats.best_finish !== null ? `#${data.stats.best_finish}` : 'No ranking yet';

            shell.innerHTML = `
                <section class="surface">
                    <div class="hero-grid">
                        <div>
                            <div class="membership-chip"><i class="ri-user-star-line"></i> ${membershipLabel}</div>
                            <h1 style="margin-top: 1rem;">${displayName}</h1>
                            <p class="muted-copy" style="margin-top: 0.75rem;">Player handle: ${player.plr_username || player.user_name || 'club-player'}</p>
                            <p class="muted-copy" style="margin-top: 0.35rem;">Best recorded finish: ${bestFinish}</p>
                        </div>
                        <div>
                            <h3>Public profile</h3>
                            <p class="muted-copy" style="margin-top: 0.75rem;">
                                This view is meant for public-safe tournament context: placements, recent results, and current club visibility.
                            </p>
                            <div class="highlight-grid" style="margin-top: 1rem;">
                                <div class="highlight-card">
                                    <strong>${data.stats.titles}</strong>
                                    <p class="metric-label" style="margin-top: 0.45rem;">Titles</p>
                                </div>
                                <div class="highlight-card">
                                    <strong>${data.stats.podium_finishes}</strong>
                                    <p class="metric-label" style="margin-top: 0.45rem;">Podium finishes</p>
                                </div>
                            </div>
                            <a href="tournaments.html" class="ghost-button" style="margin-top: 1rem;">Browse tournaments</a>
                        </div>
                    </div>
                </section>

                <section class="stats-grid">
                    <article class="stat-card"><strong>${data.stats.registered_tournaments}</strong><p class="metric-label" style="margin-top: 0.45rem;">Registered tournaments</p></article>
                    <article class="stat-card"><strong>${data.stats.completed_tournaments}</strong><p class="metric-label" style="margin-top: 0.45rem;">Completed tournaments</p></article>
                    <article class="stat-card"><strong>${data.stats.combined_matches_won} / ${data.stats.combined_matches_played}</strong><p class="metric-label" style="margin-top: 0.45rem;">Total wins / played</p></article>
                    <article class="stat-card"><strong>${data.stats.combined_win_rate}%</strong><p class="metric-label" style="margin-top: 0.45rem;">Overall win rate</p></article>
                    <article class="stat-card"><strong>${data.stats.runner_up_finishes}</strong><p class="metric-label" style="margin-top: 0.45rem;">Runner-up finishes</p></article>
                    <article class="stat-card"><strong>${data.stats.top_eight_finishes}</strong><p class="metric-label" style="margin-top: 0.45rem;">Top-eight finishes</p></article>
                </section>

                <section class="surface">
                    <h2>Placement Highlights</h2>
                    <div class="highlight-grid" style="margin-top: 1rem;">
                        ${renderPlacementHighlights(data.placement_highlights)}
                    </div>
                </section>

                <section class="panel-grid">
                    <div class="surface">
                        <h2>Tournament History</h2>
                        <div style="display: grid; gap: 0.85rem; margin-top: 1rem;">
                            ${renderTournamentCards(data.tournaments)}
                        </div>
                    </div>
                    <div class="surface">
                        <h2>Recent Results</h2>
                        <div style="display: grid; gap: 0.85rem; margin-top: 1rem;">
                            ${renderRecentResults(data.recent_results)}
                        </div>
                    </div>
                </section>
            `;
        }

        async function loadPlayerProfile() {
            const data = await window.appFetchJson(`../services/get_public_player_profile.php?id=${playerId}`);
            if (!data?.success) {
                throw new Error(data?.message || 'Failed to load player profile.');
            }

            renderProfile(data);
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadPlayerProfile().catch((error) => {
                document.getElementById('profile-shell').innerHTML = `<div class="surface">${error.message}</div>`;
            });
        });
    </script>
</body>
</html>
