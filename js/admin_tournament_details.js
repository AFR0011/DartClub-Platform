const TournamentAdminPage = { players: [], matches: {} };
let draggedBracketSlot = null;
const MatchModalState = {
    initialValues: null,
    dirty: false,
};

const TOURNAMENT_COPY = {
    continuePrompt: { en: "Continue?", tr: "Devam edilsin mi?" },
    tbd: { en: "TBD", tr: "Belirlenecek" },
    modalReadyBadge: { en: "Ready", tr: "Hazır" },
    modalReadyMessage: { en: "No unsaved changes yet.", tr: "Henüz kaydedilmemiş değişiklik yok." },
    modalUnsavedBadge: { en: "Unsaved", tr: "Kaydedilmedi" },
    modalUnsavedMessage: { en: "You have local edits in this modal that have not been saved yet.", tr: "Bu pencerede henüz kaydedilmemiş yerel değişiklikleriniz var." },
    modalWaitingBadge: { en: "Waiting", tr: "Bekliyor" },
    modalWaitingMessage: { en: "This match needs both slots seeded before score entry can be saved.", tr: "Skor girişi kaydedilmeden önce bu maçta iki sıra da doldurulmalıdır." },
    modalWaitingResultMessage: { en: "This match still needs both slots seeded before a result can be recorded.", tr: "Sonuç kaydedilmeden önce bu maçta iki sıra da doldurulmalıdır." },
    refreshFailed: { en: "Failed to refresh tournament page ({status}).", tr: "Turnuva sayfası yenilenemedi ({status})." },
    refreshMarkupIncomplete: { en: "Refreshed tournament markup is incomplete.", tr: "Yenilenen turnuva işaretlemesi eksik." },
    enterBothScoresBeforeSaving: { en: "Enter both scores before saving.", tr: "Kaydetmeden önce iki skoru da girin." },
    failedToRecordResult: { en: "Failed to record result", tr: "Sonuç kaydedilemedi" },
    resultSavedRefreshing: { en: "Result saved. Refreshing the tournament view...", tr: "Sonuç kaydedildi. Turnuva görünümü yenileniyor..." },
    rebuildStructureTitle: { en: "Rebuild tournament structure", tr: "Turnuva yapısını yeniden oluştur" },
    generateStructureTitle: { en: "Generate tournament structure", tr: "Turnuva yapısını oluştur" },
    rebuildStructureMessage: { en: "This will rebuild the tournament structure from the current non-withdrawn entrants. Continue?", tr: "Bu işlem, çekilmeyen mevcut katılımcılardan turnuva yapısını yeniden oluşturur. Devam edilsin mi?" },
    generateStructureMessage: { en: "This will generate the tournament structure from the current non-withdrawn entrants. Continue?", tr: "Bu işlem, çekilmeyen mevcut katılımcılardan turnuva yapısını oluşturur. Devam edilsin mi?" },
    rebuildStructureConfirm: { en: "Rebuild structure", tr: "Yapıyı yeniden oluştur" },
    generateStructureConfirm: { en: "Generate structure", tr: "Yapıyı oluştur" },
    cancel: { en: "Cancel", tr: "İptal" },
    failedToGenerateStructure: { en: "Failed to generate structure", tr: "Yapı oluşturulamadı" },
    tournamentStructureUpdated: { en: "Tournament structure updated. Refreshing the latest bracket and fixture data...", tr: "Turnuva yapısı güncellendi. En son braket ve fikstür verileri yenileniyor..." },
    startTournamentTitle: { en: "Start tournament", tr: "Turnuvayı başlat" },
    startTournamentMessage: { en: "This will close registration immediately and build or rebuild the tournament structure from the current non-withdrawn entrants. Continue?", tr: "Bu işlem kaydı hemen kapatır ve çekilmeyen mevcut katılımcılardan turnuva yapısını oluşturur veya yeniden kurar. Devam edilsin mi?" },
    startTournamentConfirm: { en: "Start tournament", tr: "Turnuvayı başlat" },
    failedToStartTournament: { en: "Failed to start tournament", tr: "Turnuva başlatılamadı" },
    tournamentStarted: { en: "Tournament started. Refreshing the updated structure...", tr: "Turnuva başladı. Güncellenen yapı yenileniyor..." },
    addManualMatchTitle: { en: "Add manual match", tr: "Elle maç ekle" },
    chooseMatchDate: { en: "Choose the match date (YYYY-MM-DD).", tr: "Maç tarihini seçin (YYYY-AA-GG)." },
    chooseMatchTime: { en: "Choose the match time (HH:MM:SS).", tr: "Maç saatini seçin (SS:DD:SS)." },
    datePlaceholder: { en: "YYYY-MM-DD", tr: "YYYY-AA-GG" },
    timePlaceholder: { en: "HH:MM:SS", tr: "SS:DD:SS" },
    enterRoundNumber: { en: "Enter the round number.", tr: "Tur numarasını girin." },
    optionalPlayer1Id: { en: "Optional: enter Player 1 ID.", tr: "İsteğe bağlı: 1. oyuncu ID girin." },
    optionalPlayer2Id: { en: "Optional: enter Player 2 ID.", tr: "İsteğe bağlı: 2. oyuncu ID girin." },
    optionalGroupNumber: { en: "Optional: enter a group number.", tr: "İsteğe bağlı: grup numarası girin." },
    optionalBracketLabel: { en: "Optional: enter a bracket label.", tr: "İsteğe bağlı: braket etiketi girin." },
    player1IdPlaceholder: { en: "Player 1 ID", tr: "1. Oyuncu ID" },
    player2IdPlaceholder: { en: "Player 2 ID", tr: "2. Oyuncu ID" },
    groupNumberPlaceholder: { en: "Group number", tr: "Grup numarası" },
    bracketLabelPlaceholder: { en: "Bracket label", tr: "Braket etiketi" },
    next: { en: "Next", tr: "İleri" },
    createMatch: { en: "Create match", tr: "Maç oluştur" },
    failedToCreateMatch: { en: "Failed to create match", tr: "Maç oluşturulamadı" },
    manualMatchCreated: { en: "Manual match created. Refreshing the tournament view...", tr: "Elle maç oluşturuldu. Turnuva görünümü yenileniyor..." },
    deleteMatchTitle: { en: "Delete match", tr: "Maçı sil" },
    deleteMatchMessage: { en: "Delete this match from the tournament schedule?", tr: "Bu maçı turnuva takviminden silmek istiyor musunuz?" },
    deleteMatchConfirm: { en: "Delete match", tr: "Maçı sil" },
    keepMatch: { en: "Keep match", tr: "Maçı tut" },
    failedToDeleteMatch: { en: "Failed to delete match", tr: "Maç silinemedi" },
    matchDeleted: { en: "Match deleted. Refreshing the current view...", tr: "Maç silindi. Geçerli görünüm yenileniyor..." },
    modalLoadingBadge: { en: "Loading", tr: "Yükleniyor" },
    modalLoadingMessage: { en: "Fetching the latest match data for this modal...", tr: "Bu pencere için en güncel maç verileri alınıyor..." },
    matchNotFound: { en: "Match not found", tr: "Maç bulunamadı" },
    matchLabel: { en: "Match {number}", tr: "Maç {number}" },
    roundLabel: { en: "Round {number}", tr: "Tur {number}" },
    scheduledStatus: { en: "Scheduled", tr: "Planlandı" },
    winnerToMatch: { en: "Winner to Match {number}", tr: "Kazanan Maç {number}'e" },
    winnerPathPending: { en: "Winner path pending", tr: "Kazanan yolu bekleniyor" },
    topSlot: { en: "Top slot", tr: "Üst sıra" },
    bottomSlot: { en: "Bottom slot", tr: "Alt sıra" },
    scoreLabel: { en: "{label} score", tr: "{label} skoru" },
    discardUnsavedModalChangesTitle: { en: "Discard unsaved modal changes", tr: "Kaydedilmemiş pencere değişikliklerini sil" },
    discardUnsavedModalChangesMessage: { en: "You still have local edits in this match modal. Close it anyway?", tr: "Bu maç penceresinde hâlâ yerel değişiklikleriniz var. Yine de kapatılsın mı?" },
    discardChanges: { en: "Discard changes", tr: "Değişiklikleri sil" },
    keepEditing: { en: "Keep editing", tr: "Düzenlemeye devam et" },
    savingBadge: { en: "Saving", tr: "Kaydediliyor" },
    savingButton: { en: "Saving...", tr: "Kaydediliyor..." },
    saveSchedule: { en: "Save Schedule", tr: "Takvimi Kaydet" },
    scheduleSavingMessage: { en: "Updating the match schedule and preparing a soft refresh...", tr: "Maç takvimi güncelleniyor ve yumuşak yenileme hazırlanıyor..." },
    failedToUpdateMatch: { en: "Failed to update match", tr: "Maç güncellenemedi" },
    savedBadge: { en: "Saved", tr: "Kaydedildi" },
    scheduleSavedMessage: { en: "Schedule saved. Refreshing the bracket view now...", tr: "Takvim kaydedildi. Braket görünümü şimdi yenileniyor..." },
    scheduleSavedSuccessfully: { en: "Schedule saved successfully.", tr: "Takvim başarıyla kaydedildi." },
    retryBadge: { en: "Retry", tr: "Tekrar dene" },
    bothSlotsRequiredBeforeResult: { en: "This match needs both slots seeded before a result can be recorded.", tr: "Sonuç kaydedilmeden önce bu maçta iki sıra da doldurulmalıdır." },
    enterBothScoresBeforeRecording: { en: "Enter both scores before recording the result.", tr: "Sonucu kaydetmeden önce iki skoru da girin." },
    recordingButton: { en: "Recording...", tr: "Kaydediliyor..." },
    recordResult: { en: "Record Result", tr: "Sonucu Kaydet" },
    recordResultSavingMessage: { en: "Recording the result and preparing the next bracket state...", tr: "Sonuç kaydediliyor ve sonraki braket durumu hazırlanıyor..." },
    resultRecordedMessage: { en: "Result recorded. Refreshing the tournament flow...", tr: "Sonuç kaydedildi. Turnuva akışı yenileniyor..." },
    resultRecordedSuccessfully: { en: "Result recorded successfully.", tr: "Sonuç başarıyla kaydedildi." },
    promoteGroupsTitle: { en: "Promote group qualifiers", tr: "Grup yükselenlerini ilerlet" },
    promoteGroupsMessage: { en: "Promote the top group-stage players into the knockout bracket?", tr: "Grup aşamasındaki en iyi oyuncular eleme braketine alınsın mı?" },
    promoteQualifiers: { en: "Promote qualifiers", tr: "Yükselenleri ilerlet" },
    failedToPromoteGroups: { en: "Failed to promote groups", tr: "Gruplar ilerletilemedi" },
    groupQualifiersPromoted: { en: "Group qualifiers promoted. Refreshing the tournament view...", tr: "Grup yükselenleri ilerletildi. Turnuva görünümü yenileniyor..." },
    failedToRecordTeamResult: { en: "Failed to record team result", tr: "Takım sonucu kaydedilemedi" },
    teamResultSaved: { en: "Team result saved. Refreshing the latest standings...", tr: "Takım sonucu kaydedildi. Son puan durumu yenileniyor..." },
    chooseNewStartDate: { en: "Please choose a new start date.", tr: "Lütfen yeni bir başlangıç tarihi seçin." },
    failedToRescheduleLeague: { en: "Failed to reschedule league", tr: "Lig takvimi yeniden düzenlenemedi" },
    structureDatesUpdated: { en: "Structure dates updated. Refreshing the latest schedule...", tr: "Yapı tarihleri güncellendi. En son takvim yenileniyor..." },
    completedMatchesLocked: { en: "Completed matches cannot accept drag-and-drop changes.", tr: "Tamamlanan maçlarda sürükle-bırak değişikliği yapılamaz." },
    failedToSwapPlayers: { en: "Failed to swap players", tr: "Oyuncular değiştirilemedi" },
    bracketSlotsUpdated: { en: "Bracket slots updated. Refreshing the connected bracket...", tr: "Braket sıraları güncellendi. Bağlantılı braket yenileniyor..." },
    closeFocusMode: { en: "Close focus mode", tr: "Odak modunu kapat" },
    openFocusMode: { en: "Open focus mode", tr: "Odak modunu aç" },
    focusModeUnavailable: { en: "Focus mode is not available in this browser.", tr: "Odak modu bu tarayıcıda kullanılamıyor." },
    focusModeCouldNotOpen: { en: "Could not open focus mode in this browser.", tr: "Bu tarayıcıda odak modu açılamadı." },
    noPlayersMarkedForRemoval: { en: "No players marked for removal.", tr: "Kaldırmak için işaretlenen oyuncu yok." },
    removePlayersSelected: { en: "{count} player marked for removal.|{count} players marked for removal.", tr: "{count} oyuncu kaldırılmak üzere işaretlendi." },
    noPlayersMarkedForWithdrawal: { en: "No players marked for withdrawal.", tr: "Çekilmek için işaretlenen oyuncu yok." },
    withdrawPlayersSelected: { en: "{count} player marked for withdrawal.|{count} players marked for withdrawal.", tr: "{count} oyuncu çekilmek üzere işaretlendi." },
    noNewPlayersSelected: { en: "No new players selected yet.", tr: "Henüz eklenecek yeni oyuncu seçilmedi." },
    newPlayersSelected: { en: "{count} new player selected to add.|{count} new players selected to add.", tr: "{count} yeni oyuncu eklenmek üzere seçildi." },
    showCompletedRounds: { en: "Show completed rounds", tr: "Tamamlanan turları göster" },
    fadeCompletedRounds: { en: "Fade completed rounds", tr: "Tamamlanan turları soluklaştır" },
};

