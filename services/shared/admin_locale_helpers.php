<?php

function admin_supported_locales(): array
{
    return ['en', 'tr'];
}

function admin_normalize_locale(?string $locale): string
{
    $normalized = strtolower(trim((string) $locale));
    return in_array($normalized, admin_supported_locales(), true) ? $normalized : 'en';
}

function admin_current_locale(): string
{
    return admin_normalize_locale($_COOKIE['dartClubLocale'] ?? 'en');
}

function admin_is_turkish(): bool
{
    return admin_current_locale() === 'tr';
}

function admin_text(string $english, string $turkish): string
{
    return admin_is_turkish() ? $turkish : $english;
}

function admin_html_lang(): string
{
    return admin_is_turkish() ? 'tr' : 'en';
}

function admin_translate_value(string $value): string
{
    if (!admin_is_turkish()) {
        return $value;
    }

    $trimmed = trim($value);
    if ($trimmed === '') {
        return $value;
    }

    $map = [
        'Round Robin' => 'Round Robin',
        'League' => 'Lig',
        'Group' => 'Grup',
        'Elimination' => 'Eliminasyon',
        'Knockout' => 'Eleme',
        'Double Elimination' => 'Çift Eliminasyon',
        'Knockout Bracket' => 'Eleme Braketi',
        'Tournament Bracket' => 'Turnuva Braketi',
        'Merged Bracket' => 'Birleşik Braket',
        'Opening Round' => 'Açılış Turu',
        'Winners Bracket' => 'Kazananlar Braketi',
        'Losers Bracket' => 'Kaybedenler Braketi',
        'Grand Final' => 'Büyük Final',
        'Third Place Playoff' => 'Üçüncülük Maçı',
        'Registration Open' => 'Kayıt Açık',
        'Registration Closed' => 'Kayıt Kapandı',
        'Waiting To Start' => 'Başlamayı Bekliyor',
        'In Progress' => 'Devam Ediyor',
        'Completed' => 'Tamamlandı',
        'Archived' => 'Arşivlendi',
        'Draft' => 'Taslak',
        'Scheduled' => 'Planlandı',
        'Ready' => 'Hazır',
        'Live' => 'Canlı',
        'Waiting' => 'Beklemede',
        'Active' => 'Aktif',
        'Registered' => 'Kayıtlı',
        'Withdrawn' => 'Çekildi',
        'Pending' => 'Beklemede',
        'Approved' => 'Onaylandı',
        'Rejected' => 'Reddedildi',
        'Public' => 'Herkese Açık',
        'Private' => 'Özel',
        'Generated' => 'Oluşturuldu',
        'Not generated' => 'Henüz oluşturulmadı',
        'Double elimination' => 'Çift eliminasyon',
        'League knockout' => 'Lig eleme aşaması',
        'Top' => 'Üst',
        'Bottom' => 'Alt',
        'Top Slot' => 'Üst sıra',
        'Bottom Slot' => 'Alt sıra',
        'Seeded' => 'Yerleştirildi',
        'Bye Slot' => 'Bay geçişi',
        'Bye / no fixture' => 'Bay / maç yok',
        'Auto-advance' => 'Otomatik ilerleme',
        'Auto-advanced slot' => 'Otomatik yükselen sıra',
        'Bracket spacer' => 'Braket boşluğu',
        'Drag' => 'Sürükle',
        'Drop' => 'Bırak',
        'Final' => 'Final',
        'Semifinal' => 'Yarı Final',
        'Quarterfinal' => 'Çeyrek Final',
        'Draw' => 'Berabere',
        'TBD' => 'Belirlenecek',
        'Winner TBD' => 'Kazanan belirlenecek',
        'Loser TBD' => 'Kaybeden belirlenecek',
        'Champion decided here' => 'Şampiyon burada belirlenir',
        '3rd place decided here' => 'Üçüncülük burada belirlenir',
        'Winner path pending' => 'Kazanan yolu bekleniyor',
        'Waiting for result' => 'Sonuç bekleniyor',
        'Player' => 'Oyuncu',
        'Manager' => 'Yönetici',
        'Admin' => 'Admin',
    ];

    if (isset($map[$trimmed])) {
        return $map[$trimmed];
    }

    if (str_contains($trimmed, ' | ')) {
        return implode(' | ', array_map('admin_translate_value', explode(' | ', $trimmed)));
    }

    if (preg_match('/^Match (\d+)$/', $trimmed, $matches) === 1) {
        return 'Maç ' . $matches[1];
    }

    if (preg_match('/^Winner of Match (\d+)$/', $trimmed, $matches) === 1) {
        return 'Maç ' . $matches[1] . ' galibi';
    }

    if (preg_match('/^Loser of Match (\d+)$/', $trimmed, $matches) === 1) {
        return 'Maç ' . $matches[1] . ' mağlubu';
    }

    if (preg_match('/^Winner to Match (\d+)$/', $trimmed, $matches) === 1) {
        return 'Kazanan Maç ' . $matches[1] . '\'e';
    }

    if (preg_match('/^Loser to Match (\d+)$/', $trimmed, $matches) === 1) {
        return 'Kaybeden Maç ' . $matches[1] . '\'e';
    }

    if (preg_match('/^Winners (.+)$/', $trimmed, $matches) === 1) {
        return 'Kazananlar ' . admin_translate_value($matches[1]);
    }

    if (preg_match('/^Losers (.+)$/', $trimmed, $matches) === 1) {
        return 'Kaybedenler ' . admin_translate_value($matches[1]);
    }

    if (preg_match('/^Round (\d+)$/', $trimmed, $matches) === 1) {
        return 'Tur ' . $matches[1];
    }

    if (preg_match('/^Group (\d+)$/', $trimmed, $matches) === 1) {
        return 'Grup ' . $matches[1];
    }

    if (preg_match('/^Round of (\d+)$/', $trimmed, $matches) === 1) {
        return 'Son ' . $matches[1];
    }

    if (preg_match('/^Team (\d+)$/', $trimmed, $matches) === 1) {
        return 'Takım ' . $matches[1];
    }

    return $value;
}

function admin_tournament_type_label(string $type): string
{
    return admin_translate_value($type);
}

function admin_tournament_status_label(string $status): string
{
    $normalized = strtolower(trim($status));
    $map = [
        'draft' => 'Draft',
        'registration_open' => 'Registration Open',
        'registration_closed' => 'Registration Closed',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ];

    return admin_translate_value($map[$normalized] ?? $status);
}

function admin_match_status_label(string $status): string
{
    $normalized = strtolower(trim($status));
    $map = [
        'scheduled' => 'Scheduled',
        'ready' => 'Ready',
        'completed' => 'Completed',
        'in progress' => 'Live',
        'in_progress' => 'Live',
        'live' => 'Live',
        'waiting' => 'Waiting',
    ];

    return admin_translate_value($map[$normalized] ?? $status);
}

function admin_role_label(string $role): string
{
    return admin_translate_value(ucfirst(trim($role)));
}

function admin_membership_label(string $status): string
{
    $normalized = strtolower(trim($status));
    $map = [
        'approved' => 'Approved',
        'pending' => 'Pending',
        'rejected' => 'Rejected',
        'not_submitted' => admin_text('Not Submitted', 'Başvuru Yok'),
    ];

    $label = $map[$normalized] ?? str_replace('_', ' ', ucfirst($normalized));
    return admin_translate_value($label);
}

function admin_round_title_label(string $title): string
{
    return admin_translate_value($title);
}

function admin_player_status_label(string $status): string
{
    return admin_translate_value($status);
}

function admin_match_flow_label(string $label): string
{
    return admin_translate_value($label);
}
