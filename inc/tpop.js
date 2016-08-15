String.prototype.trim = function(){return this.replace('\xa0',' ').replace(/^\s+|\s+$/g, '')};
_p = null;
document.onmousedown = function() {
  if (_p) {
    _p.style.display = 'none';
    _c.style.display = 'none';
    _p = null;
  }
}
function $(id) {
  return document.getElementById(id);
}
function value(id) {
  return $(id).value.trim();
}
function sub() {
  if (validate()) {
    _p.style.display = 'none';
    _wk.style.visibility = 'visible';
    _f.submit();
  }
}
function pop() {
  _f = $('form');
  _c = $('curtain');
  _p = $('trial-pop');
  _w = $('warn');
  _ws = _w.style;
  _wk = $('working-float');
  _c.style.display = 'block';
  resizeCurtain();
  _p.onmousedown = function(){event.cancelBubble=true};
  _p.style.display = 'block';
  center(_p);
  center(_wk);
  focus('uname');
}
function closePop2() {
  _c = $('curtain');
  _p = $('trial-pop');
  _c.style.display = 'none';
  _p.style.display = 'none';
}
function center(e) {
  var top = document.documentElement.clientHeight / 2 - e.clientHeight / 2;
  if (top < 0) top = 0;
  var left = document.documentElement.clientWidth / 2 - e.clientWidth / 2;
  if (left < 0) left = 0;
  var width = e.style.width;
  e.style.width = e.clientWidth;
  e.style.top = (top + document.documentElement.scrollTop) + 'px';
  e.style.left = (left + document.documentElement.scrollLeft) + 'px';
}
function resizeCurtain() {
  var h = document.body.offsetHeight;
  var w = document.body.offsetWidth;
  if (document.documentElement.clientHeight > h) 
    h = document.documentElement.clientHeight;
  if (document.documentElement.clientWidth > w) 
    w = document.documentElement.clientWidth;
  _c.style.height = h;
  _c.style.width = w;
}
function validate() {
  _ws.visibility = 'hidden';
  setStyle('uname', value('uname') == '');
  setStyle('email', ! isEmail(value('email')));
  return _ws.visibility == 'hidden'; 
}
function setStyle(id, err) {
  $(id).parentElement.className = (err) ? 'err' : 'ok';
  if (err && _ws.visibility == 'hidden') {
    _ws.visibility = 'visible';
    focus(id);
  }
}
function isEmail(c) {
  return /^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_.-])+\.([a-zA-Z])+([a-zA-Z])+/.test(c);
}
function focus(id) {
  var e = $(id);
  setTimeout(function(){try{e.focus();e.select()}catch(ex){}},10);
}
