'use strict';

ready(function () {
    // Expander boxes
    var expandable = document.querySelectorAll('.expandable > .expander');
    for (var i = 0; i < expandable.length; i++) {
        expandable[i].onclick = function (e) {
            var parent = e.currentTarget.parentNode;
            if (parent.classList.contains('expanded')) {
                parent.classList.remove('expanded');
            } else {
                parent.classList.add('expanded');
            }
            e.currentTarget.blur();
        };
    }
});
