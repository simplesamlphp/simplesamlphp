document.getElementById('file-input').addEventListener('change', function () {
    var showFile = document.getElementById('show-file');
    var replacement = document.createTextNode(this.files.item(0).name);
    showFile.replaceChild(replacement, showFile.childNodes[0]);
});
