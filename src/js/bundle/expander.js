document.addEventListener("DOMContentLoaded", function(event) {
    var expandable = document.querySelectorAll('.expandable > .expander');
    for (var i = 0; i < expandable.length; i++) {
        expandable[i].onclick = function (e) {
            var parent = e.currentTarget.parentNode;
            parent.classList.toggle('expanded');
            e.currentTarget.blur();
        };
    }
});
