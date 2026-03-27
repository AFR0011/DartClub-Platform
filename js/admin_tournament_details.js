const TournamentAdminPage = window.TOURNAMENT_PAGE || { players: [] };

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
        const response = await fetch('../../services/match_create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tour_id: TournamentAdminPage.tourId,
                match_date: matchDate,
                match_time: matchTime,
                round_number: Number.isFinite(roundNumber) ? roundNumber : 1,
                player1_id: player1Id ? parseInt(player1Id, 10) : null,
                player2_id: player2Id ? parseInt(player2Id, 10) : null,
                group_number: groupNumber ? parseInt(groupNumber, 10) : null,
                bracket: bracket || null
            })
        });
        const data = await response.json();
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
        const response = await fetch('../../services/match_delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ match_id: matchId })
        });
        const data = await response.json();
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
        const response = await fetch('../../services/match_update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
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
        const response = await fetch('../../services/match_result.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();
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
        const response = await fetch('../../services/group_promote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tour_id: TournamentAdminPage.tourId })
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to promote groups');
        }
        refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
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
        const response = await fetch('../../services/league_tools.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tour_id: TournamentAdminPage.tourId,
                start_date: startDate
            })
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to reschedule league');
        }
        refreshMatches();
    } catch (error) {
        window.alert(error.message);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-section-button]').forEach((button) => {
        button.addEventListener('click', () => showSection(button.dataset.sectionButton));
    });

    document.getElementById('refreshMatchesBtn')?.addEventListener('click', refreshMatches);
    document.getElementById('addMatchBtn')?.addEventListener('click', addMatch);
    document.getElementById('promoteGroupsBtn')?.addEventListener('click', promoteGroups);
    document.getElementById('openLeagueToolsBtn')?.addEventListener('click', openLeagueToolsModal);

    populatePlayerSelect('mf_p1');
    populatePlayerSelect('mf_p2');
});

window.showSection = showSection;
window.openMatchModal = openMatchModal;
window.closeMatchModal = closeMatchModal;
window.saveMatchFields = saveMatchFields;
window.saveMatchResult = saveMatchResult;
window.deleteMatch = deleteMatch;
window.openLeagueToolsModal = openLeagueToolsModal;
window.closeLeagueToolsModal = closeLeagueToolsModal;
window.submitLeagueTools = submitLeagueTools;
