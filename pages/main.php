<!DOCTYPE html>

<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css"
      rel="stylesheet"
    />
    <link rel="shortcut icon" href="../files/media/images/logo.png" />
    <link rel="stylesheet" href="../css/style.css" />
    <script src="../js/main.js"></script>

    <title>Famagusta Dart Club</title>
  </head>

  <body>
    <top id="top"></top>
    <header class="header" id="header">
      <nav class="nav container">
        <a href="#top" class="nav__logo">
          <img src="../files/media/images/logo.png" alt="logo" /> Famagusta Dart Club
        </a>

        <div class="nav__menu" id="nav-menu">
          <ul id="nav-list" class="nav__list">
            <!-- Dynamically updated based on user user_role -->
          </ul>

          <div class="nav__close" id="nav-close">
            <i class="ri-close-line"></i>
          </div>
        </div>
        <!--TOGGLE BUTTON-->
        <div class="nav__toggle" id="nav-toggle">
          <i class="ri-menu-line"></i>
        </div>
      </nav>
    </header>

    <main class="main">
      <section class="home section" id="home">
        <div class="home__container container grid">
          <div class="home_data">
            <h2 class="home__subtitle">Let's Play</h2>
            <!--WRITE MOTTO HERE-->
            <h1 class="home__title" style="color: red">Dart</h1>
            <p class="home__desctiption">Shoot for the moon!</p>
            <a href="#top" class="button button__flex">
              Get Started <i class="ri-arrow-right-line"></i>
            </a>
          </div>

          <div class="home_images">
            <img
              src="../files/media/images/homeimage.png"
              alt="home image"
              class="home__img"
            />

            <div class="home__triangle home__triangle-3"></div>
            <div class="home__triangle home__triangle-2"></div>
            <div class="home__triangle home__triangle-1"></div>
          </div>
        </div>
      </section>

      <section class="logos section">
        <div class="logos__container container grid">
          <img
            src="../files/media/images/emulogo.png"
            alt="logo image"
            class="logos__img"
          />
          <img
            src="../files/media/images/doubleedgedlogo.png"
            alt="logo image"
            class="logos__img"
          />
          <img
            src="../files/media/images/logoblackandgray.png"
            alt="logo image"
            class="logos__img"
          />
        </div>
      </section>

      <section class="events section" id="events">
        <div class="container">
          <div class="section__data">
            <h2 class="section__subtitle">Our Activities</h2>
            <div class="section__titles">
              <h1 class="section__title-border">Join</h1>
              <h1 class="section__title">Us</h1>
            </div>
          </div>
          <div class="event__container grid">
            <article class="event__card">
              <div class="event__shape">
                <img
                  src="../files/media/images/dartsicon.png"
                  alt="tournaments icon"
                  class="event__img"
                />
              </div>
              <h3 class="event__title">Tournaments</h3>

              <p class="event__description">
                Text about games and tournaments and matches and anything
                related will go here!<!--FILL THIS PART LATER-->
              </p>

              <a href="tournaments.html" class="event__button">
                <i class="ri-arrow-right-line"></i>
              </a>
            </article>
            <article class="event__card">
              <div class="event__shape">
                <img
                  src="../files/media/images/galleryicon.png"
                  alt="Gallery icon"
                  class="event__img"
                />
              </div>
              <h3 class="event__title">Gallery</h3>

              <p class="event__description">
                Text about gallery and previous events and history of the club
                and anything related will go here!<!--FILL THIS PART LATER-->
              </p>

              <a href="gallery.html" class="event__button">
                <i class="ri-arrow-right-line"></i>
              </a>
            </article>
            <article class="event__card">
              <div class="event__shape">
                <img
                  src="../files/media/images/blogicon.png"
                  alt="blog icon"
                  class="event__img"
                />
              </div>
              <h3 class="event__title">Blog</h3>

              <p class="event__description">
                Text about blog and information related to it will go here!<!--FILL THIS PART LATER-->
              </p>

              <a href="blog.html" class="event__button">
                <i class="ri-arrow-right-line"></i>
              </a>
            </article>
          </div>
        </div>
      </section>

      <section class="contactus section" id="contactus">
        <div class="contactus__overflow">
          <div class="contactus__container container grid">
            <div class="contactus__content">
              <div class="section__data">
                <h2 class="section__subtitle">Our Contact Information</h2>
                <div class="section__titles">
                  <h1 class="section__title__border">Reach out</h1>
                  <h1 class="section__title">Anytime!</h1>
                </div>
              </div>

              <p class="contactus__description">
                <!--FILL THIS PART LATER-->

                We are available 24/7. Feel free to contact us using the methods
                below.
              </p>

              <div class="contactus__data">
                <div class="contactus__group">
                  <h3 class="contactus__number">👇🏻 OUR FACEBOOK 👇🏻</h3>
                  <a
                    class="contactus__subtitle"
                    href="https://www.facebook.com/GazimagusaDartsBirligi"
                    >Facebook</a
                  >
                </div>

                <div class="contactus__group">
                  <h3 class="contactus__number">PHONE</h3>
                  <!--FILL THIS PART LATER-->
                  <p class="contactus__subtitle">+90 533 860 23 25</p>
                </div>
              </div>
            </div>

            <div class="contactus__images">
              <img
                src="../files/media/images/contactusimage.png"
                alt="contact us image"
                class="contactus__img"
              />

              <div class="contactus__triangle contactus__triangle-1"></div>
              <div class="contactus__triangle contactus__triangle-2"></div>
              <div class="contactus__triangle contactus__triangle-3"></div>
            </div>
          </div>
        </div>
      </section>

      <section class="about section" id="about">
        <div class="about__overflow">
          <div class="about__container container grid">
            <div class="about__content">
              <div class="section__data">
                <div class="section__titles">
                  <h1 class="section__title__border">About</h1>
                  <h1 class="section__title">us</h1>
                </div>
              </div>

              <p class="about__description">
                Our organization is based in TRNC and is dedicated to promoting
                the sport of darts through a variety of events and tournaments.
                Our offerings include both league and elimination modes, as well
                as national-level competitions. Our ultimate goal is to bring
                people together and foster a sense of community through the
                shared love of this exciting game We believe that darts is more
                than just a pastime, but rather a way to bring people together
                and build camaraderie. Our events are designed to be inclusive
                and accessible to players of all skill levels, so that everyone
                can experience the thrill of competition. Whether you're a
                seasoned pro or just looking to try something new, we welcome
                you to join us and be a part of our growing darts community.
                Famagusta Darts Association is a purely sports organization and
                its aim is to train darts players in Northern Cyprus and protect
                the interests of darts players. It provides solidarity and
                assistance to ensure unity and solidarity among Darts members
                without discrimination. Spiritual upliftment of members
                strengthens friendship bonds. Organizes meetings and conferences
                to inform members about Darts. It contacts and cooperates with
                similar organizations and official authorities. It organizes
                Darts competitions between members and similar organizations and
                participates in competitions organized by the Federation.
              </p>
              <a href="about.html" class="button button__flex">
                About the website <i class="ri-information-2-line"></i
                ><!--ADD SEPERATE PAGE  LATER-->
              </a>
            </div>

            <div class="about__images">
              <img
                src="../files/media/images/aboutimage2.png"
                alt="about us image"
                class="about__img"
              />

              <div class="about__triangle about__triangle-1"></div>
              <div class="about__triangle about__triangle-2"></div>
              <div class="about__triangle about__triangle-3"></div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer class="footer section" id="footer">
      <div class="footer__container container grid">
        <div>
          <a href="#top" class="footer__logo">
            <img src="../files/media/images/logo.png" alt="logo img" />Famagusta Dart Club
          </a>
          <p class="footer__description">
            <!--Something's wrong with this line(CSS most likely)-->
            Register for <br />
            updates below.
          </p>

          <form action="" class="footer__from" id="contact-form">
            <input
              type="email"
              name="user_email"
              placeholder="Your Email"
              class="footer__input"
              id="contact-user"
            />
            <button class="button" type="submit">Register</button>
          </form>
          <p class="footer__message" id="contact-message"></p>
        </div>

        <div class="footer__content">
          <div>
            <h3 class="footer__title">SERVICES</h3>

            <ul class="footer__links">
              <li>
                <a href="#top" class="footer__link">Tournaments</a
                ><!--FILL THIS PART LATER-->
              </li>
              <li>
                <a href="#top" class="footer__link">Gallery</a
                ><!--FILL THIS PART LATER-->
              </li>
              <li>
                <a href="#top" class="footer__link">Blog</a
                ><!--FILL THIS PART LATER-->
              </li>
            </ul>
          </div>

          <div>
            <h3 class="footer__title">ABOUT US</h3>

            <ul class="footer__links">
              <li>
                <a href="#top" class="footer__link">About The Club</a>
              </li>
              <li>
                <a href="#top" class="footer__link">About The Website</a
                ><!--FILL THIS PART LATER-->
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="container">
        <!--Either HTML of this div or previous div, or the CSS might need debugging-->
        <div class="footer__group">
          <ul class="footer__social">
            <a
              href="https://www.facebook.com/GazimagusaDartsBirligi"
              class="footer__social-link"
            >
              <i class="ri-facebook-circle-fill"></i>
            </a>
            <a href="" class="footer__social-link">
              <i class="ri-phone-fill"></i>
            </a>
          </ul>

          <span class="footer__copy">
            &#169; copyright Nazife Dimililer. All rights reserved
          </span>
        </div>
      </div>
    </footer>

    <a href="#top" class="scrollup" id="scroll-up">
      <i class="ri-arrow-up-line"></i>
    </a>

    <!-- Scroll reveal -->
    <script src="../js/scrollreveal.min.js"></script>

    <!--EMAIL JS-->
    <script
      type="text/javascript"
      src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"
    ></script>
    <!--primary js file-->
    <script src="../js/behaviour.js"></script>
    <script>
      // Function for logging out
      function logout() {
        sessionStorage.clear();
        fetch("../services/logout.php")
          .then((response) => response.json())
          .then((data) => {
            if (!data.success) {
              console.error("Logout failed:", data.message);
            }
          })
          .catch((error) => {
            console.error("Error: ", error);
          });
        location.reload(true);
      }
      // Function to update navigation bar based on user user_role
      function updateNavBar(user_role) {
        const navList = document.getElementById("nav-list");
        navList.innerHTML = ""; // Clear existing links

        // Common links for all roles
        const commonLinks = `
        <li class="nav__item">
          <a href="#home" class="nav__link active-link">Home</a>
        </li>
        <li class="nav__item">
          <a href="#events" class="nav__link">Events</a>
        </li>
        <li class="nav__item">
          <a href="#contactus" class="nav__link">Contact us</a>
        </li>
        <li class="nav__item">
          <a href="#about" class="nav__link">About</a>
        </li>
        <li class="nav__item">
          <a href="profile.html" class="nav__link">Profile</a>
        </li>`;

        navList.innerHTML += commonLinks;

        // Additional links based on user_role
        if (user_role === "admin" || user_role === "manager") {
          navList.innerHTML += `
          <li class="nav__item">
            <a href="admin/admin_panel.php" class="nav__link">Admin Panel</a>
          </li>
        `;
        }

        if (user_role === "player") {
          navList.innerHTML += `
          <li class="nav__item">
            <a href="profile.html" class="nav__link">Profile</a>
          </li>
          <li class="nav__item">
            <a href="tournaments.html" class="nav__link">Tournaments</a>
          </li>
          <li class="nav__item">
            <a href="blog.html" class="nav__link">Blog</a>
          </li>
          <li class="nav__item">
            <a href="gallery.html" class="nav__link">Gallery</a>
          </li>`;
        }

        if (user_role === "guest") {
          navList.innerHTML += `
    <li class="nav__item">
      <a href="login.html" class="button nav__button">Login</a>
    </li>
    <li class="nav__item">
      <a href="register.html" class="button nav__button">Register</a>
    </li>`;
        }
        
        if (user_role !== "guest") {
          navList.innerHTML += `
    <li class="nav__item">
        <button class="button nav__button" onclick="logout()">Logout</button>
    </li>`;
        }
      }

      // Fetch user user_role from server
      fetch("../services/getUserRole.php")
        .then((response) => response.json())
        .then((data) => {
          if (!data.user_role) {
            console.error("Error fetching user user_role:", data.error);
          }
          updateNavBar(data.user_role);
        })
        .catch((error) => console.error("Error:", error));
    </script>
  </body>
</html>
