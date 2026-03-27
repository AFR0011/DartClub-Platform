window.onload = () => {
  // Make main links clickable
  var cards = document.querySelectorAll("article.event__card");
  for (card of cards) {
    card.addEventListener("click", e => {
        // Get link inside the article and click it
        if (e.target.tagName.toLowerCase() == "article") e.target.lastElementChild.click();
        else {
            e.target.parentElement.lastElementChild.click();
        }
    });
  }
};
