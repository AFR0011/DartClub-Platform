//SHOW MENU
const navMenu = document.getElementById("nav-menu"),
  navToggle = document.getElementById("nav-toggle"),
  navClose = document.getElementById("nav-close");

// Menu show
if (navToggle) {
  navToggle.addEventListener("click", () => {
    navMenu.classList.add("show-menu");
  });
}

// Menu hidden
if (navClose) {
  navClose.addEventListener("click", () => {
    navMenu.classList.remove("show-menu");
  });
}

// REMOVE MENU MOBILE
document.addEventListener("click", (event) => {
  const clickedNavLink = event.target.closest(".nav__link");
  if (!clickedNavLink) {
    return;
  }

  const mobileMenu = document.getElementById("nav-menu");
  if (mobileMenu) {
    mobileMenu.classList.remove("show-menu");
  }
});

//for changing background header

const scrollHeader = () => {
  const header = document.getElementById("header");
  if (!header) {
    return;
  }

  // Add a class if the bottom offset is greater than 50 of the viewport
  this.scrollY >= 50
    ? header.classList.add("bg-header")
    : header.classList.remove("bg-header");
};
window.addEventListener("scroll", scrollHeader);

//scroll section

const sections = document.querySelectorAll("section[id]");

const scrollActive = () => {
  const scrollDown = window.scrollY;

  sections.forEach((current) => {
    const sectionHeight = current.offsetHeight,
      sectionTop = current.offsetTop - 58,
      sectionId = current.getAttribute("id"),
      sectionsClass = document.querySelector(
        `.nav__menu a[href*="${sectionId}"]`
      );

    if (!sectionsClass) {
      return;
    }

    if (scrollDown > sectionTop && scrollDown <= sectionTop + sectionHeight) {
      sectionsClass.classList.add("active-link");
    } else {
      sectionsClass.classList.remove("active-link");
    }
  });
};
window.addEventListener("scroll", scrollActive);

//Scroll up
const scrollUp = () => {
  const scrollUp = document.getElementById("scroll-up");
  if (!scrollUp) {
    return;
  }

  // When the scroll is higher than 350 viewport height, add the show-scroll class to the a tag with the scrollup class
  this.scrollY >= 350
    ? scrollUp.classList.add("show-scroll")
    : scrollUp.classList.remove("show-scroll");
};
window.addEventListener("scroll", scrollUp);

async function appFetchJson(url, options = {}) {
  const response = await fetch(url, options);
  const responseText = await response.text();

  let payload = null;
  if (responseText !== "") {
    try {
      payload = JSON.parse(responseText);
    } catch (error) {
      const preview = responseText.trim().slice(0, 160);
      throw new Error(`Invalid JSON response from ${url}: ${preview}`);
    }
  }

  if (!response.ok) {
    const message =
      payload?.message ||
      payload?.error ||
      `Request failed with status ${response.status}.`;
    throw new Error(message);
  }

  return payload;
}

window.appFetchJson = appFetchJson;

const APP_LOCALE_STORAGE_KEY = "dartClubLocale";
const APP_SUPPORTED_LOCALES = new Set(["en", "tr"]);
let appLocale = "en";
let scrollUpFooterObserver = null;
let pageNavigationLocked = false;

function readStoredLocale() {
  try {
    const storedLocale = window.localStorage.getItem(APP_LOCALE_STORAGE_KEY);
    return APP_SUPPORTED_LOCALES.has(storedLocale) ? storedLocale : "en";
  } catch (error) {
    return "en";
  }
}

function appLocaleText(copy) {
  if (typeof copy === "string") {
    return copy;
  }

  if (!copy || typeof copy !== "object") {
    return "";
  }

  return copy[appLocale] ?? copy.en ?? Object.values(copy)[0] ?? "";
}

function applyLocaleAttributes(root = document) {
  document.documentElement.lang = appLocale === "tr" ? "tr" : "en";

  root.querySelectorAll("[data-i18n-en]").forEach((element) => {
    const nextValue = appLocale === "tr" ? element.dataset.i18nTr : element.dataset.i18nEn;
    const attr = element.dataset.i18nAttr || "text";

    if (typeof nextValue === "undefined") {
      return;
    }

    if (attr === "html") {
      element.innerHTML = nextValue;
      return;
    }

    if (attr === "placeholder") {
      element.setAttribute("placeholder", nextValue);
      return;
    }

    if (attr === "value") {
      element.value = nextValue;
      return;
    }

    element.textContent = nextValue;
  });

  root.querySelectorAll("[data-i18n-aria-label-en]").forEach((element) => {
    const nextValue =
      appLocale === "tr"
        ? element.dataset.i18nAriaLabelTr
        : element.dataset.i18nAriaLabelEn;

    if (typeof nextValue !== "undefined") {
      element.setAttribute("aria-label", nextValue);
    }
  });
}

