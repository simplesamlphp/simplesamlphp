ready(function () {
    var button = document.getElementById("submit_button");
    var form = document.getElementById("f");

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

    button.onclick = function () {
        var form = document.getElementById("f");
        form.submit();
        return true;
    };
});