function t(key, fallback = "") {
    const value = TOURNAMENT_COPY[key];
    if (!value) {
        return fallback || key;
    }

    if (typeof window.adminLocaleText === "function") {
        return window.adminLocaleText(value) || fallback || key;
    }

    const locale = document.documentElement.lang === "tr" ? "tr" : "en";
    const fallbackValue = fallback || key;
    return value[locale] ?? value.en ?? fallbackValue;
}

function formatT(key, replacements = {}, fallback = "") {
    let text = t(key, fallback);
    Object.entries(replacements).forEach(([name, value]) => {
        text = text.split(`{${name}}`).join(String(value));
    });
    return text;
}

function selectionMetaText(kind, count) {
    if (kind === "remove") {
        const usesWithdrawals = !!TournamentAdminPage.rosterUsesWithdrawals;
        if (count <= 0) {
            return usesWithdrawals ? t("noPlayersMarkedForWithdrawal") : t("noPlayersMarkedForRemoval");
        }

        const template = usesWithdrawals ? t("withdrawPlayersSelected") : t("removePlayersSelected");
        const message = template.includes("|")
            ? template.split("|")[count === 1 ? 0 : 1]
            : template;
        return message.split("{count}").join(String(count));
    }

    if (count <= 0) {
        return t("noNewPlayersSelected");
    }

    const template = t("newPlayersSelected");
    const message = template.includes("|")
        ? template.split("|")[count === 1 ? 0 : 1]
        : template;
    return message.split("{count}").join(String(count));
}

