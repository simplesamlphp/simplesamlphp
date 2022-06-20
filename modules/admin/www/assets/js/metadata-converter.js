document.getElementById('file-input').addEventListener('change', function () {
    document.getElementById('show-file').innerHTML = this.files.item(0).name;
});
