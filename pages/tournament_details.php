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
            background: linear-gradient(180deg, rgba(255, 124, 77, 0.54), rgba(96, 165, 250, 0.48));
        }

        .read-bracket-node.has-incoming::after {
            content: '';
            position: absolute;
            left: -18px;
            top: 50%;
            width: 18px;
            height: 2px;
            border-radius: 999px;
            background: rgba(255, 124, 77, 0.44);
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
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.92), rgba(12, 18, 29, 0.94));
            text-align: left;
            color: #fff;
            cursor: pointer;
            transition: transform 0.18s ease, border-color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
        }

        .read-bracket-matchup:hover {
            transform: translateY(-1px);
            border-color: rgba(255, 124, 77, 0.4);
        }

        .read-bracket-matchup.is-active {
            border-color: rgba(255, 124, 77, 0.5);
            background:
                radial-gradient(circle at top right, rgba(255, 124, 77, 0.2), transparent 32%),
                linear-gradient(180deg, rgba(34, 46, 68, 0.96), rgba(15, 23, 42, 0.96));
            box-shadow: 0 0 0 3px rgba(255, 124, 77, 0.16);
        }

        .read-bracket-matchup.state-scheduled {
            border-color: rgba(255, 255, 255, 0.14);
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.92), rgba(12, 18, 29, 0.94));
        }

        .read-bracket-matchup.state-waiting {
            border-color: rgba(96, 165, 250, 0.36);
            background:
                radial-gradient(circle at top right, rgba(96, 165, 250, 0.16), transparent 34%),
                linear-gradient(180deg, rgba(16, 31, 53, 0.94), rgba(11, 22, 37, 0.96));
        }

        .read-bracket-matchup.state-ready {
            border-color: rgba(251, 191, 36, 0.44);
            background:
                radial-gradient(circle at top right, rgba(251, 191, 36, 0.18), transparent 34%),
                linear-gradient(180deg, rgba(54, 35, 14, 0.92), rgba(30, 24, 14, 0.96));
        }

        .read-bracket-matchup.state-live {
            border-color: rgba(255, 124, 77, 0.52);
            background:
                radial-gradient(circle at top right, rgba(255, 124, 77, 0.24), transparent 34%),
                linear-gradient(180deg, rgba(59, 27, 16, 0.92), rgba(38, 17, 12, 0.96));
        }

        .read-bracket-matchup.state-completed {
            border-color: rgba(34, 197, 94, 0.46);
            background:
                radial-gradient(circle at top right, rgba(34, 197, 94, 0.2), transparent 34%),
                linear-gradient(180deg, rgba(18, 44, 29, 0.92), rgba(12, 26, 19, 0.96));
        }

        .read-bracket-matchup.has-outgoing::after {
            content: '';
            position: absolute;
            right: -18px;
            top: 50%;
            width: 18px;
            height: 2px;
            border-radius: 999px;
            background: rgba(255, 124, 77, 0.44);
        }

        .read-bracket-matchup.is-placeholder {
            cursor: default;
            border-style: dashed;
            border-color: rgba(148, 163, 184, 0.34);
            background:
                repeating-linear-gradient(
                    135deg,
                    rgba(30, 41, 59, 0.9),
                    rgba(30, 41, 59, 0.9) 10px,
                    rgba(51, 65, 85, 0.9) 10px,
                    rgba(51, 65, 85, 0.9) 20px
                );
        }

        .read-bracket-matchup.is-placeholder:hover {
            transform: none;
            border-color: rgba(148, 163, 184, 0.34);
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
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
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

        .bracket-section {
            position: relative;
        }

        .bracket-section-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .bracket-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .bracket-toolbar-copy {
            max-width: 760px;
            color: var(--text-color);
        }

        .bracket-path-nav {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .public-bracket-view-toggle {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .bracket-path-button {
            padding-inline: 1rem;
        }

        .bracket-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 0 0 1rem;
        }

        .bracket-group-stack {
            display: grid;
            gap: 1.1rem;
        }

        .bracket-group-stack--merged {
            grid-template-columns: minmax(0, 1.12fr) minmax(240px, 0.78fr) minmax(0, 1.12fr);
            grid-template-areas: "winners final losers";
            align-items: start;
        }

        .bracket-group-stack--merged [data-public-bracket-group="Winners Bracket"] {
            grid-area: winners;
        }

        .bracket-group-stack--merged [data-public-bracket-group="Losers Bracket"] {
            grid-area: losers;
        }

        .bracket-group-stack--merged [data-public-bracket-group="Grand Final"] {
            grid-area: final;
            align-self: center;
        }

        .bracket-group {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
        }

        .bracket-group h3 {
            margin: 0 0 0.35rem;
        }

        .bracket-group p {
            margin: 0 0 0.9rem;
            color: var(--text-color);
        }

        .legend-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.5rem 0.85rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.04);
            color: #fff;
            font-size: 0.85rem;
        }

        .legend-swatch {
            width: 0.85rem;
            height: 0.85rem;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .legend-swatch--waiting { background: #60a5fa; }
        .legend-swatch--ready { background: #fbbf24; }
        .legend-swatch--live { background: #ff7c4d; }
        .legend-swatch--completed { background: #22c55e; }

        .bracket-section:fullscreen {
            padding: 1.4rem;
            overflow: auto;
            background:
                radial-gradient(circle at top right, rgba(255, 107, 53, 0.18), transparent 34%),
                linear-gradient(180deg, rgba(6, 11, 22, 0.99), rgba(2, 6, 18, 0.99));
        }

        .bracket-section:fullscreen .read-bracket-shell {
            max-height: calc(100vh - 18rem);
            overflow: auto;
            padding-right: 0.6rem;
        }

        .bracket-section:fullscreen .selected-match-card {
            margin-bottom: 0.75rem;
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

        .detail-button-secondary.is-active {
            background: rgba(255, 107, 53, 0.16);
            border-color: rgba(255, 107, 53, 0.32);
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

        @media (max-width: 820px) {
            .bracket-section-head,
            .bracket-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .bracket-group-stack--merged {
                grid-template-columns: 1fr;
                grid-template-areas: none;
            }
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
        let selectedPublicBracketView = 'merged';

        function roundTitle(roundNumber, totalRounds) {
            if (totalRounds <= 1 || roundNumber >= totalRounds) {
                return 'Final';
            }
            if (roundNumber === totalRounds - 1) {
                return 'Semifinal';
            }
            if (roundNumber === totalRounds - 2) {
                return 'Quarterfinal';
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

        function detailEscapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function bracketVisualState(match) {
            const status = String(match.match_status || '').toLowerCase();
            if (status === 'completed') {
                return 'completed';
            }
            if (status === 'in progress' || status === 'in_progress') {
                return 'live';
            }
            if (match.player1_id && match.player2_id) {
                return 'ready';
            }
            if (match.player1_id || match.player2_id) {
                return 'waiting';
            }
            return 'scheduled';
        }

        function bracketGroupOrder(label) {
            const order = {
                'Winners Bracket': 1,
                'Elimination': 1,
                'Knockout': 1,
                'Losers Bracket': 2,
                'Grand Final': 3,
                'Third Place Playoff': 4
            };

            return order[label] || 99;
        }

        function bracketRoundTitle(label, roundNumber, totalRounds) {
            if (label === 'Grand Final') {
                return 'Grand Final';
            }
            if (label === 'Third Place Playoff') {
                return 'Third Place Playoff';
            }
            if (label === 'Winners Bracket') {
                return `Winners ${roundTitle(roundNumber, totalRounds)}`;
            }
            if (label === 'Losers Bracket') {
                if (totalRounds <= 1 || roundNumber >= totalRounds) {
                    return 'Losers Final';
                }
                if (roundNumber === totalRounds - 1) {
                    return 'Losers Semifinal';
                }
                return `Losers Round ${roundNumber}`;
            }

            return roundTitle(roundNumber, totalRounds);
        }

        function bracketDomKey(value) {
            return String(value || 'group')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || 'group';
        }

        function renderBracketLegend(includeBracketPaths = false) {
            return `
                <div class="bracket-legend">
                    <span class="legend-chip"><span class="legend-swatch legend-swatch--waiting"></span> Waiting for an opponent</span>
                    <span class="legend-chip"><span class="legend-swatch legend-swatch--ready"></span> Ready to play</span>
                    <span class="legend-chip"><span class="legend-swatch legend-swatch--live"></span> Live or highlighted</span>
                    <span class="legend-chip"><span class="legend-swatch legend-swatch--completed"></span> Result recorded</span>
                    ${includeBracketPaths ? '<span class="legend-chip">Winners, losers, and grand final paths stay in one merged layout below.</span>' : ''}
                </div>
            `;
        }

        function playerPlacementLabel(player) {
            if (player.placement_label) {
                return player.placement_label;
            }
            if (player.final_rank !== null && player.final_rank !== undefined && player.final_rank !== '') {
                return `#${player.final_rank}`;
            }
            return '';
        }

        function renderPlacementTable(players) {
            const rows = (players || [])
                .filter((player) => playerPlacementLabel(player))
                .sort((left, right) => {
                    const leftRank = Number.isFinite(Number(left.final_rank)) ? Number(left.final_rank) : 9999;
                    const rightRank = Number.isFinite(Number(right.final_rank)) ? Number(right.final_rank) : 9999;
                    if (leftRank !== rightRank) {
                        return leftRank - rightRank;
                    }
                    return `${left.plr_name} ${left.plr_surname}`.localeCompare(`${right.plr_name} ${right.plr_surname}`, undefined, { sensitivity: 'base' });
                });

            if (rows.length === 0) {
                return '<p style="color: var(--text-color);">Placements will appear here once elimination results start settling the bracket.</p>';
            }

            return `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Placement</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map((player) => `
                            <tr>
                                <td>${player.final_rank ?? '-'}</td>
                                <td><a href="player_profile.php?id=${player.plr_idNum}" style="color:#fff; text-decoration:none;">${detailEscapeHtml(player.plr_name)} ${detailEscapeHtml(player.plr_surname)}</a></td>
                                <td>${detailEscapeHtml(playerPlacementLabel(player))}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        function bracketDisplayTitle(match, bracketLabel, totalRounds) {
            const roundName = bracketRoundTitle(bracketLabel, Number(match.round_number || 1), totalRounds);
            return `${roundName} - Match ${match.match_id}`;
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
                    ${matches.map((match) => {
                        const teamMeta = match.player1_team_name && match.player2_team_name
                            ? `<p style="color: var(--text-color); margin-top: 0.55rem;">${detailEscapeHtml(match.player1_team_name)} vs ${detailEscapeHtml(match.player2_team_name)}</p>`
                            : '';

                        return `
                        <article class="match-card">
                            <div class="pill">${match.bracket || (match.group_number ? `Group ${match.group_number}` : 'Fixture')}</div>
                            <h3>${playerLabel(match, 'player1')} vs ${playerLabel(match, 'player2')}</h3>
                            ${teamMeta}
                            <p style="color: var(--text-color); margin-top: 0.75rem;">
                                ${match.match_date} at ${match.match_time}
                            </p>
                            <p style="margin-top: 0.5rem;">
                                ${match.match_status === 'Completed' ? `<strong>${match.player1_score} - ${match.player2_score}</strong>` : 'Scheduled'}
                            </p>
                        </article>
                    `;
                    }).join('')}
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

        function renderSelectedBracketMatch(match, group) {
            if (!match) {
                return '';
            }

            const hasScore = match.player1_score !== null && match.player2_score !== null;
            return `
                <article class="surface selected-match-card">
                    <div class="read-bracket-meta">
                        <strong>${bracketDisplayTitle(match, group.label, group.rounds.length)}</strong>
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
                        <span>${group.label || (match.bracket || (match.group_number ? `Group ${match.group_number}` : 'Knockout fixture'))}</span>
                    </div>
                    <div style="margin-top: 1rem;">
                        <span class="selected-match-result">${hasScore ? `${match.player1_score} - ${match.player2_score}` : 'Waiting for result'}</span>
                    </div>
                </article>
            `;
        }

        function renderBracketPathNav(groupEntries) {
            if (!Array.isArray(groupEntries) || groupEntries.length < 2) {
                return '';
            }

            return `
                <div class="bracket-path-nav">
                    ${groupEntries.map((group) => `
                        <button
                            type="button"
                            class="detail-button-secondary bracket-path-button"
                            onclick="focusPublicBracketGroup('${bracketDomKey(group.key)}')"
                        >
                            ${detailEscapeHtml(group.label)}
                        </button>
                    `).join('')}
                </div>
            `;
        }

        function renderBracketViewToggle(groupEntries, tournamentType) {
            if (tournamentType !== 'Double Elimination' || !Array.isArray(groupEntries) || groupEntries.length < 2) {
                return '';
            }

            const views = [
                { key: 'merged', label: 'Merged Bracket' },
                { key: 'Winners Bracket', label: 'Winners Bracket' },
                { key: 'Losers Bracket', label: 'Losers Bracket' }
            ];

            if (groupEntries.some((group) => group.rawLabel === 'Grand Final')) {
                views.push({ key: 'Grand Final', label: 'Grand Final' });
            }

            return `
                <div class="public-bracket-view-toggle">
                    ${views.map((view) => `
                        <button
                            type="button"
                            class="detail-button-secondary bracket-path-button ${selectedPublicBracketView === view.key ? 'is-active' : ''}"
                            onclick="setPublicBracketView('${detailEscapeHtml(view.key)}')"
                        >
                            ${detailEscapeHtml(view.label)}
                        </button>
                    `).join('')}
                </div>
            `;
        }

        function renderBracket(matches, tournamentType) {
            const knockoutMatches = (matches || []).filter((match) => match.group_number === null);
            if (knockoutMatches.length === 0) {
                return '<p style="color: var(--text-color);">A knockout bracket will appear here once elimination fixtures exist.</p>';
            }

            const groupsMap = new Map();
            knockoutMatches.forEach((match) => {
                const rawLabel = String(match.bracket || '').trim() || 'Elimination';
                const groupKey = tournamentType === 'Double Elimination'
                    ? rawLabel
                    : (rawLabel === 'Third Place Playoff' ? 'Third Place Playoff' : 'primary');
                const groupLabel = tournamentType === 'Double Elimination'
                    ? rawLabel
                    : (rawLabel === 'Third Place Playoff' ? 'Third Place Playoff' : (rawLabel === 'Knockout' ? 'Knockout Bracket' : 'Tournament Bracket'));
                const roundNumber = Number(match.round_number || 1);

                if (!groupsMap.has(groupKey)) {
                    groupsMap.set(groupKey, {
                        key: groupKey,
                        rawLabel,
                        label: groupLabel,
                        rounds: new Map(),
                        matches: []
                    });
                }

                const group = groupsMap.get(groupKey);
                if (!group.rounds.has(roundNumber)) {
                    group.rounds.set(roundNumber, []);
                }
                group.rounds.get(roundNumber).push(match);
                group.matches.push(match);
            });

            const groupEntries = Array.from(groupsMap.values())
                .map((group) => ({
                    ...group,
                    rounds: Array.from(group.rounds.entries()).sort((left, right) => left[0] - right[0])
                }))
                .sort((left, right) => {
                    const orderDelta = bracketGroupOrder(left.rawLabel) - bracketGroupOrder(right.rawLabel);
                    if (orderDelta !== 0) {
                        return orderDelta;
                    }

                    return left.label.localeCompare(right.label, undefined, { sensitivity: 'base' });
                });

            const visibleGroupEntries = tournamentType === 'Double Elimination' && selectedPublicBracketView !== 'merged'
                ? groupEntries.filter((group) => group.rawLabel === selectedPublicBracketView)
                : groupEntries;
            const renderedGroups = visibleGroupEntries.length > 0 ? visibleGroupEntries : groupEntries;
            const visibleMatches = renderedGroups.flatMap((group) => group.matches);
            const selectedMatch = visibleMatches.find((match) => Number(match.match_id) === Number(selectedBracketMatchId)) || visibleMatches[0] || knockoutMatches[0];
            const selectedGroup = renderedGroups.find((group) => group.matches.some((match) => Number(match.match_id) === Number(selectedMatch?.match_id))) || renderedGroups[0];
            const groupStackClass = tournamentType === 'Double Elimination' && selectedPublicBracketView === 'merged'
                ? 'bracket-group-stack bracket-group-stack--merged'
                : 'bracket-group-stack';

            return `
                ${renderBracketLegend(groupEntries.length > 1)}
                <div class="bracket-toolbar">
                    <p class="bracket-toolbar-copy">
                        ${tournamentType === 'Double Elimination'
                            ? 'Start from the merged bracket for the full tournament picture, then switch to a single path when you want to inspect just the winners side, losers side, or the final.'
                            : 'Open focus mode for a larger, scrollable bracket view when the elimination paths get crowded.'}
                    </p>
                    ${groupEntries.length > 1 ? '<span class="legend-chip">Bracket placeholders keep bye lines visible even when the entrant count is not a power of two.</span>' : ''}
                </div>
                ${renderBracketViewToggle(groupEntries, tournamentType)}
                ${tournamentType !== 'Double Elimination' ? renderBracketPathNav(groupEntries) : ''}
                <div class="${groupStackClass}">
                    ${renderedGroups.map((group) => {
                        const slotCount = Math.pow(2, group.rounds.length);
                        return `
                            <div class="bracket-group" id="public-bracket-group-${bracketDomKey(group.key)}" data-public-bracket-group="${detailEscapeHtml(group.rawLabel)}">
                                <h3>${group.label}</h3>
                                <p>${group.rawLabel === 'Grand Final' ? 'The winners-bracket champion meets the last survivor from the lower path here.' : (group.rawLabel === 'Third Place Playoff' ? 'The semifinal losers meet here to settle third and fourth place.' : 'Follow this path round by round through the connected bracket below.')}</p>
                                <div class="read-bracket-shell">
                                    <div class="read-bracket">
                                        ${group.rounds.map(([roundNumber, roundMatches], roundIndex) => `
                                            <div class="read-bracket-round">
                                                <h3>${bracketRoundTitle(group.rawLabel, roundNumber, group.rounds.length)}</h3>
                                                <div class="read-bracket-lane" style="--slot-count: ${slotCount};">
                                                    ${(() => {
                                                        const renderRoundMatches = [...roundMatches];
                                                        const expectedRoundMatches = Math.max(1, Math.floor(slotCount / Math.pow(2, roundIndex + 1)));
                                                        while (renderRoundMatches.length < expectedRoundMatches) {
                                                            renderRoundMatches.push({ is_placeholder: true, match_id: `placeholder-${group.key}-${roundNumber}-${renderRoundMatches.length}` });
                                                        }
                                                        return renderRoundMatches.map((match, matchIndex) => {
                                                            const rowSpan = Math.pow(2, roundIndex + 1);
                                                            const rowStart = (matchIndex * rowSpan) + 1;
                                                            const rowEnd = rowStart + rowSpan;
                                                            const isPlaceholder = Boolean(match.is_placeholder);
                                                            const isActive = !isPlaceholder && Number(selectedMatch?.match_id) === Number(match.match_id);
                                                            const visualState = isPlaceholder ? 'scheduled' : bracketVisualState(match);
                                                            return `
                                                                <div class="read-bracket-node ${roundIndex > 0 ? 'has-incoming' : ''}" style="grid-row: ${rowStart} / ${rowEnd};">
                                                                    <button
                                                                        type="button"
                                                                        class="read-bracket-matchup ${isPlaceholder ? 'is-placeholder' : ''} state-${visualState} ${roundIndex < group.rounds.length - 1 ? 'has-outgoing' : ''} ${isActive ? 'is-active' : ''}"
                                                                        ${isPlaceholder ? 'disabled' : `onclick="showBracketMatch(${match.match_id})"`}
                                                                    >
                                                                        <div class="read-bracket-summary">
                                                                            <span>${isPlaceholder ? 'Bye Slot' : `Match ${match.match_id}`}</span>
                                                                            <span>${isPlaceholder ? 'Auto-advance' : match.match_status}</span>
                                                                        </div>
                                                                        <div class="read-bracket-vs">
                                                                            <div class="read-bracket-player">
                                                                                <span>Top</span>
                                                                                <strong>${isPlaceholder ? 'Bye / no fixture' : playerLabel(match, 'player1')}</strong>
                                                                            </div>
                                                                            <div class="read-bracket-player">
                                                                                <span>Bottom</span>
                                                                                <strong>${isPlaceholder ? 'Bracket spacer' : playerLabel(match, 'player2')}</strong>
                                                                            </div>
                                                                        </div>
                                                                    </button>
                                                                </div>
                                                            `;
                                                        }).join('');
                                                    })()}
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
                ${selectedMatch ? renderSelectedBracketMatch(selectedMatch, selectedGroup) : ''}
            `;
        }

        function renderTournamentPage(data) {
            const tournament = data.tournament;
            const shell = document.getElementById('detail-shell');
            const knockoutBracket = tournament.tour_type !== 'Group' ? renderBracket(data.matches, tournament.tour_type) : '';
            const hasPlayerPlacements = (data.players || []).some((player) => playerPlacementLabel(player));

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
                            <p>${data.matches.some((match) => match.group_number === null) ? (tournament.tour_type === 'Double Elimination' ? 'Merged, winners-only, losers-only, and grand-final views are available below' : 'Available below') : 'Will appear after elimination fixtures exist'}</p>
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
                                        ${hasPlayerPlacements ? '<th>Placement</th>' : ''}
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.players.map((player) => `
                                        <tr>
                                            <td><a href="player_profile.php?id=${player.plr_idNum}" style="color:#fff; text-decoration:none;">${player.plr_name} ${player.plr_surname}</a></td>
                                            <td>${player.player_status}</td>
                                            <td>${player.group_number ?? '-'}</td>
                                            ${hasPlayerPlacements ? `<td>${detailEscapeHtml(playerPlacementLabel(player) || '-')}</td>` : ''}
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
                        <section class="surface bracket-section" id="publicBracketSection">
                            <div class="bracket-section-head">
                                <div>
                                    <h2>Tournament Bracket</h2>
                                    <p style="color: var(--text-color); margin-top: 0.6rem;">${tournament.tour_type === 'Double Elimination' ? 'Follow the winners-bracket, losers-bracket, and grand-final flow visually on the main website.' : 'Follow the knockout structure visually on the main website when elimination fixtures are available.'}</p>
                                </div>
                                <button type="button" class="detail-button-secondary" onclick="togglePublicBracketFocus()">
                                    <i class="ri-fullscreen-line"></i> Open focus mode
                                </button>
                            </div>
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
                                : renderPlacementTable(data.players)
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
                            <h2>${(data.matches || []).length > 0 ? 'Player Fixtures' : 'Team Fixtures'}</h2>
                            ${(data.matches || []).length > 0 ? renderIndividualMatches(data.matches) : renderTeamMatches(data.team_matches)}
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

        function setPublicBracketView(viewKey) {
            selectedPublicBracketView = viewKey || 'merged';
            if (!currentTournamentData) {
                return;
            }

            const visibleMatches = (currentTournamentData.matches || []).filter((match) => {
                if (selectedPublicBracketView === 'merged') {
                    return match.group_number === null;
                }

                return match.group_number === null && String(match.bracket || '').trim() === selectedPublicBracketView;
            });

            if (!visibleMatches.some((match) => Number(match.match_id) === Number(selectedBracketMatchId))) {
                selectedBracketMatchId = visibleMatches[0] ? Number(visibleMatches[0].match_id) : null;
            }

            renderTournamentPage(currentTournamentData);
        }

        async function togglePublicBracketFocus() {
            const bracketSection = document.getElementById('publicBracketSection');
            if (!bracketSection) {
                return;
            }

            try {
                if (document.fullscreenElement === bracketSection) {
                    await document.exitFullscreen();
                    return;
                }

                if (bracketSection.requestFullscreen) {
                    await bracketSection.requestFullscreen();
                }
            } catch (error) {
                console.warn('Failed to toggle public bracket focus mode:', error);
            }
        }

        function focusPublicBracketGroup(groupKey) {
            const target = document.getElementById(`public-bracket-group-${groupKey}`);
            if (!target) {
                return;
            }

            target.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
        }

        async function loadTournamentDetails() {
            if (!tournamentId) {
                throw new Error('Tournament id is missing.');
            }

            const data = await window.appFetchJson(`../services/get_tournament_details.php?id=${tournamentId}`);
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
                document.getElementById('detail-shell').innerHTML = `<div class="surface">${detailEscapeHtml(error.message)}</div>`;
            });
        });

        window.showBracketMatch = showBracketMatch;
        window.setPublicBracketView = setPublicBracketView;
        window.togglePublicBracketFocus = togglePublicBracketFocus;
        window.focusPublicBracketGroup = focusPublicBracketGroup;
    </script>
</body>
</html>
