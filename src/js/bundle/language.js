document.addEventListener("DOMContentLoaded", function(event) {
    // Language selector
    var languageSelector = document.getElementById("language-selector");
    languageSelector.onchange = function() {
        var languageForm = document.getElementById("language-form");
        languageForm.submit();
        return true;
    };


    // Side menu
    var menuLink = document.getElementById("menuLink");
    menuLink.onclick = function(e) {
        e.preventDefault();

        var layout = document.getElementById("layout");
        layout.classList.toggle('active');

        var foot = document.getElementById("foot");
        foot.classList.toggle('active');

        menuLink.classList.toggle('active');
    };
});
