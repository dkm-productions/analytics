function fix_bounce(cls) {
  var cont;
  var startY;
  var idOfContent = "";
  var isContent = function(elem) {
    var id = elem.getAttribute("id");
    while (id !== idOfContent && elem.nodeName.toLowerCase() !== "body") {
      elem = elem.parentNode;
      id = elem.getAttribute("id");
    }
    return (id === idOfContent);
  }
  var touchstart = function(evt) {
    if (!isContent(evt.target)) {
      evt.preventDefault();
      return false;
    }
    startY = (evt.touches) ? evt.touches[0].screenY : evt.screenY;
  }
  var touchmove = function(evt) {
    var elem = evt.target;
    var y = (evt.touches) ? evt.touches[0].screenY : evt.screenY;
    if (cont.scrollTop === 0 && startY <= y) {
      evt.preventDefault();
    }
    if (cont.scrollHeight - cont.offsetHeight === cont.scrollTop && startY >= y) {
      evt.preventDefault();
    }
  }
  window.addEventListener("touchstart", touchstart, false);
  window.addEventListener("touchmove", touchmove, false);
}