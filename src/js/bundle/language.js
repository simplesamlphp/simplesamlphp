'use strict';

ready(function () {
    // Language selector
    var languageSelector = document.getElementById("language-selector");
    languageSelector.onchange = function () {
        var languageForm = document.getElementById("language-form");
        languageForm.submit();
        return true;
    };


    // Side menu
    var menuLink = document.getElementById("menuLink");
    menuLink.onclick = function (e) {
        e.preventDefault();

        var layout = document.getElementById("layout");
        if (layout.classList.contains('active')) {
            layout.classList.remove('active');
        } else {
            layout.classList.add('active');
        }

        var foot = document.getElementById("foot");
        if (foot.classList.contains('active')) {
            foot.classList.remove('active');
        } else {
            foot.classList.add('active');
        }

        if (menuLink.classList.contains('active')) {
            menuLink.classList.remove('active');
        } else {
            menuLink.classList.add('active');
        }
    };
});