function closeLanguageDock() {
  document.querySelectorAll(".nav__locale.is-open").forEach((dock) => {
    dock.classList.remove("is-open");
    dock.querySelector(".nav__locale-trigger")?.setAttribute("aria-expanded", "false");
  });
}

function updateLanguageDock() {
  const dock = document.querySelector(".nav__locale");
  if (!dock) {
    return;
  }

  const trigger = dock.querySelector(".nav__locale-trigger");
  const current = dock.querySelector(".nav__locale-current");
  if (current) {
    current.textContent = appLocale.toUpperCase();
  }

  if (trigger) {
    const triggerLabel = appLocaleText({ en: "Language", tr: "Dil" });
    trigger.setAttribute("aria-label", triggerLabel);
    trigger.setAttribute("title", triggerLabel);
  }

  dock.querySelectorAll(".nav__locale-option").forEach((option) => {
    const selected = option.dataset.locale === appLocale;
    option.classList.toggle("is-active", selected);
    option.setAttribute("aria-pressed", selected ? "true" : "false");
  });

  const englishOption = dock.querySelector('.nav__locale-option[data-locale="en"]');
  if (englishOption) {
    englishOption.innerHTML = `<span>${appLocaleText({ en: "English", tr: "Ingilizce" })}</span><span class="nav__locale-option-code">EN</span>`;
  }

  const turkishOption = dock.querySelector('.nav__locale-option[data-locale="tr"]');
  if (turkishOption) {
    turkishOption.innerHTML = `<span>${appLocaleText({ en: "Turkish", tr: "Turkce" })}</span><span class="nav__locale-option-code">TR</span>`;
  }
}

function ensureLanguageDock() {
  const nav = document.querySelector(".nav");
  if (!nav) {
    return;
  }

  let dock = nav.querySelector(".nav__utility");
  if (!dock) {
    dock = document.createElement("div");
    dock.className = "nav__utility";
    dock.innerHTML = `
      <div class="nav__locale">
        <button type="button" class="nav__locale-trigger" aria-haspopup="true" aria-expanded="false">
          <i class="ri-earth-line nav__locale-icon" aria-hidden="true"></i>
          <span class="nav__locale-current">${appLocale.toUpperCase()}</span>
          <span class="nav__locale-label"></span>
        </button>
        <div class="nav__locale-panel" role="menu">
          <button type="button" class="nav__locale-option" data-locale="en" role="menuitemradio"></button>
          <button type="button" class="nav__locale-option" data-locale="tr" role="menuitemradio"></button>
        </div>
      </div>
    `;

    const toggle = nav.querySelector(".nav__toggle");
    if (toggle && toggle.parentElement === nav) {
      nav.insertBefore(dock, toggle);
    } else {
      nav.appendChild(dock);
    }

    const localeMenu = dock.querySelector(".nav__locale");
    const trigger = dock.querySelector(".nav__locale-trigger");

    trigger?.addEventListener("click", (event) => {
      event.preventDefault();
      const willOpen = !localeMenu?.classList.contains("is-open");
      closeLanguageDock();
      if (willOpen && localeMenu && trigger) {
        localeMenu.classList.add("is-open");
        trigger.setAttribute("aria-expanded", "true");
      }
    });

    dock.querySelectorAll(".nav__locale-option").forEach((option) => {
      option.addEventListener("click", () => {
        setAppLocale(option.dataset.locale || "en");
        closeLanguageDock();
      });
    });

    if (!document.body.dataset.localeDockBound) {
      document.body.dataset.localeDockBound = "true";
      document.addEventListener("click", (event) => {
        if (!event.target.closest(".nav__locale")) {
          closeLanguageDock();
        }
      });
      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          closeLanguageDock();
        }
      });
    }
  }

  updateLanguageDock();
}

