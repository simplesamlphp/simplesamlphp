ready(function () {
    var button = document.getElementById("submit_button");

    window.onpageshow  = function () {
        var button = document.getElementById("submit_button");
        button.innerHTML = button.getAttribute('data-default');
        button.disabled = false;
    }

    form.onsubmit = function () {
        var button = document.getElementById("submit_button");
        button.innerHTML = button.getAttribute("data-processing");
        button.disabled = true;
    }
});

