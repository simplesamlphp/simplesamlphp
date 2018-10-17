document.addEventListener(
    'DOMContentLoaded',
    function () {
        var button = document.getElementById("submit_button");
        button.addEventListener(
            'click',
            function () {
                var translation = document.getElementById("processing_trans");
                this.disabled = true;
                this.innerHTML = translation.value;
                return true;
            }
        );
    }
);