function setAppLocale(nextLocale) {
  const normalizedLocale = APP_SUPPORTED_LOCALES.has(nextLocale) ? nextLocale : "en";
  if (normalizedLocale === appLocale) {
    updateLanguageDock();
    return;
  }

  appLocale = normalizedLocale;

  try {
    window.localStorage.setItem(APP_LOCALE_STORAGE_KEY, appLocale);
  } catch (error) {
    console.warn("Failed to store locale preference:", error);
  }

  applyLocaleAttributes();
  updateLanguageDock();
  window.dispatchEvent(
    new CustomEvent("app:localechange", {
      detail: { locale: appLocale },
    })
  );
}

function initScrollUpFooterAvoidance() {
  const scrollUpButton = document.getElementById("scroll-up");
  const footer = document.getElementById("footer");

  if (!scrollUpButton || !footer || typeof IntersectionObserver === "undefined") {
    return;
  }

  if (scrollUpFooterObserver) {
    scrollUpFooterObserver.disconnect();
  }

  scrollUpFooterObserver = new IntersectionObserver(
    (entries) => {
      const footerVisible = entries.some((entry) => entry.isIntersecting);
      scrollUpButton.classList.toggle("scrollup--avoid-footer", footerVisible);
    },
    { threshold: 0.18 }
  );

  scrollUpFooterObserver.observe(footer);
}

function ensurePageTransitionOverlay() {
  if (!document.body || document.querySelector(".page-transition-overlay")) {
    return;
  }

  const overlay = document.createElement("div");
  overlay.className = "page-transition-overlay";
  document.body.appendChild(overlay);
}

function initPageTransitions() {
  if (!document.body) {
    return;
  }

  ensurePageTransitionOverlay();
  window.requestAnimationFrame(() => {
    document.body.classList.add("page-ready");
    document.body.classList.remove("page-is-leaving");
  });

  if (document.body.dataset.pageTransitionsBound === "true") {
    return;
  }

  document.body.dataset.pageTransitionsBound = "true";
  document.addEventListener("click", (event) => {
    const anchor = event.target.closest("a[href]");
    if (!anchor || pageNavigationLocked) {
      return;
    }

    if (
      event.defaultPrevented ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey ||
      anchor.target === "_blank" ||
      anchor.hasAttribute("download")
    ) {
      return;
    }

    const rawHref = anchor.getAttribute("href") || "";
    if (
      rawHref === "" ||
      rawHref.startsWith("#") ||
      rawHref.startsWith("javascript:") ||
      rawHref === "#"
    ) {
      return;
    }

    const targetUrl = new URL(anchor.href, window.location.href);
    if (
      anchor.dataset.skipTransition === "true" ||
      /\.(pdf|doc|docx|xls|xlsx|zip|rar)$/i.test(targetUrl.pathname)
    ) {
      return;
    }

    if (targetUrl.origin !== window.location.origin) {
      return;
    }

    if (
      targetUrl.pathname === window.location.pathname &&
      targetUrl.search === window.location.search &&
      targetUrl.hash
    ) {
      return;
    }

    event.preventDefault();
    pageNavigationLocked = true;
    document.body.classList.add("page-is-leaving");

    window.setTimeout(() => {
      window.location.href = targetUrl.href;
    }, 180);
  });
}

// Smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
      const targetSelector = this.getAttribute("href");
      if (!targetSelector || targetSelector === "#") {
        return;
      }

      const targetElement = document.querySelector(targetSelector);
      if (!targetElement) {
        return;
      }

      e.preventDefault();
      targetElement.scrollIntoView({
          behavior: "smooth"
      });
  });
});


//scroll reveal
const sr = ScrollReveal({
  origin: "top",
  distance: "60px",
  duration: 2500,
  delay: 400,
});

sr.reveal(`.home__data, .home_data, .footer__container, .footer__group`);
sr.reveal(`.home__img`, { delay: 700, origin: "bottom" });
sr.reveal(`.logos__img, .event__card`, { interval: 100 });
sr.reveal(`.contactus__img, .about__content`, { origin: "left" });
sr.reveal(`.contactus__content, .about__img`, { origin: "right" });

const PUBLIC_LOGO = "../files/media/images/logo.png";

function currentPageName() {
  const parts = window.location.pathname.split("/");
  return parts[parts.length - 1] || "main.php";
}

function isAuthPage() {
  return ["login.html", "sign_up.html", "reset_password.html"].includes(
    currentPageName()
  );
}

function setActiveLinkClass(linkPath) {
  const page = currentPageName();
  return page === linkPath ? "nav__link active-link" : "nav__link";
}