function feedbackToast(message, type = "info") {
    if (window.AppUI?.toast) {
        window.AppUI.toast(message, type);
        return;
    }

    if (type === "error" || type === "warning") {
        window.alert(message);
    } else {
        console.info(message);
    }
}

async function confirmAction(config) {
    if (window.AppUI?.confirm) {
        return window.AppUI.confirm(config);
    }

    return window.confirm(config?.message || t("continuePrompt", "Continue?"));
}

async function promptForValue(config) {
    if (window.AppUI?.prompt) {
        return window.AppUI.prompt(config);
    }

    return window.prompt(config?.message || config?.title || "", config?.initialValue || "");
}

function setModalFeedbackState(tone, badgeLabel, message) {
    const badge = document.getElementById("mf_feedback_badge");
    const label = document.getElementById("mf_feedback_text");
    if (badge) {
        badge.dataset.tone = tone || "neutral";
        badge.textContent = badgeLabel || t("modalReadyBadge", "Ready");
    }
    if (label) {
        label.textContent = message || t("modalReadyMessage", "No unsaved changes yet.");
    }
}

function readModalValues() {
    return {
        date: document.getElementById("mf_date")?.value || "",
        time: document.getElementById("mf_time")?.value || "",
        player1Score: document.getElementById("mf_p1s")?.value || "",
        player2Score: document.getElementById("mf_p2s")?.value || "",
    };
}

