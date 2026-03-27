function openNav() {
    document.getElementById("sidenav").style.width = "250px";
}

function closeNav() {
    document.getElementById("sidenav").style.width = "0";
}

function viewApplication(appPath) {
var iframe = document.getElementById('iframe');
var applicationFrame = document.getElementById('applicationFrame');
iframe.src = appPath;
applicationFrame.style.display = 'block';
}