const ADMIN_LOCALE_STORAGE_KEY = "dartClubLocale";
const ADMIN_SUPPORTED_LOCALES = new Set(["en", "tr"]);
let adminLocale = document.documentElement.lang === "tr" ? "tr" : "en";

function normalizeAdminLocale(locale) {
    const normalized = String(locale || "").trim().toLowerCase();
    return ADMIN_SUPPORTED_LOCALES.has(normalized) ? normalized : "en";
}

function readStoredLocale() {
    try {
        return normalizeAdminLocale(window.localStorage.getItem(ADMIN_LOCALE_STORAGE_KEY));
    } catch (error) {
        return "en";
    }
}

function readCookieLocale() {
    const cookie = document.cookie
        .split(";")
        .map((part) => part.trim())
        .find((part) => part.startsWith(`${ADMIN_LOCALE_STORAGE_KEY}=`));

    if (!cookie) {
        return "en";
    }

    return normalizeAdminLocale(decodeURIComponent(cookie.split("=")[1] || "en"));
}

function writeCookieLocale(locale) {
    document.cookie = `${ADMIN_LOCALE_STORAGE_KEY}=${encodeURIComponent(locale)}; path=/; max-age=31536000; SameSite=Lax`;
}

function storeAdminLocale(locale) {
    try {
        window.localStorage.setItem(ADMIN_LOCALE_STORAGE_KEY, locale);
    } catch (error) {
        console.warn("Failed to store admin locale preference:", error);
    }
}

function adminLocaleText(copy) {
    if (typeof copy === "string") {
        return copy;
    }

    if (!copy || typeof copy !== "object") {
        return "";
    }

    return copy[adminLocale] ?? copy.en ?? Object.values(copy)[0] ?? "";
}

function getSidenav() {
    return document.getElementById("sidenav");
}

function setSidenavOpen(isOpen) {
    const sidenav = getSidenav();
    if (!sidenav) {
        return;
    }

    sidenav.style.width = isOpen ? "250px" : "0";
    sidenav.classList.toggle("is-open", isOpen);
    sidenav.setAttribute("aria-hidden", isOpen ? "false" : "true");
}

function isSidenavOpen() {
    const sidenav = getSidenav();
    if (!sidenav) {
        return false;
    }

    return Number.parseFloat(window.getComputedStyle(sidenav).width || "0") > 0;
}

function openNav() {
    setSidenavOpen(true);
}

function closeNav() {
    setSidenavOpen(false);
}

function syncAdminLocalePreference() {
    const documentLocale = normalizeAdminLocale(document.documentElement.lang);
    const storedLocale = readStoredLocale();
    const cookieLocale = readCookieLocale();
    const preferredLocale = normalizeAdminLocale(storedLocale || cookieLocale || documentLocale);

    adminLocale = preferredLocale;
    document.documentElement.lang = preferredLocale;
    storeAdminLocale(preferredLocale);
    writeCookieLocale(preferredLocale);

    return preferredLocale !== documentLocale;
}

function setAdminLocale(nextLocale) {
    const normalizedLocale = normalizeAdminLocale(nextLocale);
    storeAdminLocale(normalizedLocale);
    writeCookieLocale(normalizedLocale);
    adminLocale = normalizedLocale;

    if (normalizeAdminLocale(document.documentElement.lang) !== normalizedLocale) {
        window.location.reload();
        return;
    }

    updateAdminLocaleRail();
    localizeAdminSidebar();
}

function ensureAdminLocaleRail() {
    let rail = document.querySelector(".admin-shell-tools");
    if (!rail) {
        rail = document.createElement("div");
        rail.className = "admin-shell-tools";
        rail.innerHTML = `
            <span class="admin-shell-tools-label" data-admin-locale-label></span>
            <div class="admin-shell-locale-switch" role="group" aria-label="">
                <button type="button" class="admin-shell-locale-button" data-locale="en">EN</button>
                <button type="button" class="admin-shell-locale-button" data-locale="tr">TR</button>
            </div>
        `;
        document.body.appendChild(rail);

        rail.querySelectorAll("[data-locale]").forEach((button) => {
            button.addEventListener("click", () => {
                setAdminLocale(button.dataset.locale || "en");
            });
        });
    }

    updateAdminLocaleRail();
}

function updateAdminLocaleRail() {
    const rail = document.querySelector(".admin-shell-tools");
    if (!rail) {
        return;
    }

    const switcher = rail.querySelector(".admin-shell-locale-switch");
    const labelNode = rail.querySelector("[data-admin-locale-label]");
    if (switcher) {
        const label = adminLocaleText({
            en: "Admin language",
            tr: "Yönetim dili",
        });
        switcher.setAttribute("aria-label", label);
        switcher.setAttribute("title", label);
        if (labelNode) {
            labelNode.textContent = label;
        }
    }

    rail.querySelectorAll(".admin-shell-locale-button").forEach((button) => {
        const isActive = button.dataset.locale === adminLocale;
        button.classList.toggle("is-active", isActive);
        button.setAttribute("aria-pressed", isActive ? "true" : "false");
        button.setAttribute(
            "title",
            button.dataset.locale === "tr"
                ? adminLocaleText({ en: "Switch to Turkish", tr: "Türkçeye geç" })
                : adminLocaleText({ en: "Switch to English", tr: "İngilizceye geç" })
        );
    });
}

function localizeAdminSidebar() {
    const sidenav = getSidenav();
    if (!sidenav) {
        return;
    }

    sidenav.querySelectorAll("a").forEach((link) => {
        const href = link.getAttribute("href") || "";

        if (link.classList.contains("closebtn")) {
            const closeLabel = adminLocaleText({ en: "Close menu", tr: "Menüyü kapat" });
            link.setAttribute("aria-label", closeLabel);
            link.setAttribute("title", closeLabel);
            return;
        }

        if (href.includes("manage_players.php")) {
            link.textContent = adminLocaleText({ en: "Manage Players", tr: "Oyuncuları Yönet" });
            return;
        }

        if (href.includes("manage_tournaments.php")) {
            link.textContent = adminLocaleText({ en: "Manage Tournaments", tr: "Turnuvaları Yönet" });
            return;
        }

        if (href.includes("manage_users.php")) {
            link.textContent = adminLocaleText({ en: "Manage Users", tr: "Kullanıcıları Yönet" });
            return;
        }

        if (href.includes("../main.php")) {
            link.textContent = adminLocaleText({ en: "Back to Website", tr: "Siteye Dön" });
        }
    });
}

function initAdminNavInteractions() {
    const sidenav = getSidenav();
    if (sidenav && !sidenav.hasAttribute("aria-hidden")) {
        sidenav.setAttribute("aria-hidden", isSidenavOpen() ? "false" : "true");
    }

    document.addEventListener("click", (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        if (
            isSidenavOpen() &&
            !target.closest("#sidenav") &&
            !target.closest('[onclick*="openNav"]') &&
            !target.closest("[data-admin-nav-toggle]")
        ) {
            setSidenavOpen(false);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            setSidenavOpen(false);
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    if (syncAdminLocalePreference()) {
        window.location.reload();
        return;
    }

    ensureAdminLocaleRail();
    localizeAdminSidebar();
    initAdminNavInteractions();
});

window.getAdminLocale = () => adminLocale;
window.adminLocaleText = adminLocaleText;
window.setAdminLocale = setAdminLocale;

function viewApplication(appPath) {
    const iframe = document.getElementById("iframe");
    const applicationFrame = document.getElementById("applicationFrame");
    if (!iframe || !applicationFrame) {
        return;
    }

    iframe.src = appPath;
    applicationFrame.style.display = "block";
}
