window.readyHandlers = [];
window.ready = function ready(handler) {
  window.readyHandlers.push(handler);
  handleState();
};

window.handleState = function handleState () {
  if (document.readyState === 'interactive' || document.readyState === "complete") {
    while(window.readyHandlers.length > 0) {
      (window.readyHandlers.shift())();
    }
  }
};

document.onreadystatechange = window.handleState;

ready(function () {
  // your code here
  var button = document.getElementById("submit_button");
  button.onclick = function () {
      this.innerHTML = button.getAttribute("data-processing");;
      this.disabled = true;

      var form = document.getElementById("f");
      form.submit();
      return true;
  };
});