function captureModalInitialState() {
    MatchModalState.initialValues = readModalValues();
    MatchModalState.dirty = false;
    setModalFeedbackState("neutral", t("modalReadyBadge"), t("modalReadyMessage"));
}

function syncModalDirtyState() {
    if (!MatchModalState.initialValues) {
        return;
    }

    const current = readModalValues();
    const dirty = Object.keys(MatchModalState.initialValues).some((key) => current[key] !== MatchModalState.initialValues[key]);
    MatchModalState.dirty = dirty;

    if (dirty) {
        setModalFeedbackState("dirty", t("modalUnsavedBadge"), t("modalUnsavedMessage"));
    } else {
        setModalFeedbackState("neutral", t("modalReadyBadge"), t("modalReadyMessage"));
    }
}

function bindMatchModalInputs() {
    ["mf_date", "mf_time", "mf_p1s", "mf_p2s"].forEach((id) => {
        const field = document.getElementById(id);
        if (!field || field.dataset.modalBinding === "true") {
            return;
        }

        field.dataset.modalBinding = "true";
        field.addEventListener("input", syncModalDirtyState);
        field.addEventListener("change", syncModalDirtyState);
    });
}

function setButtonBusy(buttonId, busy, busyLabel, idleLabel) {
    const button = document.getElementById(buttonId);
    if (!button) {
        return;
    }

    if (!button.dataset.idleLabel) {
        button.dataset.idleLabel = idleLabel || button.textContent;
    }

    if (busy) {
        button.disabled = true;
        button.textContent = busyLabel;
        return;
    }

    button.disabled = false;
    button.textContent = button.dataset.idleLabel;
}

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

function getBracketViewStorageKey() {
    return `tournament-admin:${TournamentAdminPage.tourId || 'default'}:bracket-view`;
}

function rememberBracketView(viewKey) {
    if (!viewKey) {
        return;
    }

    try {
        window.sessionStorage.setItem(getBracketViewStorageKey(), viewKey);
    } catch (error) {
        console.warn('Failed to persist bracket view state:', error);
    }
}

function readRememberedBracketView() {
    try {
        return window.sessionStorage.getItem(getBracketViewStorageKey());
    } catch (error) {
        return null;
    }
}

function getPlayersPanelStorageKey() {
    return `tournament-admin:${TournamentAdminPage.tourId || 'default'}:players-panel`;
}

function getCompletedFadeStorageKey() {
    return `tournament-admin:${TournamentAdminPage.tourId || 'default'}:completed-fade`;
}

function rememberPlayersPanel(panelName) {
    if (!panelName) {
        return;
    }

    try {
        window.sessionStorage.setItem(getPlayersPanelStorageKey(), panelName);
    } catch (error) {
        console.warn('Failed to persist players panel state:', error);
    }
}

function readRememberedPlayersPanel() {
    try {
        return window.sessionStorage.getItem(getPlayersPanelStorageKey());
    } catch (error) {
        return null;
    }
}

function readRememberedCompletedFade() {
    try {
        const saved = window.sessionStorage.getItem(getCompletedFadeStorageKey());
        return saved === null ? true : saved === 'true';
    } catch (error) {
        return true;
    }
}

function rememberCompletedFade(enabled) {
    try {
        window.sessionStorage.setItem(getCompletedFadeStorageKey(), enabled ? 'true' : 'false');
    } catch (error) {
        console.warn('Failed to persist completed-round fade state:', error);
    }
}

function syncCompletedMatchFadeState(enabled = readRememberedCompletedFade()) {
    document.body.classList.toggle('completed-round-fade-enabled', enabled);
    document.querySelectorAll('[data-completed-fade-toggle]').forEach((button) => {
        button.textContent = enabled ? t('showCompletedRounds') : t('fadeCompletedRounds');
        button.classList.toggle('active', !enabled);
    });
}

