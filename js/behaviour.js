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
const navLink = document.querySelectorAll(".nav__link");

const linkAction = () => {
  const navMenu = document.getElementById("nav-menu");
  // When we click on each nav__link, we remove the show-menu class
  navMenu.classList.remove("show-menu");
};
navLink.forEach((n) => n.addEventListener("click", linkAction));

//for changing background header

const scrollHeader = () => {
  const header = document.getElementById("header");
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
        ".nav__menu a[href*=" + sectionId + "]"
      );

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
  // When the scroll is higher than 350 viewport height, add the show-scroll class to the a tag with the scrollup class
  this.scrollY >= 350
    ? scrollUp.classList.add("show-scroll")
    : scrollUp.classList.remove("show-scroll");
};
window.addEventListener("scroll", scrollUp);

// Smooth scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
      e.preventDefault();
      console.log(e.target.getAttribute('href'));
      console.log(typeof(document.querySelector(this.getAttribute('href'))));

      document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
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

sr.reveal(`.home__data, .footer__container, .footer__group`);
sr.reveal(`.home__img`, { delay: 700, origin: "bottom" });
sr.reveal(`.logos__img, .event__card`, { interval: 100 });
sr.reveal(`.contactus__img, .about__content`, { origin: "left" });
sr.reveal(`.contactus__content, .about__img`, { origin: "right" });

const PUBLIC_LOGO = "../files/media/images/logo.png";

function currentPageName() {
  const parts = window.location.pathname.split("/");
  return parts[parts.length - 1] || "main.php";
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
  await fetch("../services/logout.php", { method: "POST" });
  window.location.href = "main.php";
}

function buildNavItems(context) {
  const items = [
    { href: "main.php", label: "Home" },
    { href: "tournaments.html", label: "Tournaments" },
    { href: "blog.html", label: "Blog" },
    { href: "gallery.html", label: "Gallery" },
    { href: "register.html", label: "Join the Club" },
  ];

  if (context.logged_in) {
    items.push({ href: "profile.html", label: "Profile" });
  }

  if (context.can_manage_club) {
    items.push({ href: "admin/admin_panel.php", label: "Admin" });
  }

  return items
    .map((item) => `<li class="nav__item"><a href="${item.href}" class="${setActiveLinkClass(item.href)}">${item.label}</a></li>`)
    .join("");
}

function buildAuthActions(context) {
  if (context.logged_in) {
    return `
      <li class="nav__item">
        <a href="#" class="button nav__button" id="shell-logout-btn">Logout</a>
      </li>
    `;
  }

  return `
    <li class="nav__item"><a href="login.html" class="${setActiveLinkClass("login.html")}">Sign In</a></li>
    <li class="nav__item"><a href="sign_up.html" class="button nav__button">Create Account</a></li>
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
            Follow tournaments, blog updates, and club activity in one place.
          </p>
          <p class="footer__description">
            ${
              context.logged_in
                ? `Signed in as ${escapeHtml(context.user_name)}`
                : "Create an account to register for tournaments and apply for membership."
            }
          </p>
        </div>
        <div class="footer__content">
          <div>
            <h3 class="footer__title">EXPLORE</h3>
            <ul class="footer__links">
              <li><a href="tournaments.html" class="${footerLinkClass("tournaments.html")}">Tournament Hub</a></li>
              <li><a href="blog.html" class="${footerLinkClass("blog.html")}">Club Blog</a></li>
              <li><a href="gallery.html" class="${footerLinkClass("gallery.html")}">Gallery</a></li>
            </ul>
          </div>
          <div>
            <h3 class="footer__title">ACCOUNT</h3>
            <ul class="footer__links">
              <li><a href="profile.html" class="${footerLinkClass("profile.html")}">Player Dashboard</a></li>
              <li><a href="register.html" class="${footerLinkClass("register.html")}">Membership</a></li>
              <li><a href="about.html" class="${footerLinkClass("about.html")}">About</a></li>
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
          <a href="mailto:info@dartclub.local" class="footer__social-link">
            <i class="ri-mail-fill"></i>
          </a>
        </ul>
        <span class="footer__copy">&#169; Famagusta Dart Club. All rights reserved.</span>
      </div>
    </footer>
  `;
}

async function hydratePublicShell() {
  try {
    const response = await fetch("../services/get_session_context.php");
    const context = await response.json();

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

    const footerLogo = document.querySelector(".footer__logo img");
    if (footerLogo) {
      footerLogo.setAttribute("src", PUBLIC_LOGO);
    }

    let footer = document.querySelector(".footer");
    if (!footer) {
      const wrapper = document.createElement("div");
      wrapper.innerHTML = buildFooterHtml(context);
      document.body.appendChild(wrapper.firstElementChild);
    } else {
      footer.outerHTML = buildFooterHtml(context);
    }

    const logoutButton = document.getElementById("shell-logout-btn");
    if (logoutButton) {
      logoutButton.addEventListener("click", (event) => {
        event.preventDefault();
        logoutFromShell();
      });
    }
  } catch (error) {
    console.error("Failed to hydrate public shell", error);
  }
}

document.addEventListener("DOMContentLoaded", hydratePublicShell);
