document.addEventListener("DOMContentLoaded", function(event) {
    var button = document.getElementById("submit_button");
    button.onclick = function () {
        this.innerHTML = button.getAttribute("data-processing");
        this.disabled = true;

        var form = document.getElementById("f");
        form.submit();
        return true;
    };

    button.focus();

    var username = document.getElementById("username");
    username.focus();
});

