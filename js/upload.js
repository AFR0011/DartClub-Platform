$(document).ready(function () {
  const $uploadArea = $("#uploadArea");
  const $uploadInput = $("#uploadInput");
  const $uploadFiles = $("#uploadFiles");
  const $uploadButton = $("#uploadButton");
  const $browseButton = $("#browseButton");

  // Function to handle file upload
  function handleFileUpload(files) {
    // Display uploaded files
    $.each(files, function (index, file) {
      const $fileItem = $("<div>", { class: "uploaded_file" }).html(`
          <span>${file.name}</span>
          <button class="button remove_button">Remove</button>
        `);
      $uploadFiles.append($fileItem);

      // Remove file when remove button is clicked
      $fileItem.find(".remove_button").on("click", function () {
        $fileItem.remove();
      });
    });
  }
  
  // Stop propagation for the file input element
  $uploadInput.on("click", function (e) {
    e.stopPropagation();
  });

  // Event listeners for clicking area or browse button
  $browseButton.on("click", function (event) {
    event.preventDefault();
    event.stopPropagation();
    $uploadInput.click();
  });

  $uploadArea.on("click", function (event) {
    event.preventDefault();
    event.stopPropagation();
    $uploadInput.click();
  });

  // Event listener for drag and drop
  $uploadArea.on("dragover", function (event) {
    event.preventDefault();
    $uploadArea.addClass("dragover");
  });

  $uploadArea.on("dragleave", function (event) {
    event.preventDefault();
    $uploadArea.removeClass("dragover");
  });

  $uploadArea.on("drop", function (event) {
    event.preventDefault();
    $uploadArea.removeClass("dragover");
    const droppedFiles = event.originalEvent.dataTransfer.files;
    $uploadInput[0].files = droppedFiles;
    handleFileUpload(droppedFiles);
  });

  // Event listener for file input
  $uploadInput.on("change", function (event) {
    const selectedFiles = Array.from(event.target.files);
    handleFileUpload(selectedFiles);
  });

  // Event listener for upload button
  $uploadButton.on("click", function (e) {
    e.stopPropagation();
    const $uploadedFileElements = $uploadFiles.find(".uploaded_file");
    const uploadedFiles = $uploadedFileElements
      .map(function () {
        return $(this).find("span").text();
      })
      .get();
    // Now you have the list of uploaded files, you can do whatever you need to do with them
    console.log("Uploaded files:", uploadedFiles);
  });
});
