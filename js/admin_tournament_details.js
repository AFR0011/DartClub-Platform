const TournamentAdminPage = window.TOURNAMENT_PAGE || { players: [] };
let draggedBracketSlot = null;

async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    return response.json();
}

function populatePlayerSelect(selectId) {
    const select = document.getElementById(selectId);
    if (!select) {
        return;
    }

    select.innerHTML = '<option value="">-</option>';
    TournamentAdminPage.players.forEach((player) => {
        const option = document.createElement('option');
        option.value = player.id;
        option.textContent = player.name;
        select.appendChild(option);
    });
}

function showSection(sectionName) {
    document.querySelectorAll('[data-section]').forEach((section) => {
        section.classList.toggle('active', section.dataset.section === sectionName);
    });

    document.querySelectorAll('[data-section-button]').forEach((button) => {
        button.classList.toggle('active', button.dataset.sectionButton === sectionName);
    });
}

function refreshMatches() {
    window.location.reload();
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
            refreshMatches();
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
        refreshMatches();
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
        refreshMatches();
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
        refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

async function openMatchModal(matchId) {
    populatePlayerSelect('mf_p1');
    populatePlayerSelect('mf_p2');

    document.getElementById('mf_match_id').value = matchId;
    document.getElementById('matchModal').style.display = 'flex';

    try {
        const response = await fetch(`../../services/match_get.php?id=${matchId}`);
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Match not found');
        }

        const match = data.match;
        document.getElementById('mf_date').value = match.match_date || '';
        document.getElementById('mf_time').value = (match.match_time || '').slice(0, 5);
        document.getElementById('mf_round').value = match.round_number || '';
        document.getElementById('mf_bracket').value = match.bracket || '';
        document.getElementById('mf_group').value = match.group_number || '';
        document.getElementById('mf_next').value = match.next_match_id || '';
        document.getElementById('mf_pos').value = match.position_in_next || '';
        document.getElementById('mf_loser_next').value = match.loser_next_match_id || '';
        document.getElementById('mf_loser_pos').value = match.loser_position_in_next || '';
        document.getElementById('mf_p1').value = match.player1_id || '';
        document.getElementById('mf_p2').value = match.player2_id || '';
        document.getElementById('mf_p1s').value = match.player1_score || '';
        document.getElementById('mf_p2s').value = match.player2_score || '';
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
        match_time: document.getElementById('mf_time').value ? `${document.getElementById('mf_time').value}:00` : null,
        round_number: document.getElementById('mf_round').value ? parseInt(document.getElementById('mf_round').value, 10) : null,
        bracket: document.getElementById('mf_bracket').value || null,
        group_number: document.getElementById('mf_group').value ? parseInt(document.getElementById('mf_group').value, 10) : null,
        next_match_id: document.getElementById('mf_next').value ? parseInt(document.getElementById('mf_next').value, 10) : null,
        position_in_next: document.getElementById('mf_pos').value ? parseInt(document.getElementById('mf_pos').value, 10) : null,
        loser_next_match_id: document.getElementById('mf_loser_next').value ? parseInt(document.getElementById('mf_loser_next').value, 10) : null,
        loser_position_in_next: document.getElementById('mf_loser_pos').value ? parseInt(document.getElementById('mf_loser_pos').value, 10) : null,
        player1_id: document.getElementById('mf_p1').value ? parseInt(document.getElementById('mf_p1').value, 10) : null,
        player2_id: document.getElementById('mf_p2').value ? parseInt(document.getElementById('mf_p2').value, 10) : null
    };

    try {
        const data = await postJson('../../services/match_update.php', payload);
        if (!data.success) {
            throw new Error(data.message || 'Failed to update match');
        }
        refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

async function saveMatchResult() {
    const payload = {
        match_id: parseInt(document.getElementById('mf_match_id').value, 10),
        player1_score: parseInt(document.getElementById('mf_p1s').value || '0', 10),
        player2_score: parseInt(document.getElementById('mf_p2s').value || '0', 10)
    };

    try {
        const data = await postJson('../../services/match_result.php', payload);
        if (!data.success) {
            throw new Error(data.message || 'Failed to record result');
        }
        refreshMatches();
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
        refreshMatches();
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
        refreshMatches();
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
        refreshMatches();
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
        refreshMatches();
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
    if (!filter) {
        return;
    }

    filter.addEventListener('input', () => {
        const query = filter.value.trim().toLowerCase();
        document.querySelectorAll('[data-player-add-row]').forEach((row) => {
            const playerName = row.dataset.playerName || '';
            row.style.display = playerName.includes(query) ? '' : 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-section-button]').forEach((button) => {
        button.addEventListener('click', () => showSection(button.dataset.sectionButton));
    });

    document.getElementById('generateStructureBtn')?.addEventListener('click', generateStructure);
    document.getElementById('refreshMatchesBtn')?.addEventListener('click', refreshMatches);
    document.getElementById('addMatchBtn')?.addEventListener('click', addMatch);
    document.getElementById('promoteGroupsBtn')?.addEventListener('click', promoteGroups);
    document.getElementById('openLeagueToolsBtn')?.addEventListener('click', openLeagueToolsModal);

    populatePlayerSelect('mf_p1');
    populatePlayerSelect('mf_p2');
    bindBracketSlots();
    bindSelectableRows();
    bindAvailablePlayerFilter();
    refreshSelectionMeta();
});

window.showSection = showSection;
window.generateStructure = generateStructure;
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
