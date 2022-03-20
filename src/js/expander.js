// Expander boxes
var expandable = document.querySelectorAll('.expandable > .expander');
expandable.forEach(function (currentValue, index, arr) {
    currentValue.onclick = function (e) {
        e.preventDefault();

        var parent = e.currentTarget.parentNode;
        if (parent.className.match(/(?:^|\s)expanded(?!\S)/)) {
            parent.className = parent.className.replace(/(?:^|\s)expanded(?!\S)/g , '');
        } else {
            parent.className += " expanded";
        }

        e.currentTarget.blur();
    }
});
