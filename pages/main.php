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
            <!-- Dynamically updated based on session context -->
          </ul>

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
      <section class="home section" id="home">
        <div class="home__container container grid">
          <div class="home_data">
            <h2 class="home__subtitle" data-i18n-en="Play. Compete. Belong." data-i18n-tr="Oyna. Yaris. Ait ol.">Play. Compete. Belong.</h2>
            <h1 class="home__title" style="color: red" data-i18n-en="Famagusta Dart Club" data-i18n-tr="Gazimagusa Dart Kulubu">Famagusta Dart Club</h1>
            <p class="home__desctiption" data-i18n-en="Follow club tournaments, player stories, match progress, and membership activity from one shared home base." data-i18n-tr="Kulup turnuvalarini, oyuncu hikayelerini, mac ilerleyisini ve uyelik sureclerini tek bir merkezden takip edin.">
              Follow club tournaments, player stories, match progress, and membership activity from one shared home base.
            </p>
            <div class="home__actions">
              <a href="tournaments.html" class="button button__flex">
                <span data-i18n-en="Explore Tournaments" data-i18n-tr="Turnuvalari Kesfet">Explore Tournaments</span> <i class="ri-arrow-right-line"></i>
              </a>
            </div>
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
            alt="Eastern Mediterranean University logo"
            class="logos__img"
            loading="lazy"
          />
          <img
            src="../files/media/images/doubleedgedlogo.png"
            alt="Neo DoubleEdged logo"
            class="logos__img"
            loading="lazy"
          />
          <img
            src="../files/media/images/logoblackandgray.png"
            alt="Studio logo placeholder"
            class="logos__img"
            loading="lazy"
          />
          <div class="logos__placeholder" data-i18n-en="Personal logo slot reserved" data-i18n-tr="Kisisel logo alani ayrildi">Personal logo slot reserved</div>
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
                  loading="lazy"
                />
              </div>
              <h3 class="event__title">Tournaments</h3>

              <p class="event__description" data-i18n-en="Track open registrations, published fixtures, and live results across round robin, league, group, elimination, and double-elimination events." data-i18n-tr="Acik kayitlari, yayinlanan fiksturleri ve round robin, lig, grup, eliminasyon ile double-elimination turnuvalarindaki canli sonuclari takip edin.">
                Track open registrations, published fixtures, and live results across round robin, league, group, elimination, and double-elimination events.
              </p>

              <a href="tournaments.html" class="event__button">
                <i class="ri-arrow-right-line"></i>
              </a>
            </article>
            <article class="event__card">
              <div class="event__shape">
                <img
                  src="../files/media/images/galleryicon.png"
                  alt="gallery icon"
                  class="event__img"
                  loading="lazy"
                />
              </div>
              <h3 class="event__title">Gallery</h3>

              <p class="event__description" data-i18n-en="Browse match-day photography, club moments, and media pulled directly from tournament recaps and community posts." data-i18n-tr="Mac gunu fotograflarini, kulup anlarini ve turnuva ozetleriyle topluluk paylasimlarindan gelen medyalari inceleyin.">
                Browse match-day photography, club moments, and media pulled directly from tournament recaps and community posts.
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
                  loading="lazy"
                />
              </div>
              <h3 class="event__title">Blog</h3>

              <p class="event__description" data-i18n-en="Read announcements, recap articles, and member-authored updates that document what is happening around the club." data-i18n-tr="Duyurulari, ozet yazilarini ve kulupte olup biteni anlatan uye paylasimlarini okuyun.">
                Read announcements, recap articles, and member-authored updates that document what is happening around the club.
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
                  <h1 class="section__title__border" data-i18n-en="Reach out" data-i18n-tr="Bize ulasin">Reach out</h1>
                  <h1 class="section__title" data-i18n-en="Anytime" data-i18n-tr="Her zaman">Anytime</h1>
                </div>
              </div>

              <p class="contactus__description" data-i18n-en="Tournament questions, membership paperwork, and general club updates are coordinated through the official Facebook page and club phone line." data-i18n-tr="Turnuva sorulari, uyelik belgeleri ve genel kulup guncellemeleri resmi Facebook sayfasi ile kulup telefon hatti uzerinden yonetilir.">
                Tournament questions, membership paperwork, and general club updates are coordinated through the official Facebook page and club phone line.
              </p>

              <div class="contactus__data">
                <div class="contactus__group">
                  <h3 class="contactus__number">FACEBOOK</h3>
                  <a
                    class="contactus__subtitle"
                    href="https://www.facebook.com/GazimagusaDartsBirligi"
                    target="_blank"
                    rel="noreferrer"
                  >
                    Gazimagusa Darts Birligi
                  </a>
                </div>

                <div class="contactus__group">
                  <h3 class="contactus__number">PHONE</h3>
                  <p class="contactus__subtitle">+90 533 860 23 25</p>
                </div>
              </div>
            </div>

            <div class="contactus__images">
              <img
                src="../files/media/images/contactusimage.png"
                alt="contact us image"
                class="contactus__img"
                loading="lazy"
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
                  <h1 class="section__title__border" data-i18n-en="About" data-i18n-tr="Hakkinda">About</h1>
                  <h1 class="section__title" data-i18n-en="Us" data-i18n-tr="Biz">Us</h1>
                </div>
              </div>

              <p class="about__description" data-i18n-en="Famagusta Dart Club brings together local players, club members, and competition organizers around one shared darts calendar. The website now handles public tournament discovery, player profiles, membership applications, club news, and media so the whole community can follow the same workflow. Whether you are joining your first event or tracking a full tournament run, the goal is the same: make the club easy to follow and easy to participate in." data-i18n-tr="Gazimagusa Dart Kulubu; yerel oyunculari, kulup uyelerini ve organizatorleri tek bir ortak dart takvimi etrafinda bulusturur. Web sitesi artik acik turnuva kesfini, oyuncu profillerini, uyelik basvurularini, kulup haberlerini ve medyayi tek bir akista topluyor. Ister ilk etkinliginize katilin ister tam bir turnuva yolculugunu takip edin, amac ayni: kulubu takip etmeyi ve katilimi kolaylastirmak.">
                Famagusta Dart Club brings together local players, club members, and competition organizers around one shared darts calendar.
                The website now handles public tournament discovery, player profiles, membership applications, club news, and media so the whole community can follow the same workflow.
                Whether you are joining your first event or tracking a full tournament run, the goal is the same: make the club easy to follow and easy to participate in.
              </p>
              <div style="display:flex; flex-wrap:wrap; gap:0.85rem; justify-content:center;">
                <a href="about.html" class="button button__flex">
                  <span data-i18n-en="About the Club & Platform" data-i18n-tr="Kulup ve Platform Hakkinda">About the Club & Platform</span> <i class="ri-information-2-line"></i>
                </a>
                <a href="developers.html" class="button button__flex" style="background:rgba(255,255,255,0.05); color:#fff; border-color:rgba(255,255,255,0.16);">
                  <span data-i18n-en="Meet the Build Team" data-i18n-tr="Ekibi Taniyin">Meet the Build Team</span> <i class="ri-team-line"></i>
                </a>
              </div>
            </div>

            <div class="about__images">
              <img
                src="../files/media/images/aboutimage2.png"
                alt="about us image"
                class="about__img"
                loading="lazy"
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
          <a href="main.php" class="footer__logo">
            <img src="../files/media/images/logo.png" alt="logo img" />Famagusta Dart Club
          </a>
          <p class="footer__description">
            Follow tournament progress, club updates, and membership activity from the main public pages.
          </p>
          <p class="footer__description">
            Use the Join the Club page for membership paperwork, or contact the club directly through Facebook or phone.
          </p>
        </div>

        <div class="footer__content">
          <div>
            <h3 class="footer__title">EXPLORE</h3>

            <ul class="footer__links">
              <li>
                <a href="tournaments.html" class="footer__link">Tournament Hub</a>
              </li>
              <li>
                <a href="gallery.html" class="footer__link">Gallery</a>
              </li>
              <li>
                <a href="blog.html" class="footer__link">Blog</a>
              </li>
            </ul>
          </div>

          <div>
            <h3 class="footer__title">CLUB</h3>

            <ul class="footer__links">
              <li>
                <a href="register.html" class="footer__link">Membership</a>
              </li>
              <li>
                <a href="about.html" class="footer__link">About</a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="container">
        <div class="footer__group">
          <ul class="footer__social">
            <a
              href="https://www.facebook.com/GazimagusaDartsBirligi"
              class="footer__social-link"
              target="_blank"
              rel="noreferrer"
            >
              <i class="ri-facebook-circle-fill"></i>
            </a>
            <a href="tel:+905338602325" class="footer__social-link">
              <i class="ri-phone-fill"></i>
            </a>
          </ul>

          <span class="footer__copy">
            &#169; Famagusta Dart Club. All rights reserved.
          </span>
        </div>
      </div>
    </footer>

    <a href="#top" class="scrollup" id="scroll-up">
      <i class="ri-arrow-up-line"></i>
    </a>

    <script src="../js/scrollreveal.min.js"></script>
    <script src="../js/behaviour.js?v=20260413-1"></script>
  </body>
</html>
