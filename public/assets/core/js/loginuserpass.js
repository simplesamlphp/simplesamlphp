ready(function () {
    window.onpageshow  = function () {
        var button = document.getElementById("submit_button");
        var replacement = document.createTextNode(button.getAttribute("data-default"));
        button.replaceChild(replacement, button.childNodes[0]);
        button.disabled = false;
    };

    var form = document.getElementById("f");
    form.onsubmit = function () {
        var button = document.getElementById("submit_button");
        var replacement = document.createTextNode(button.getAttribute("data-processing"));
        button.replaceChild(replacement, button.childNodes[0]);
        button.disabled = true;
    };
});

