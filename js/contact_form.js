const contactForm = document.getElementById("contact-form"),
  contactMessage = document.getElementById("contact-message"),
  contactUser = document.getElementById("contact-user");

const sendEmail = (e) => {
  e.preventDefault();

  if (contactUser.value === "") {
    contactMessage.classList.remove("color-green");
    contactMessage.classList.add("color-red");

    contactMessage.textContent = "You must enter your email 👆";
    setTimeout(() => {
      contactMessage.textContent = "";
    }, 4000);
  } else {
    // Parameters passed: service id(for Gmail), template id , form No , public key
    emailjs
      .sendForm(
        "service_1i330zn",
        "template_6kxxxmf",
        "#contact-form",
        "Dzqja-Lc3erScWnmb"
      )
      .then(() => {
        contactMessage.classList.add("color-green");
        contactMessage.textContent = "You successfully registered 🎯";
      });
  }
};

contactForm.addEventListener("submit", sendEmail);