function toggleCompletedMatchFade() {
    const nextEnabled = !document.body.classList.contains('completed-round-fade-enabled');
    rememberCompletedFade(nextEnabled);
    syncCompletedMatchFadeState(nextEnabled);
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

    const safeLabel = playerLabel || t("tbd", "TBD");
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

    if (!hasBothPlayers) {
        setModalFeedbackState("warning", t("modalWaitingBadge"), t("modalWaitingMessage"));
    }
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

function showPlayersPanel(panelName) {
    const panels = Array.from(document.querySelectorAll('[data-players-panel]'));
    if (panels.length === 0) {
        return;
    }

    const panelExists = panels.some((panel) => panel.dataset.playersPanel === panelName);
    const nextPanel = panelExists ? panelName : (panels[0]?.dataset.playersPanel || null);

    panels.forEach((panel) => {
        panel.classList.toggle('is-hidden', panel.dataset.playersPanel !== nextPanel);
    });

    document.querySelectorAll('[data-players-panel-button]').forEach((button) => {
        button.classList.toggle('active', button.dataset.playersPanelButton === nextPanel);
    });

    rememberPlayersPanel(nextPanel);
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
        throw new Error(formatT("refreshFailed", { status: response.status }, `Failed to refresh tournament page (${response.status}).`));
    }

    const html = await response.text();
    const parser = new DOMParser();
    const nextDocument = parser.parseFromString(html, 'text/html');
    const nextContainer = nextDocument.querySelector('.container');
    const nextMatchModal = nextDocument.getElementById('matchModal');
    const nextLeagueModal = nextDocument.getElementById('leagueToolsModal');
    const nextDataNode = nextDocument.getElementById('tournament-page-data');

    if (!nextContainer || !nextMatchModal || !nextLeagueModal || !nextDataNode) {
        throw new Error(t("refreshMarkupIncomplete", "Refreshed tournament markup is incomplete."));
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
        feedbackToast(t("enterBothScoresBeforeSaving"), 'warning');
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
                throw new Error(data.message || t("failedToRecordResult"));
            }
            feedbackToast(t("resultSavedRefreshing"), 'success');
            await refreshMatches();
        } catch (error) {
            feedbackToast(error.message, 'error');
        }
    })();

    return false;
}

async function generateStructure() {
    const confirmed = await confirmAction({
        title: TournamentAdminPage.structureGenerated ? t("rebuildStructureTitle") : t("generateStructureTitle"),
        message: TournamentAdminPage.structureGenerated ? t("rebuildStructureMessage") : t("generateStructureMessage"),
        confirmLabel: TournamentAdminPage.structureGenerated ? t("rebuildStructureConfirm") : t("generateStructureConfirm"),
        cancelLabel: t("cancel")
    });
    if (!confirmed) {
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
            throw new Error(data.message || t("failedToGenerateStructure"));
        }
        feedbackToast(t("tournamentStructureUpdated"), 'success');
        await refreshMatches();
    } catch (error) {
        feedbackToast(error.message, 'error');
    }
}

async function startTournament() {
    const confirmed = await confirmAction({
        title: t("startTournamentTitle"),
        message: t("startTournamentMessage"),
        confirmLabel: t("startTournamentConfirm"),
        cancelLabel: t("cancel")
    });
    if (!confirmed) {
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
            throw new Error(data.message || t("failedToStartTournament"));
        }
        feedbackToast(t("tournamentStarted"), 'success');
        await refreshMatches();
    } catch (error) {
        feedbackToast(error.message, 'error');
    }
}

async function addMatch() {
    const matchDate = await promptForValue({
        title: t("addManualMatchTitle"),
        message: t("chooseMatchDate"),
        initialValue: new Date().toISOString().slice(0, 10),
        placeholder: t("datePlaceholder"),
        confirmLabel: t("next"),
        cancelLabel: t("cancel")
    });
    if (matchDate === null || !String(matchDate).trim()) {
        return;
    }

    const matchTime = await promptForValue({
        title: t("addManualMatchTitle"),
        message: t("chooseMatchTime"),
        initialValue: '18:00:00',
        placeholder: t("timePlaceholder"),
        confirmLabel: t("next"),
        cancelLabel: t("cancel")
    });
    if (matchTime === null || !String(matchTime).trim()) {
        return;
    }

    const roundNumberRaw = await promptForValue({
        title: t("addManualMatchTitle"),
        message: t("enterRoundNumber"),
        initialValue: '1',
        placeholder: '1',
        confirmLabel: t("next"),
        cancelLabel: t("cancel")
    });
    if (roundNumberRaw === null) {
        return;
    }

    const player1Id = await promptForValue({
        title: t("addManualMatchTitle"),
        message: t("optionalPlayer1Id"),
        initialValue: '',
        placeholder: t("player1IdPlaceholder"),
        confirmLabel: t("next"),
        cancelLabel: t("cancel")
    });
    if (player1Id === null) {
        return;
    }

    const player2Id = await promptForValue({
        title: t("addManualMatchTitle"),
        message: t("optionalPlayer2Id"),
        initialValue: '',
        placeholder: t("player2IdPlaceholder"),
        confirmLabel: t("next"),
        cancelLabel: t("cancel")
    });
    if (player2Id === null) {
        return;
    }

    const groupNumber = await promptForValue({
        title: t("addManualMatchTitle"),
        message: t("optionalGroupNumber"),
        initialValue: '',
        placeholder: t("groupNumberPlaceholder"),
        confirmLabel: t("next"),
        cancelLabel: t("cancel")
    });
    if (groupNumber === null) {
        return;
    }

    const bracket = await promptForValue({
        title: t("addManualMatchTitle"),
        message: t("optionalBracketLabel"),
        initialValue: '',
        placeholder: t("bracketLabelPlaceholder"),
        confirmLabel: t("createMatch"),
        cancelLabel: t("cancel")
    });
    if (bracket === null) {
        return;
    }

    const roundNumber = parseInt(String(roundNumberRaw || '1'), 10);

    try {
        const data = await postJson('../../services/match_create.php', {
            tour_id: TournamentAdminPage.tourId,
            match_date: String(matchDate).trim(),
            match_time: String(matchTime).trim(),
            round_number: Number.isFinite(roundNumber) ? roundNumber : 1,
            player1_id: player1Id ? parseInt(String(player1Id), 10) : null,
            player2_id: player2Id ? parseInt(String(player2Id), 10) : null,
            group_number: groupNumber ? parseInt(String(groupNumber), 10) : null,
            bracket: String(bracket || '').trim() || null
        });
        if (!data.success) {
            throw new Error(data.message || t("failedToCreateMatch"));
        }
        feedbackToast(t("manualMatchCreated"), 'success');
        await refreshMatches();
    } catch (error) {
        feedbackToast(error.message, 'error');
    }
}

