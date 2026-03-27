function selectRow(row) {
  // Remove selection from any previously selected row
  var previouslySelected = document.querySelector(".selected");
  if (previouslySelected) {
    previouslySelected.classList.remove("selected");
  }

  // Add 'selected' class to the clicked row
  row.classList.add("selected");

  // Show the registration form
  document.getElementById("registrationForm").style.display = "block";

  // Update the hidden input field with the application id from the first cell of the row
  var appId = row.cells[0].innerText;
  document.getElementById("appId").value = appId;
}

function checkRowSelection() {
  // Check if any row is selected
  var selectedRow = document.querySelector(".selected");
  if (!selectedRow) {
    // Hide the registration form if no row is selected
    document.getElementById("registrationForm").style.display = "none";
  }
}

// Call checkRowSelection on page load to ensure the form is hidden initially
window.onload = checkRowSelection;
