const TournamentAdminPage = { players: [], matches: {} };
let draggedBracketSlot = null;

function readTournamentPageData(source = document) {
    const dataNode = source.getElementById('tournament-page-data');
    if (dataNode) {
        try {
            return JSON.parse(dataNode.textContent || '{}');
        } catch (error) {
            console.error('Failed to parse tournament page data:', error);
        }
    }

    return window.TOURNAMENT_PAGE || { players: [], matches: {} };
}

function hydrateTournamentPageData(source = document) {
    const nextData = readTournamentPageData(source) || {};
    Object.keys(TournamentAdminPage).forEach((key) => {
        delete TournamentAdminPage[key];
    });
    Object.assign(TournamentAdminPage, nextData);
    TournamentAdminPage.players = Array.isArray(TournamentAdminPage.players) ? TournamentAdminPage.players : [];
    TournamentAdminPage.matches = TournamentAdminPage.matches && typeof TournamentAdminPage.matches === 'object'
        ? TournamentAdminPage.matches
        : {};
}

function getSectionStorageKey() {
    return `tournament-admin:${TournamentAdminPage.tourId || 'default'}:section`;
}

function getCurrentSectionName() {
    return document.querySelector('[data-section].active')?.dataset.section || null;
}

function rememberActiveSection(sectionName) {
    if (!sectionName) {
        return;
    }

    try {
        window.sessionStorage.setItem(getSectionStorageKey(), sectionName);
    } catch (error) {
        console.warn('Failed to persist tournament section state:', error);
    }
}

function readRememberedSection() {
    try {
        return window.sessionStorage.getItem(getSectionStorageKey());
    } catch (error) {
        return null;
    }
}

async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    return response.json();
}

function getMatchMeta(matchId) {
    const matches = TournamentAdminPage.matches || {};
    return matches[String(matchId)] || matches[matchId] || null;
}

function setElementText(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

function syncModalPlayerCard(linkId, labelId, playerLabel, profileUrl) {
    const link = document.getElementById(linkId);
    const label = document.getElementById(labelId);
    if (!link || !label) {
        return;
    }

    const safeLabel = playerLabel || 'TBD';
    if (profileUrl) {
        link.textContent = safeLabel;
        link.href = profileUrl;
        link.hidden = false;
        label.hidden = true;
    } else {
        label.textContent = safeLabel;
        label.hidden = false;
        link.hidden = true;
        link.removeAttribute('href');
    }
}

function syncModalResultState(match) {
    const hasBothPlayers = Boolean(match?.player1_id) && Boolean(match?.player2_id);
    const resultButton = document.getElementById('mf_save_result');
    const player1Input = document.getElementById('mf_p1s');
    const player2Input = document.getElementById('mf_p2s');
    if (!resultButton || !player1Input || !player2Input) {
        return;
    }

    resultButton.disabled = !hasBothPlayers;
    player1Input.disabled = !hasBothPlayers;
    player2Input.disabled = !hasBothPlayers;
}

function showSection(sectionName) {
    const sections = Array.from(document.querySelectorAll('[data-section]'));
    const sectionExists = sections.some((section) => section.dataset.section === sectionName);
    const nextSection = sectionExists ? sectionName : (sections[0]?.dataset.section || null);

    sections.forEach((section) => {
        section.classList.toggle('active', section.dataset.section === nextSection);
    });

    document.querySelectorAll('[data-section-button]').forEach((button) => {
        button.classList.toggle('active', button.dataset.sectionButton === nextSection);
    });

    rememberActiveSection(nextSection);
}

async function refreshMatches(options = {}) {
    const preserveSection = options.preserveSection !== false;
    const preserveScroll = options.preserveScroll !== false;
    const preferredSection = preserveSection ? (getCurrentSectionName() || readRememberedSection()) : null;
    const currentScrollY = preserveScroll ? window.scrollY : 0;

    const response = await fetch(window.location.href, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }
    });

    if (!response.ok) {
        throw new Error(`Failed to refresh tournament page (${response.status}).`);
    }

    const html = await response.text();
    const parser = new DOMParser();
    const nextDocument = parser.parseFromString(html, 'text/html');
    const nextContainer = nextDocument.querySelector('.container');
    const nextMatchModal = nextDocument.getElementById('matchModal');
    const nextLeagueModal = nextDocument.getElementById('leagueToolsModal');
    const nextDataNode = nextDocument.getElementById('tournament-page-data');

    if (!nextContainer || !nextMatchModal || !nextLeagueModal || !nextDataNode) {
        throw new Error('Refreshed tournament markup is incomplete.');
    }

    document.querySelector('.container')?.replaceWith(nextContainer);
    document.getElementById('matchModal')?.replaceWith(nextMatchModal);
    document.getElementById('leagueToolsModal')?.replaceWith(nextLeagueModal);
    document.getElementById('tournament-page-data')?.replaceWith(nextDataNode);

    hydrateTournamentPageData(document);
    initializeTournamentPage(preferredSection);

    if (preserveScroll) {
        window.requestAnimationFrame(() => {
            window.scrollTo({ top: currentScrollY, behavior: 'auto' });
        });
    }
}