async function deleteMatch(matchId) {
    const confirmed = await confirmAction({
        title: t("deleteMatchTitle"),
        message: t("deleteMatchMessage"),
        confirmLabel: t("deleteMatchConfirm"),
        cancelLabel: t("keepMatch"),
        tone: 'danger'
    });
    if (!confirmed) {
        return;
    }

    try {
        const data = await postJson('../../services/match_delete.php', { match_id: matchId });
        if (!data.success) {
            throw new Error(data.message || t("failedToDeleteMatch"));
        }
        feedbackToast(t("matchDeleted"), 'success');
        await refreshMatches();
    } catch (error) {
        feedbackToast(error.message, 'error');
    }
}

async function openMatchModal(matchId) {
    document.getElementById('mf_match_id').value = matchId;
    document.getElementById('matchModal').style.display = 'flex';
    setModalFeedbackState('saving', t("modalLoadingBadge"), t("modalLoadingMessage"));

    try {
        const response = await fetch(`../../services/match_get.php?id=${matchId}`);
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || t("matchNotFound"));
        }

        const match = data.match;
        const matchMeta = getMatchMeta(matchId) || {};
        document.getElementById('mf_date').value = match.match_date || '';
        document.getElementById('mf_time').value = (match.match_time || '').slice(0, 5);
        document.getElementById('mf_p1s').value = match.player1_score ?? '';
        document.getElementById('mf_p2s').value = match.player2_score ?? '';

        setElementText('mf_match_number', formatT('matchLabel', { number: matchMeta.number || matchId }));
        setElementText('mf_round_label', matchMeta.round_title || formatT('roundLabel', { number: match.round_number || 1 }));
        setElementText('mf_status_label', matchMeta.status || match.match_status || t('scheduledStatus'));
        setElementText(
            'mf_flow_label',
            matchMeta.flow_label || (matchMeta.next_match_number ? formatT('winnerToMatch', { number: matchMeta.next_match_number }) : t('winnerPathPending'))
        );
        syncModalPlayerCard('mf_player1_link', 'mf_player1_label', matchMeta.player1_label, matchMeta.player1_profile_url);
        syncModalPlayerCard('mf_player2_link', 'mf_player2_label', matchMeta.player2_label, matchMeta.player2_profile_url);
        setElementText('mf_score_label_1', formatT('scoreLabel', { label: matchMeta.player1_label || t('topSlot') }));
        setElementText('mf_score_label_2', formatT('scoreLabel', { label: matchMeta.player2_label || t('bottomSlot') }));
        syncModalResultState(match);
        bindMatchModalInputs();
        captureModalInitialState();

        if (!match?.player1_id || !match?.player2_id) {
            setModalFeedbackState('warning', t('modalWaitingBadge'), t('modalWaitingResultMessage'));
        }
    } catch (error) {
        closeMatchModal(true);
        feedbackToast(error.message, 'error');
    }
}

async function closeMatchModal(force = false) {
    if (!force && MatchModalState.dirty) {
        const discard = await confirmAction({
            title: t('discardUnsavedModalChangesTitle'),
            message: t('discardUnsavedModalChangesMessage'),
            confirmLabel: t('discardChanges'),
            cancelLabel: t('keepEditing'),
            tone: 'danger'
        });
        if (!discard) {
            return;
        }
    }

    MatchModalState.initialValues = null;
    MatchModalState.dirty = false;
    document.getElementById('matchModal').style.display = 'none';
}