function footerLinkClass(linkPath) {
  const page = currentPageName();
  return page === linkPath ? "footer__link active-link" : "footer__link";
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

async function logoutFromShell() {
  await appFetchJson("../services/logout.php", { method: "POST" });
  window.location.href = "main.php";
}

function buildNavItems(context) {
  const items = [
    { href: "main.php", label: appLocaleText({ en: "Home", tr: "Ana Sayfa" }) },
    {
      href: "tournaments.html",
      label: appLocaleText({ en: "Tournaments", tr: "Turnuvalar" }),
    },
    { href: "blog.html", label: appLocaleText({ en: "Blog", tr: "Blog" }) },
    { href: "gallery.html", label: appLocaleText({ en: "Gallery", tr: "Galeri" }) },
    {
      href: "register.html",
      label: appLocaleText({ en: "Join the Club", tr: "Kulube Katil" }),
    },
  ];

  if (context.logged_in) {
    items.push({
      href: "profile.html",
      label: appLocaleText({ en: "Profile", tr: "Profil" }),
    });
  }

  if (context.can_manage_club) {
    items.push({
      href: "admin/admin_panel.php",
      label: appLocaleText({ en: "Admin", tr: "Yonetim" }),
    });
  }

  return items
    .map((item) => `<li class="nav__item"><a href="${item.href}" class="${setActiveLinkClass(item.href)}">${item.label}</a></li>`)
    .join("");
}

function buildAuthActions(context) {
  if (context.logged_in) {
    return `
      <li class="nav__item">
        <a href="#" class="button nav__button" id="shell-logout-btn">${appLocaleText({
          en: "Logout",
          tr: "Cikis Yap",
        })}</a>
      </li>
    `;
  }

  return `
    <li class="nav__item"><a href="login.html" class="${setActiveLinkClass("login.html")}">${appLocaleText({
      en: "Sign In",
      tr: "Giris Yap",
    })}</a></li>
    <li class="nav__item"><a href="sign_up.html" class="button nav__button">${appLocaleText({
      en: "Create Account",
      tr: "Hesap Olustur",
    })}</a></li>
  `;
}

function buildFooterHtml(context) {
  return `
    <footer class="footer section" id="footer">
      <div class="footer__container container grid">
        <div>
          <a href="main.php" class="footer__logo">
            <img src="${PUBLIC_LOGO}" alt="logo" /> Famagusta Dart Club
          </a>
          <p class="footer__description">
            ${appLocaleText({
              en: "Follow public brackets, match results, club news, and membership updates in one place.",
              tr: "Acik fiksturleri, mac sonuclarini, kulup haberlerini ve uyelik guncellemelerini tek yerden takip edin.",
            })}
          </p>
          <p class="footer__description">
            ${
              context.logged_in
                ? appLocaleText({
                    en: `Signed in as ${escapeHtml(context.user_name)}`,
                    tr: `${escapeHtml(context.user_name)} olarak giris yaptiniz`,
                  })
                : appLocaleText({
                    en: "Create an account to register for tournaments and submit membership paperwork.",
                    tr: "Turnuvalara kaydolmak ve uyelik belgelerini gondermek icin hesap olusturun.",
                  })
            }
          </p>
        </div>
        <div class="footer__content">
          <div>
            <h3 class="footer__title">${appLocaleText({
              en: "EXPLORE",
              tr: "KESFET",
            })}</h3>
            <ul class="footer__links">
              <li><a href="tournaments.html" class="${footerLinkClass("tournaments.html")}">${appLocaleText({
                en: "Tournament Hub",
                tr: "Turnuva Merkezi",
              })}</a></li>
              <li><a href="blog.html" class="${footerLinkClass("blog.html")}">${appLocaleText({
                en: "Club Blog",
                tr: "Kulup Blogu",
              })}</a></li>
              <li><a href="gallery.html" class="${footerLinkClass("gallery.html")}">${appLocaleText({
                en: "Gallery",
                tr: "Galeri",
              })}</a></li>
            </ul>
          </div>
          <div>
            <h3 class="footer__title">${appLocaleText({
              en: "CLUB",
              tr: "KULUP",
            })}</h3>
            <ul class="footer__links">
              <li><a href="profile.html" class="${footerLinkClass("profile.html")}">${appLocaleText({
                en: "Player Dashboard",
                tr: "Oyuncu Paneli",
              })}</a></li>
              <li><a href="register.html" class="${footerLinkClass("register.html")}">${appLocaleText({
                en: "Membership",
                tr: "Uyelik",
              })}</a></li>
              <li><a href="about.html" class="${footerLinkClass("about.html")}">${appLocaleText({
                en: "About",
                tr: "Hakkinda",
              })}</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="footer__group">
        <ul class="footer__social">
          <a href="https://www.facebook.com/GazimagusaDartsBirligi" class="footer__social-link">
            <i class="ri-facebook-circle-fill"></i>
          </a>
          <a href="tel:+905338602325" class="footer__social-link">
            <i class="ri-phone-fill"></i>
          </a>
          <a href="register.html" class="footer__social-link" aria-label="Open membership page">
            <i class="ri-team-fill"></i>
          </a>
        </ul>
        <span class="footer__copy">${appLocaleText({
          en: "&#169; Famagusta Dart Club. All rights reserved.",
          tr: "&#169; Famagusta Dart Club. Tum haklari saklidir.",
        })}</span>
      </div>
    </footer>
  `;
}

function buildCompactFooterHtml() {
  return `
    <footer class="footer section footer--compact" id="footer">
      <div class="container">
        <div class="footer__group">
          <span class="footer__copy">${appLocaleText({
            en: "&#169; Famagusta Dart Club. All rights reserved.",
            tr: "&#169; Famagusta Dart Club. Tum haklari saklidir.",
          })}</span>
        </div>
      </div>
    </footer>
  `;
}

async function hydratePublicShell() {
  try {
    const context = await appFetchJson("../services/get_session_context.php");
    const authPage = isAuthPage();

    if (authPage) {
      document.body.classList.add("auth-shell-page");
    }

    const logo = document.querySelector(".nav__logo");
    if (logo) {
      logo.setAttribute("href", "main.php");
      const logoImage = logo.querySelector("img");
      if (logoImage) {
        logoImage.setAttribute("src", PUBLIC_LOGO);
      }
    }

    const navList = document.getElementById("nav-list") || document.querySelector(".nav__list");
    if (navList) {
      navList.innerHTML = buildNavItems(context) + buildAuthActions(context);
    }

    ensureLanguageDock();

    const footerLogo = document.querySelector(".footer__logo img");
    if (footerLogo) {
      footerLogo.setAttribute("src", PUBLIC_LOGO);
    }

    let footer = document.querySelector(".footer");
    const footerHtml = authPage
      ? buildCompactFooterHtml()
      : buildFooterHtml(context);
    if (!footer) {
      const wrapper = document.createElement("div");
      wrapper.innerHTML = footerHtml;
      document.body.appendChild(wrapper.firstElementChild);
    } else {
      footer.outerHTML = footerHtml;
    }

    const logoutButton = document.getElementById("shell-logout-btn");
    if (logoutButton) {
      logoutButton.addEventListener("click", (event) => {
        event.preventDefault();
        logoutFromShell();
      });
    }

    applyLocaleAttributes();
    initScrollUpFooterAvoidance();
  } catch (error) {
    console.error("Failed to hydrate public shell", error);
  }
}

function bindHomepageEventCards() {
  document.querySelectorAll("article.event__card").forEach((card) => {
    if (card.dataset.cardBound === "1") {
      return;
    }

    card.dataset.cardBound = "1";
    card.addEventListener("click", (event) => {
      if (event.target.closest("a, button, input, select, textarea")) {
        return;
      }

      const targetLink = card.querySelector("a.event__button");
      if (targetLink) {
        targetLink.click();
      }
    });
  });
}

appLocale = readStoredLocale();

document.addEventListener("DOMContentLoaded", hydratePublicShell);
document.addEventListener("DOMContentLoaded", bindHomepageEventCards);
document.addEventListener("DOMContentLoaded", () => {
  applyLocaleAttributes();
  ensureLanguageDock();
  initPageTransitions();
  initScrollUpFooterAvoidance();
});
window.addEventListener("app:localechange", () => {
  hydratePublicShell();
});
window.addEventListener("pageshow", () => {
  pageNavigationLocked = false;
  ensurePageTransitionOverlay();
  window.requestAnimationFrame(() => {
    document.body.classList.add("page-ready");
    document.body.classList.remove("page-is-leaving");
  });
});

window.getAppLocale = () => appLocale;
window.appLocaleText = appLocaleText;
window.setAppLocale = setAppLocale;
