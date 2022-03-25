ready(function () {
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
        if (layout.className.match(/(?:^|\s)active(?!\S)/)) {
            layout.className = layout.className.replace(/(?:^|\s)active(?!\S)/g , '');
        } else {
            layout.className += " active";
        }

        var foot = document.getElementById("foot");
        if (foot.className.match(/(?:^|\s)active(?!\S)/)) {
            foot.className = foot.className.replace(/(?:^|\s)active(?!\S)/g , '');
        } else {
            foot.className += " active";
        }

        if (menuLink.className.match(/(?:^|\s)active(?!\S)/)) {
            menuLink.className = menuLink.className.replace(/(?:^|\s)active(?!\S)/g , '');
        } else {
            menuLink.className += " active";
        }
    };
});