async function saveMatchFields() {
    const payload = {
        match_id: parseInt(document.getElementById('mf_match_id').value, 10),
        match_date: document.getElementById('mf_date').value || null,
        match_time: document.getElementById('mf_time').value ? `${document.getElementById('mf_time').value}:00` : null
    };

    try {
        setButtonBusy('mf_save_schedule', true, t('savingButton'), t('saveSchedule'));
        setModalFeedbackState('saving', t('savingBadge'), t('scheduleSavingMessage'));
        const data = await postJson('../../services/match_update.php', payload);
        if (!data.success) {
            throw new Error(data.message || t('failedToUpdateMatch'));
        }
        setModalFeedbackState('saved', t('savedBadge'), t('scheduleSavedMessage'));
        feedbackToast(t('scheduleSavedSuccessfully'), 'success');
        await closeMatchModal(true);
        await refreshMatches();
    } catch (error) {
        setModalFeedbackState('warning', t('retryBadge'), error.message);
        feedbackToast(error.message, 'error');
    } finally {
        setButtonBusy('mf_save_schedule', false, t('savingButton'), t('saveSchedule'));
    }
}

async function saveMatchResult() {
    if (document.getElementById('mf_save_result')?.disabled) {
        feedbackToast(t('bothSlotsRequiredBeforeResult'), 'warning');
        return;
    }

    const player1Raw = document.getElementById('mf_p1s').value;
    const player2Raw = document.getElementById('mf_p2s').value;
    if (player1Raw === '' || player2Raw === '') {
        feedbackToast(t('enterBothScoresBeforeRecording'), 'warning');
        return;
    }

    const payload = {
        match_id: parseInt(document.getElementById('mf_match_id').value, 10),
        player1_score: parseInt(player1Raw, 10),
        player2_score: parseInt(player2Raw, 10)
    };

    try {
        setButtonBusy('mf_save_result', true, t('recordingButton'), t('recordResult'));
        setModalFeedbackState('saving', t('savingBadge'), t('recordResultSavingMessage'));
        const data = await postJson('../../services/match_result.php', payload);
        if (!data.success) {
            throw new Error(data.message || t('failedToRecordResult'));
        }
        setModalFeedbackState('saved', t('savedBadge'), t('resultRecordedMessage'));
        feedbackToast(t('resultRecordedSuccessfully'), 'success');
        await closeMatchModal(true);
        await refreshMatches();
    } catch (error) {
        setModalFeedbackState('warning', t('retryBadge'), error.message);
        feedbackToast(error.message, 'error');
    } finally {
        setButtonBusy('mf_save_result', false, t('recordingButton'), t('recordResult'));
    }
}

