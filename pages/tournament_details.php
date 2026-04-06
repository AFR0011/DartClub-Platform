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
            gap: 1.75rem;
        }

        .surface,
        .match-card,
        .team-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 22px;
            padding: 1.5rem;
        }

        .hero-grid,
        .meta-grid,
        .results-grid,
        .roster-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .hero-surface {
            background:
                radial-gradient(circle at top right, rgba(255, 107, 53, 0.12), transparent 34%),
                radial-gradient(circle at bottom left, rgba(255, 255, 255, 0.06), transparent 40%),
                rgba(255, 255, 255, 0.04);
        }

        .summary-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 1.1rem 1.15rem;
        }

        .summary-card strong {
            display: block;
            color: #fff;
            font-size: 1.55rem;
        }

        .summary-card span {
            color: var(--text-color);
            font-size: 0.92rem;
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

        .read-bracket-shell {
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .read-bracket {
            --bracket-track: 62px;
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(220px, 220px);
            gap: 26px;
            min-width: max-content;
            align-items: start;
        }

        .read-bracket-round {
            display: grid;
            gap: 12px;
        }

        .read-bracket-round h3 {
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.92rem;
            color: var(--text-color);
        }

        .read-bracket-lane {
            position: relative;
            display: grid;
            grid-template-rows: repeat(var(--slot-count, 2), var(--bracket-track));
            min-height: calc(var(--slot-count, 2) * var(--bracket-track));
        }

        .read-bracket-node {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 0;
        }

        .read-bracket-node.has-incoming::before {
            content: '';
            position: absolute;
            left: -18px;
            top: 25%;
            bottom: 25%;
            width: 2px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
        }

        .read-bracket-node.has-incoming::after {
            content: '';
            position: absolute;
            left: -18px;
            top: 50%;
            width: 18px;
            height: 2px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
        }

        .read-bracket-card {
            position: relative;
            width: 100%;
            padding: 1rem;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.18);
        }

        .read-bracket-matchup {
            position: relative;
            width: 100%;
            display: grid;
            gap: 0.4rem;
            padding: 0.7rem 0.8rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.18);
            text-align: left;
            color: #fff;
            cursor: pointer;
            transition: transform 0.18s ease, border-color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
        }

        .read-bracket-matchup:hover {
            transform: translateY(-1px);
            border-color: rgba(255, 107, 53, 0.28);
        }

        .read-bracket-matchup.is-active {
            border-color: rgba(255, 107, 53, 0.38);
            background:
                radial-gradient(circle at top right, rgba(255, 107, 53, 0.12), transparent 32%),
                rgba(255, 255, 255, 0.07);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.12);
        }

        .read-bracket-matchup.has-outgoing::after {
            content: '';
            position: absolute;
            right: -18px;
            top: 50%;
            width: 18px;
            height: 2px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
        }

        .read-bracket-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-color);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .read-bracket-vs {
            display: grid;
            gap: 0.28rem;
        }

        .read-bracket-player {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
            padding: 0.35rem 0.55rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .read-bracket-player strong {
            color: #fff;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .read-bracket-player span {
            color: var(--text-color);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            flex-shrink: 0;
        }

        .read-bracket-meta,
        .selected-match-meta {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            color: var(--text-color);
            font-size: 0.88rem;
        }

        .selected-match-card {
            margin-top: 1.2rem;
            background:
                radial-gradient(circle at top right, rgba(255, 107, 53, 0.08), transparent 34%),
                rgba(255, 255, 255, 0.04);
        }

        .selected-match-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin-top: 1rem;
        }

        .selected-match-slot {
            padding: 0.95rem 1rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.03);
            display: grid;
            gap: 0.35rem;
        }

        .selected-match-slot strong {
            color: #fff;
        }

        .selected-match-result {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(255, 107, 53, 0.16);
            color: #ffd7ca;
            font-weight: 700;
        }

        .detail-button,
        .detail-button-secondary,
        .detail-button-danger,
        .detail-input,
        .detail-select,
        .detail-textarea {
            font: inherit;
        }

        .detail-button,
        .detail-button-secondary,
        .detail-button-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            padding: 0.82rem 1.15rem;
            border-radius: 999px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .detail-button {
            background: linear-gradient(180deg, #ff7c4d, #ef5a29);
            color: #fff;
            box-shadow: 0 10px 22px rgba(239, 90, 41, 0.2);
        }

        .detail-button-secondary {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .detail-button-danger {
            background: rgba(220, 38, 38, 0.12);
            border-color: rgba(220, 38, 38, 0.25);
            color: #fecaca;
        }

        .detail-button:hover,
        .detail-button-secondary:hover,
        .detail-button-danger:hover {
            transform: translateY(-1px);
        }

        .detail-input,
        .detail-select,
        .detail-textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(0, 0, 0, 0.18);
            color: #fff;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .detail-input:focus,
        .detail-select:focus,
        .detail-textarea:focus {
            outline: none;
            border-color: rgba(255, 107, 53, 0.38);
            box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.12);
            background: rgba(0, 0, 0, 0.26);
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
    <script src="../js/behaviour.js?v=20260402-2"></script>
    <script>
        const tournamentId = <?php echo $tourId; ?>;
        let currentTournamentData = null;
        let selectedBracketMatchId = null;

        function roundTitle(roundNumber, totalRounds) {
            const remainingRounds = totalRounds - roundNumber;
            if (remainingRounds <= 0) {
                return 'Final';
            }
            if (remainingRounds === 1) {
                return 'Semi Finals';
            }
            if (remainingRounds === 2) {
                return 'Quarter Finals';
            }
            return `Round ${roundNumber}`;
        }

        function playerLabel(match, slot) {
            const prefix = slot === 'player1' ? 'player1' : 'player2';
            const firstName = match[`${prefix}_name`] || '';
            const surname = match[`${prefix}_surname`] || '';
            const joinedName = `${firstName} ${surname}`.trim();
            return joinedName || 'TBD';
        }

        function playerProfileLink(match, slot) {
            const prefix = slot === 'player1' ? 'player1' : 'player2';
            const playerId = Number(match[`${prefix}_id`] || 0);
            const label = playerLabel(match, slot);
            if (!playerId) {
                return label;
            }

            return `<a href="player_profile.php?id=${playerId}" style="color:#fff; text-decoration:none; font-weight:700;">${label}</a>`;
        }

        function bracketDisplayTitle(match, totalRounds) {
            const roundName = roundTitle(Number(match.round_number || 1), totalRounds);
            return `${roundName} • Match ${match.match_id}`;
        }

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
                            <h3>${playerLabel(match, 'player1')} vs ${playerLabel(match, 'player2')}</h3>
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

        function renderSelectedBracketMatch(match, totalRounds) {
            if (!match) {
                return '';
            }

            const hasScore = match.player1_score !== null && match.player2_score !== null;
            return `
                <article class="surface selected-match-card">
                    <div class="read-bracket-meta">
                        <strong>${bracketDisplayTitle(match, totalRounds)}</strong>
                        <span>${match.match_status}</span>
                    </div>
                    <div class="selected-match-grid">
                        <div class="selected-match-slot">
                            <span>Top slot</span>
                            <strong>${playerProfileLink(match, 'player1')}</strong>
                        </div>
                        <div class="selected-match-slot">
                            <span>Bottom slot</span>
                            <strong>${playerProfileLink(match, 'player2')}</strong>
                        </div>
                    </div>
                    <div class="selected-match-meta" style="margin-top: 1rem;">
                        <span>${match.match_date} at ${String(match.match_time || '').slice(0, 5)}</span>
                        <span>${match.bracket || (match.group_number ? `Group ${match.group_number}` : 'Knockout fixture')}</span>
                    </div>
                    <div style="margin-top: 1rem;">
                        <span class="selected-match-result">${hasScore ? `${match.player1_score} - ${match.player2_score}` : 'Waiting for result'}</span>
                    </div>
                </article>
            `;
        }

        function renderBracket(matches) {
            const knockoutMatches = (matches || []).filter((match) => match.group_number === null);
            if (knockoutMatches.length === 0) {
                return '<p style="color: var(--text-color);">A knockout bracket will appear here once elimination fixtures exist.</p>';
            }

            const roundsMap = new Map();
            knockoutMatches.forEach((match) => {
                const roundNumber = Number(match.round_number || 1);
                if (!roundsMap.has(roundNumber)) {
                    roundsMap.set(roundNumber, []);
                }
                roundsMap.get(roundNumber).push(match);
            });

            const roundEntries = Array.from(roundsMap.entries()).sort((left, right) => left[0] - right[0]);
            const slotCount = Math.pow(2, roundEntries.length);
            const selectedMatch = knockoutMatches.find((match) => Number(match.match_id) === Number(selectedBracketMatchId)) || knockoutMatches[0];

            return `
                <div class="read-bracket-shell">
                    <div class="read-bracket">
                        ${roundEntries.map(([roundNumber, roundMatches], roundIndex) => `
                            <div class="read-bracket-round">
                                <h3>${roundTitle(roundNumber, roundEntries.length)}</h3>
                                <div class="read-bracket-lane" style="--slot-count: ${slotCount};">
                                    ${roundMatches.map((match, matchIndex) => {
                                        const rowSpan = Math.pow(2, roundIndex + 1);
                                        const rowStart = (matchIndex * rowSpan) + 1;
                                        const rowEnd = rowStart + rowSpan;
                                        const isActive = Number(selectedMatch.match_id) === Number(match.match_id);
                                        return `
                                            <div class="read-bracket-node ${roundIndex > 0 ? 'has-incoming' : ''}" style="grid-row: ${rowStart} / ${rowEnd};">
                                                <button
                                                    type="button"
                                                    class="read-bracket-matchup ${roundIndex < roundEntries.length - 1 ? 'has-outgoing' : ''} ${isActive ? 'is-active' : ''}"
                                                    onclick="showBracketMatch(${match.match_id})"
                                                >
                                                    <div class="read-bracket-summary">
                                                        <span>Match ${match.match_id}</span>
                                                        <span>${match.match_status}</span>
                                                    </div>
                                                    <div class="read-bracket-vs">
                                                        <div class="read-bracket-player">
                                                            <span>Top</span>
                                                            <strong>${playerLabel(match, 'player1')}</strong>
                                                        </div>
                                                        <div class="read-bracket-player">
                                                            <span>Bottom</span>
                                                            <strong>${playerLabel(match, 'player2')}</strong>
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ${renderSelectedBracketMatch(selectedMatch, roundEntries.length)}
            `;
        }

        function renderTournamentPage(data) {
            const tournament = data.tournament;
            const shell = document.getElementById('detail-shell');
            const knockoutBracket = tournament.tour_type !== 'Group' ? renderBracket(data.matches) : '';

            shell.innerHTML = `
                <section class="surface hero-surface">
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
                    ${tournament.tour_type !== 'Group' ? `
                        <div>
                            <strong>Bracket</strong>
                            <p>${data.matches.some((match) => match.group_number === null) ? 'Available below' : 'Will appear after elimination fixtures exist'}</p>
                        </div>
                    ` : ''}
                </div>
            </div>
        </section>

                <section class="summary-grid">
                    <article class="summary-card"><strong>${data.players.length}</strong><span>Registered entrants</span></article>
                    <article class="summary-card"><strong>${data.matches.length}</strong><span>Fixtures created</span></article>
                    <article class="summary-card"><strong>${data.recent_results.length}</strong><span>Recent results</span></article>
                    <article class="summary-card"><strong>${tournament.winner_label || 'TBD'}</strong><span>Current winner</span></article>
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
                                            <td><a href="player_profile.php?id=${player.plr_idNum}" style="color:#fff; text-decoration:none;">${player.plr_name} ${player.plr_surname}</a></td>
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

                ${
                  tournament.tour_type !== 'Group'
                    ? `
                        <section class="surface">
                            <h2>Tournament Bracket</h2>
                            <p style="color: var(--text-color); margin-top: 0.6rem; margin-bottom: 1rem;">Follow the knockout structure visually on the main website when elimination fixtures are available.</p>
                            ${knockoutBracket}
                        </section>
                      `
                    : ''
                }

                <section class="surface">
                    <h2>Competition View</h2>
                    ${
                      tournament.tour_type === 'Round Robin'
                        ? renderStandingsTable(data.standings)
                        : tournament.tour_type === 'League'
                            ? renderLeagueGroupTables(data.group_standings)
                            : tournament.tour_type === 'Group'
                                ? renderStandingsTable(data.team_standings, 'Team')
                                : '<p style="color: var(--text-color);">Elimination standings are represented through the bracket and fixture list below.</p>'
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
                            <h2>Fixtures List</h2>
                            ${renderIndividualMatches(data.matches)}
                        </section>
                      `
                }
            `;
        }

        function showBracketMatch(matchId) {
            if (!currentTournamentData) {
                return;
            }

            selectedBracketMatchId = Number(matchId);
            renderTournamentPage(currentTournamentData);
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

            currentTournamentData = data;
            if (selectedBracketMatchId === null) {
                const firstKnockoutMatch = (data.matches || []).find((match) => match.group_number === null);
                selectedBracketMatchId = firstKnockoutMatch ? Number(firstKnockoutMatch.match_id) : null;
            }
            renderTournamentPage(data);
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadTournamentDetails().catch((error) => {
                document.getElementById('detail-shell').innerHTML = `<div class="surface">${error.message}</div>`;
            });
        });

        window.showBracketMatch = showBracketMatch;
    </script>
</body>
</html>