function saveQuickMatchResult(event, matchId) {
    if (event) {
        event.preventDefault();
    }

    const form = event?.currentTarget;
    if (!form) {
        return false;
    }

    const formData = new FormData(form);
    const player1Raw = formData.get('player1_score');
    const player2Raw = formData.get('player2_score');
    if (player1Raw === '' || player2Raw === '') {
        window.alert('Enter both scores before saving.');
        return false;
    }

    const player1Score = parseInt(player1Raw, 10);
    const player2Score = parseInt(player2Raw, 10);

    (async () => {
        try {
            const data = await postJson('../../services/match_result.php', {
                match_id: matchId,
                player1_score: player1Score,
                player2_score: player2Score
            });
            if (!data.success) {
                throw new Error(data.message || 'Failed to record result');
            }
            await refreshMatches();
        } catch (error) {
            window.alert(error.message);
        }
    })();

    return false;
}

async function generateStructure() {
    const actionLabel = TournamentAdminPage.structureGenerated ? 'rebuild' : 'generate';
    if (!window.confirm(`This will ${actionLabel} the tournament structure from the current non-withdrawn entrants. Continue?`)) {
        return;
    }

    try {
        const response = await fetch('../../services/tournament_generate_structure.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tour_id: TournamentAdminPage.tourId })
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to generate structure');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

async function startTournament() {
    if (!window.confirm('This will close registration immediately and build or rebuild the tournament structure from the current non-withdrawn entrants. Continue?')) {
        return;
    }

    try {
        const response = await fetch('../../services/tournament_generate_structure.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tour_id: TournamentAdminPage.tourId,
                start_tournament: true
            })
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to start tournament');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

async function addMatch() {
    const matchDate = window.prompt('Match date (YYYY-MM-DD):', new Date().toISOString().slice(0, 10));
    if (!matchDate) {
        return;
    }

    const matchTime = window.prompt('Match time (HH:MM:SS):', '18:00:00');
    if (!matchTime) {
        return;
    }

    const roundNumber = parseInt(window.prompt('Round number:', '1') || '1', 10);
    const player1Id = window.prompt('Player 1 ID (optional):', '') || null;
    const player2Id = window.prompt('Player 2 ID (optional):', '') || null;
    const groupNumber = window.prompt('Group number (optional):', '') || null;
    const bracket = window.prompt('Bracket (optional):', '') || null;

    try {
        const data = await postJson('../../services/match_create.php', {
            tour_id: TournamentAdminPage.tourId,
            match_date: matchDate,
            match_time: matchTime,
            round_number: Number.isFinite(roundNumber) ? roundNumber : 1,
            player1_id: player1Id ? parseInt(player1Id, 10) : null,
            player2_id: player2Id ? parseInt(player2Id, 10) : null,
            group_number: groupNumber ? parseInt(groupNumber, 10) : null,
            bracket: bracket || null
        });
        if (!data.success) {
            throw new Error(data.message || 'Failed to create match');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

async function deleteMatch(matchId) {
    if (!window.confirm('Delete this match?')) {
        return;
    }

    try {
        const data = await postJson('../../services/match_delete.php', { match_id: matchId });
        if (!data.success) {
            throw new Error(data.message || 'Failed to delete match');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

async function openMatchModal(matchId) {
    document.getElementById('mf_match_id').value = matchId;
    document.getElementById('matchModal').style.display = 'flex';

    try {
        const response = await fetch(`../../services/match_get.php?id=${matchId}`);
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Match not found');
        }

        const match = data.match;
        const matchMeta = getMatchMeta(matchId) || {};
        document.getElementById('mf_date').value = match.match_date || '';
        document.getElementById('mf_time').value = (match.match_time || '').slice(0, 5);
        document.getElementById('mf_p1s').value = match.player1_score ?? '';
        document.getElementById('mf_p2s').value = match.player2_score ?? '';

        setElementText('mf_match_number', `Match ${matchMeta.number || matchId}`);
        setElementText('mf_round_label', matchMeta.round_title || `Round ${match.round_number || 1}`);
        setElementText('mf_status_label', match.match_status || matchMeta.status || 'Scheduled');
        setElementText(
            'mf_flow_label',
            matchMeta.next_match_number ? `Winner to Match ${matchMeta.next_match_number}` : 'Winner path pending'
        );
        syncModalPlayerCard('mf_player1_link', 'mf_player1_label', matchMeta.player1_label, matchMeta.player1_profile_url);
        syncModalPlayerCard('mf_player2_link', 'mf_player2_label', matchMeta.player2_label, matchMeta.player2_profile_url);
        setElementText('mf_score_label_1', `${matchMeta.player1_label || 'Top slot'} score`);
        setElementText('mf_score_label_2', `${matchMeta.player2_label || 'Bottom slot'} score`);
        syncModalResultState(match);
    } catch (error) {
        closeMatchModal();
        window.alert(error.message);
    }
}

function closeMatchModal() {
    document.getElementById('matchModal').style.display = 'none';
}

async function saveMatchFields() {
    const payload = {
        match_id: parseInt(document.getElementById('mf_match_id').value, 10),
        match_date: document.getElementById('mf_date').value || null,
        match_time: document.getElementById('mf_time').value ? `${document.getElementById('mf_time').value}:00` : null
    };

    try {
        const data = await postJson('../../services/match_update.php', payload);
        if (!data.success) {
            throw new Error(data.message || 'Failed to update match');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

async function saveMatchResult() {
    if (document.getElementById('mf_save_result')?.disabled) {
        window.alert('This match needs both slots seeded before a result can be recorded.');
        return;
    }

    const player1Raw = document.getElementById('mf_p1s').value;
    const player2Raw = document.getElementById('mf_p2s').value;
    if (player1Raw === '' || player2Raw === '') {
        window.alert('Enter both scores before recording the result.');
        return;
    }

    const payload = {
        match_id: parseInt(document.getElementById('mf_match_id').value, 10),
        player1_score: parseInt(player1Raw, 10),
        player2_score: parseInt(player2Raw, 10)
    };

    try {
        const data = await postJson('../../services/match_result.php', payload);
        if (!data.success) {
            throw new Error(data.message || 'Failed to record result');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

async function promoteGroups() {
    if (!window.confirm('Promote the top group-stage players into the knockout bracket?')) {
        return;
    }

    try {
        const data = await postJson('../../services/group_promote.php', { tour_id: TournamentAdminPage.tourId });
        if (!data.success) {
            throw new Error(data.message || 'Failed to promote groups');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

async function saveTeamMatchResult(event, teamMatchId) {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    const team1Score = parseInt(formData.get('team1_score') || '0', 10);
    const team2Score = parseInt(formData.get('team2_score') || '0', 10);

    try {
        const data = await postJson('../../services/team_match_result.php', {
            team_match_id: teamMatchId,
            team1_score: team1Score,
            team2_score: team2Score
        });
        if (!data.success) {
            throw new Error(data.message || 'Failed to record team result');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }

    return false;
}

function openLeagueToolsModal() {
    const modal = document.getElementById('leagueToolsModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeLeagueToolsModal() {
    const modal = document.getElementById('leagueToolsModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

async function submitLeagueTools() {
    const startDate = document.getElementById('lt_startdate').value;
    if (!startDate) {
        window.alert('Please choose a new start date.');
        return;
    }

    try {
        const data = await postJson('../../services/league_tools.php', {
            tour_id: TournamentAdminPage.tourId,
            start_date: startDate
        });
        if (!data.success) {
            throw new Error(data.message || 'Failed to reschedule league');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

function clearBracketDropTargets() {
    document.querySelectorAll('[data-bracket-slot]').forEach((slot) => {
        slot.classList.remove('is-drop-target', 'is-dragging');
    });
}

function handleBracketDragStart(event) {
    const slot = event.currentTarget;
    const playerId = slot.dataset.playerId || '';
    const matchStatus = slot.dataset.matchStatus || '';

    if (!playerId || matchStatus === 'Completed') {
        event.preventDefault();
        return;
    }

    draggedBracketSlot = {
        matchId: parseInt(slot.dataset.matchId || '0', 10),
        slot: slot.dataset.slot || '',
        playerId: parseInt(playerId, 10),
        playerName: slot.dataset.playerName || ''
    };

    slot.classList.add('is-dragging');
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', JSON.stringify(draggedBracketSlot));
}

function handleBracketDragEnd() {
    draggedBracketSlot = null;
    clearBracketDropTargets();
}

function handleBracketDragOver(event) {
    if (!draggedBracketSlot) {
        return;
    }

    event.preventDefault();
    event.currentTarget.classList.add('is-drop-target');
    event.dataTransfer.dropEffect = 'move';
}

function handleBracketDragLeave(event) {
    event.currentTarget.classList.remove('is-drop-target');
}

async function handleBracketDrop(event) {
    event.preventDefault();
    const targetSlot = event.currentTarget;
    targetSlot.classList.remove('is-drop-target');

    let source = draggedBracketSlot;
    if (!source) {
        try {
            source = JSON.parse(event.dataTransfer.getData('text/plain'));
        } catch (error) {
            source = null;
        }
    }

    if (!source) {
        return;
    }

    const target = {
        matchId: parseInt(targetSlot.dataset.matchId || '0', 10),
        slot: targetSlot.dataset.slot || '',
        playerId: targetSlot.dataset.playerId ? parseInt(targetSlot.dataset.playerId, 10) : null,
        matchStatus: targetSlot.dataset.matchStatus || ''
    };

    if (target.matchStatus === 'Completed') {
        window.alert('Completed matches cannot accept drag-and-drop changes.');
        clearBracketDropTargets();
        return;
    }

    if (source.matchId === target.matchId && source.slot === target.slot) {
        clearBracketDropTargets();
        return;
    }

    try {
        const data = await postJson('../../services/match_swap_players.php', {
            source_match_id: source.matchId,
            source_slot: source.slot,
            target_match_id: target.matchId,
            target_slot: target.slot
        });
        if (!data.success) {
            throw new Error(data.message || 'Failed to swap players');
        }
        await refreshMatches();
    } catch (error) {
        window.alert(error.message);
        clearBracketDropTargets();
    }
}

function bindBracketSlots() {
    document.querySelectorAll('[data-bracket-slot]').forEach((slot) => {
        slot.addEventListener('dragstart', handleBracketDragStart);
        slot.addEventListener('dragend', handleBracketDragEnd);
        slot.addEventListener('dragover', handleBracketDragOver);
        slot.addEventListener('dragleave', handleBracketDragLeave);
        slot.addEventListener('drop', handleBracketDrop);
    });
}

function selectAdminBracketMatch(matchId) {
    openMatchModal(matchId);
}

function bindBracketInteraction() {
    const cards = Array.from(document.querySelectorAll('[data-admin-bracket-match]'));
    if (cards.length === 0) {
        return;
    }

    cards.forEach((card) => {
        card.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            openMatchModal(card.dataset.matchId || '0');
        });
    });
}

function syncSelectableRow(row) {
    const checkbox = row.querySelector('input[type="checkbox"]');
    const toggle = row.querySelector('[data-row-toggle]');
    const checked = !!checkbox?.checked;
    row.classList.toggle('is-selected', checked);
    toggle?.classList.toggle('is-selected', checked);
}

function refreshSelectionMeta() {
    const removeCount = document.querySelectorAll('input[name="remove_players[]"]:checked').length;
    const addCount = document.querySelectorAll('input[name="new_players[]"]:checked').length;
    const removeMeta = document.getElementById('removePlayerSelectionCount');
    const addMeta = document.getElementById('newPlayerSelectionCount');

    if (removeMeta) {
        removeMeta.textContent = removeCount > 0
            ? `${removeCount} player${removeCount === 1 ? '' : 's'} marked for removal.`
            : 'No players marked for removal.';
    }

    if (addMeta) {
        addMeta.textContent = addCount > 0
            ? `${addCount} new player${addCount === 1 ? '' : 's'} selected to add.`
            : 'No new players selected yet.';
    }
}

function bindSelectableRows() {
    document.querySelectorAll('[data-selectable-row]').forEach((row) => {
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (!checkbox) {
            return;
        }

        syncSelectableRow(row);

        row.addEventListener('click', (event) => {
            if (event.target.closest('input, button, a, select, textarea, label')) {
                return;
            }

            checkbox.checked = !checkbox.checked;
            syncSelectableRow(row);
            refreshSelectionMeta();
        });

        checkbox.addEventListener('change', () => {
            syncSelectableRow(row);
            refreshSelectionMeta();
        });
    });
}

function bindAvailablePlayerFilter() {
    const filter = document.getElementById('newPlayerFilter');
    const sort = document.getElementById('newPlayerSort');
    const tbody = document.querySelector('[data-player-add-row]')?.closest('tbody');
    if (!filter || !tbody) {
        return;
    }

    const applyControls = () => {
        const query = filter.value.trim().toLowerCase();
        const sortMode = sort?.value || 'name_asc';
        const rows = Array.from(tbody.querySelectorAll('[data-player-add-row]'));

        rows.sort((left, right) => {
            const leftName = left.dataset.playerName || '';
            const rightName = right.dataset.playerName || '';
            if (sortMode === 'name_desc') {
                return rightName.localeCompare(leftName, undefined, { sensitivity: 'base' });
            }
            if (sortMode === 'id_asc') {
                return Number(left.dataset.playerId || 0) - Number(right.dataset.playerId || 0);
            }
            if (sortMode === 'id_desc') {
                return Number(right.dataset.playerId || 0) - Number(left.dataset.playerId || 0);
            }

            return leftName.localeCompare(rightName, undefined, { sensitivity: 'base' });
        });

        rows.forEach((row) => {
            row.style.display = (row.dataset.playerName || '').includes(query) ? '' : 'none';
            tbody.appendChild(row);
        });
    };

    filter.addEventListener('input', applyControls);
    sort?.addEventListener('change', applyControls);
    applyControls();
}

function bindCurrentPlayerControls() {
    const filter = document.getElementById('currentPlayerFilter');
    const sort = document.getElementById('currentPlayerSort');
    const tbody = document.querySelector('[data-current-player-row]')?.closest('tbody');
    if (!filter || !tbody) {
        return;
    }

    const applyControls = () => {
        const query = filter.value.trim().toLowerCase();
        const sortMode = sort?.value || 'name_asc';
        const rows = Array.from(tbody.querySelectorAll('[data-current-player-row]'));

        rows.sort((left, right) => {
            const leftName = left.dataset.playerName || '';
            const rightName = right.dataset.playerName || '';
            if (sortMode === 'name_desc') {
                return rightName.localeCompare(leftName, undefined, { sensitivity: 'base' });
            }
            if (sortMode === 'status') {
                return (left.dataset.playerStatus || '').localeCompare(right.dataset.playerStatus || '', undefined, { sensitivity: 'base' })
                    || leftName.localeCompare(rightName, undefined, { sensitivity: 'base' });
            }
            if (sortMode === 'group') {
                return Number(left.dataset.playerGroup || 9999) - Number(right.dataset.playerGroup || 9999)
                    || leftName.localeCompare(rightName, undefined, { sensitivity: 'base' });
            }

            return leftName.localeCompare(rightName, undefined, { sensitivity: 'base' });
        });

        rows.forEach((row) => {
            const searchable = `${row.dataset.playerName || ''} ${row.dataset.playerStatus || ''} ${row.dataset.playerGroup || ''}`;
            row.style.display = searchable.includes(query) ? '' : 'none';
            tbody.appendChild(row);
        });
    };

    filter.addEventListener('input', applyControls);
    sort?.addEventListener('change', applyControls);
    applyControls();
}

function bindModalDismissals() {
    const matchModal = document.getElementById('matchModal');
    const leagueModal = document.getElementById('leagueToolsModal');

    [matchModal, leagueModal].forEach((modal) => {
        if (!modal) {
            return;
        }

        if (modal.dataset.dismissBound === 'true') {
            return;
        }

        modal.dataset.dismissBound = 'true';
        modal.addEventListener('click', (event) => {
            if (event.target !== modal) {
                return;
            }

            if (modal.id === 'matchModal') {
                closeMatchModal();
            } else {
                closeLeagueToolsModal();
            }
        });
    });

    if (document.body.dataset.tournamentModalEscapeBound === 'true') {
        return;
    }

    document.body.dataset.tournamentModalEscapeBound = 'true';
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const liveMatchModal = document.getElementById('matchModal');
        const liveLeagueModal = document.getElementById('leagueToolsModal');

        if (liveMatchModal?.style.display === 'flex') {
            closeMatchModal();
        }
        if (liveLeagueModal?.style.display === 'flex') {
            closeLeagueToolsModal();
        }
    });
}

function initializeTournamentPage(preferredSection = null) {
    document.querySelectorAll('[data-section-button]').forEach((button) => {
        button.addEventListener('click', () => showSection(button.dataset.sectionButton));
    });

    document.getElementById('generateStructureBtn')?.addEventListener('click', generateStructure);
    document.getElementById('startTournamentBtn')?.addEventListener('click', startTournament);
    document.getElementById('refreshMatchesBtn')?.addEventListener('click', () => {
        refreshMatches().catch((error) => window.alert(error.message));
    });
    document.getElementById('addMatchBtn')?.addEventListener('click', addMatch);
    document.getElementById('promoteGroupsBtn')?.addEventListener('click', promoteGroups);
    document.getElementById('openLeagueToolsBtn')?.addEventListener('click', openLeagueToolsModal);

    bindBracketSlots();
    bindBracketInteraction();
    bindSelectableRows();
    bindCurrentPlayerControls();
    bindAvailablePlayerFilter();
    bindModalDismissals();
    refreshSelectionMeta();
    showSection(preferredSection || readRememberedSection() || getCurrentSectionName() || 'matches');
}

document.addEventListener('DOMContentLoaded', () => {
    hydrateTournamentPageData(document);
    initializeTournamentPage();
});

window.showSection = showSection;
window.generateStructure = generateStructure;
window.startTournament = startTournament;
window.selectAdminBracketMatch = selectAdminBracketMatch;
window.saveQuickMatchResult = saveQuickMatchResult;
window.openMatchModal = openMatchModal;
window.closeMatchModal = closeMatchModal;
window.saveMatchFields = saveMatchFields;
window.saveMatchResult = saveMatchResult;
window.deleteMatch = deleteMatch;
window.openLeagueToolsModal = openLeagueToolsModal;
window.closeLeagueToolsModal = closeLeagueToolsModal;
window.submitLeagueTools = submitLeagueTools;
window.saveTeamMatchResult = saveTeamMatchResult;
