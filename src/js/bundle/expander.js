ready(function () {
    // Expander boxes
    var expandable = document.querySelectorAll('.expandable > .expander');
    for (var i = 0; i < expandable.length; i++) {
        expandable[i].currentValue.onclick = function (e) {
            var parent = e.currentTarget.parentNode;
            if (parent.className.match(/(?:^|\s)expanded(?!\S)/)) {
                parent.className = parent.className.replace(/(?:^|\s)expanded(?!\S)/g , '');
            } else {
                parent.className += " expanded";
            }
            e.currentTarget.blur();
        };
    }
});