async function promoteGroups() {
    const confirmed = await confirmAction({
        title: t('promoteGroupsTitle'),
        message: t('promoteGroupsMessage'),
        confirmLabel: t('promoteQualifiers'),
        cancelLabel: t('cancel')
    });
    if (!confirmed) {
        return;
    }

    try {
        const data = await postJson('../../services/group_promote.php', { tour_id: TournamentAdminPage.tourId });
        if (!data.success) {
            throw new Error(data.message || t('failedToPromoteGroups'));
        }
        feedbackToast(t('groupQualifiersPromoted'), 'success');
        await refreshMatches();
    } catch (error) {
        feedbackToast(error.message, 'error');
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
            throw new Error(data.message || t('failedToRecordTeamResult'));
        }
        feedbackToast(t('teamResultSaved'), 'success');
        await refreshMatches();
    } catch (error) {
        feedbackToast(error.message, 'error');
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
        feedbackToast(t('chooseNewStartDate'), 'warning');
        return;
    }

    try {
        const data = await postJson('../../services/league_tools.php', {
            tour_id: TournamentAdminPage.tourId,
            start_date: startDate
        });
        if (!data.success) {
            throw new Error(data.message || t('failedToRescheduleLeague'));
        }
        feedbackToast(t('structureDatesUpdated'), 'success');
        await refreshMatches();
    } catch (error) {
        feedbackToast(error.message, 'error');
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
        feedbackToast(t('completedMatchesLocked'), 'warning');
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
            throw new Error(data.message || t('failedToSwapPlayers'));
        }
        feedbackToast(t('bracketSlotsUpdated'), 'success');
        await refreshMatches();
    } catch (error) {
        feedbackToast(error.message, 'error');
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

function syncBracketFocusButton() {
    const button = document.getElementById('adminBracketFocusButton');
    const section = document.getElementById('adminBracketSection');
    if (!button || !section) {
        return;
    }

    const isFocused = document.fullscreenElement === section;
    button.textContent = isFocused ? t('closeFocusMode') : t('openFocusMode');
}

async function toggleBracketSectionFocus() {
    const section = document.getElementById('adminBracketSection');
    if (!section) {
        return;
    }

    try {
        if (document.fullscreenElement === section) {
            await document.exitFullscreen();
            syncBracketFocusButton();
            return;
        }

        if (section.requestFullscreen) {
            await section.requestFullscreen();
            syncBracketFocusButton();
            return;
        }

        feedbackToast(t('focusModeUnavailable'), 'warning');
    } catch (error) {
        feedbackToast(t('focusModeCouldNotOpen'), 'warning');
    }
}

function scrollToBracketGroup(groupId) {
    const target = document.getElementById(groupId);
    if (!target) {
        return;
    }

    target.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
}

function syncMirroredBracketShells(activeView) {
    document.querySelectorAll('[data-mirrored-bracket-shell]').forEach((shell) => {
        const targetLeft = activeView === 'merged' || activeView === 'Losers Bracket'
            ? Math.max(0, shell.scrollWidth - shell.clientWidth)
            : 0;

        shell.scrollLeft = targetLeft;
        shell.scrollTo({ left: targetLeft, behavior: 'auto' });
        window.requestAnimationFrame(() => {
            shell.scrollLeft = targetLeft;
        });
        window.setTimeout(() => {
            shell.scrollLeft = targetLeft;
        }, 48);
    });
}

function showBracketView(viewKey) {
    const groups = Array.from(document.querySelectorAll('[data-bracket-group]'));
    if (groups.length === 0) {
        return;
    }

    const isDoubleElimination = TournamentAdminPage.type === 'Double Elimination'
        || groups.some((group) => (group.dataset.bracketGroup || '') === 'Opening Round');
    const rememberedView = readRememberedBracketView();
    let activeView = viewKey || rememberedView || (isDoubleElimination ? 'merged' : 'all');

    if (isDoubleElimination && !['merged', 'Winners Bracket', 'Losers Bracket', 'Grand Final'].includes(activeView)) {
        activeView = 'merged';
    }

    if (!isDoubleElimination) {
        const availableGroups = groups.map((group) => group.dataset.bracketGroup || '').filter(Boolean);
        activeView = activeView === 'all' || availableGroups.includes(activeView) ? activeView : 'all';

        groups.forEach((group) => {
            group.classList.toggle('is-hidden', activeView !== 'all' && (group.dataset.bracketGroup || '') !== activeView);
        });

        groups.forEach((group) => {
            if (!group.classList.contains('is-hidden')) {
                group.hidden = false;
            }
        });

        document.querySelectorAll('[data-bracket-view-button]').forEach((button) => {
            button.classList.toggle('active', button.dataset.bracketViewButton === activeView);
        });

        document.querySelectorAll('[data-bracket-groups-container]').forEach((container) => {
            container.dataset.activeView = activeView;
            const visibleGroups = Array.from(container.querySelectorAll('[data-bracket-group]'))
                .filter((group) => !group.classList.contains('is-hidden'));
            container.classList.toggle('is-single-view', activeView !== 'all' || visibleGroups.length <= 1);
        });

        window.requestAnimationFrame(() => syncMirroredBracketShells(activeView));
        rememberBracketView(activeView);
        return;
    }

    const allowedGroupsByView = {
        merged: ['Losers Bracket', 'Opening Round', 'Winners Bracket'],
        'Winners Bracket': ['Opening Round', 'Winners Bracket'],
        'Losers Bracket': ['Opening Round', 'Losers Bracket'],
        'Grand Final': ['Grand Final', 'Third Place Playoff']
    };

    document.querySelectorAll('[data-bracket-view-button]').forEach((button) => {
        button.classList.toggle('active', button.dataset.bracketViewButton === activeView);
    });

    document.querySelectorAll('[data-bracket-groups-container]').forEach((container) => {
        container.dataset.activeView = activeView;
        const visibleGroups = Array.from(container.querySelectorAll('[data-bracket-group]'))
            .filter((group) => (allowedGroupsByView[activeView] || []).includes(group.dataset.bracketGroup || ''));
        container.classList.toggle('is-single-view', activeView === 'Grand Final' || visibleGroups.length <= 1);
    });

    window.requestAnimationFrame(() => syncMirroredBracketShells(activeView));

    rememberBracketView(activeView);
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
        removeMeta.textContent = selectionMetaText('remove', removeCount);
    }

    if (addMeta) {
        addMeta.textContent = selectionMetaText('add', addCount);
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

    document.querySelectorAll('[data-players-panel-button]').forEach((button) => {
        button.addEventListener('click', () => showPlayersPanel(button.dataset.playersPanelButton));
    });

    document.getElementById('generateStructureBtn')?.addEventListener('click', generateStructure);
    document.getElementById('startTournamentBtn')?.addEventListener('click', startTournament);
    document.getElementById('refreshMatchesBtn')?.addEventListener('click', () => {
        refreshMatches().catch((error) => feedbackToast(error.message, 'error'));
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
    bindMatchModalInputs();
    refreshSelectionMeta();
    showSection(preferredSection || readRememberedSection() || getCurrentSectionName() || 'details');
    showPlayersPanel(readRememberedPlayersPanel() || 'roster');
    showBracketView(readRememberedBracketView() || 'merged');
    syncCompletedMatchFadeState();
    syncBracketFocusButton();
}

document.addEventListener('DOMContentLoaded', () => {
    hydrateTournamentPageData(document);
    initializeTournamentPage();
});
document.addEventListener('fullscreenchange', syncBracketFocusButton);
window.addEventListener('resize', () => {
    const activeView = document.querySelector('[data-bracket-groups-container]')?.dataset.activeView || 'all';
    syncMirroredBracketShells(activeView);
});

window.showSection = showSection;
window.generateStructure = generateStructure;
window.startTournament = startTournament;
window.toggleBracketSectionFocus = toggleBracketSectionFocus;
window.scrollToBracketGroup = scrollToBracketGroup;
window.showBracketView = showBracketView;
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
window.toggleCompletedMatchFade = toggleCompletedMatchFade;
