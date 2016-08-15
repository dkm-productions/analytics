var p;  // pop window reference
var pcap;  // pop window's caption, must have pop's id + "-cap"
var mx;  // mouse x at drag start
var my;  // mouse y at drag start
var px;  // pop x at drag start
var py;  // pop y at drag start
var isdrag = false;  // true while dragging
var c;  // curtain
var cshow = false;  // curtain showing (boolean)
var cfn;  // callback function for confirm/calendar pop
var cpids = [];  // overlayed pop IDs
var cfid;  // ID of field for calendar to update
var workingStay;  // if true, require closeWorking (closePop won't do it)
var ze;  // zoom event
var plpos;  // last pos
var bffn = null;  // body focus function
var CONFIRM_CANCEL = null;

// TODO add dirty functionality

function registerBodyFocus(fn) {  // fn to call when last popup closed (i.e. body "receives" focus) 
  bffn = fn;
}
function showWorking(msg, noCurtain, stay) { // stay = true, require closeWorking (closePop won't hide this)
  $("pop-workingbar").innerHTML = msg ? msg : "Working";
  workingStay = (stay != null);
  if (workingStay) {
    $("pop-working-control").style.visibility = "hidden";
  } else {
    $("pop-working-control").style.visibility = "";
  }
  if (noCurtain) {
    showCurtainlessPop("pop-working");
  } else {
    showPop("pop-working");
  }
}
function showOverlayWorking(msg, noCurtain, stay) {
  $("pop-workingbar").innerText = msg ? msg : "Working";  
  showOverlayPop("pop-working");
}
function closeOverlayWorking() {
  document.body.style.cursor = "";
  workingStay = false;
  closeOverlayPop();
}
function closeWorking() {
  document.body.style.cursor = "";
  workingStay = false;
  closePop();
}
function getWorkingText() {
  return $("pop-workingbar").innerText;
}
function setWorkingText(html) {
  $("pop-workingbar").innerHTML = html;
}
function doOverlayWork(f, msg) {
  showOverlayWorking(msg); 
  setTimeout(f, 10);
}
function showConfirmDirtyExit(callbackFunction, noun) {
  noun = denull(noun, "record");
  showConfirm("This " + noun + " has changed. Do you want to save changes before exiting?", callbackFunction, null, null, "Save and Exit", "Don't Save, Just Exit");
}
/*
 * Simple form... callsback only if 'yes'
 */
function confirm(msg, callback) {
  setCfnObject(callback, null, null, true);
  $("pop-confirm").style.width = "";
  $("pop-confirm").style.cursor = "default";
  $("pop-confirm-no").style.display = "";
  $("pop-confirm-cancel").style.display = "none";
  setHtml("pop-confirm-yes", "&nbsp;&nbsp;&nbsp;Yes&nbsp;&nbsp;&nbsp;");
  setHtml("pop-confirm-no", "&nbsp;&nbsp;&nbsp;No&nbsp;&nbsp;&nbsp;");
  setHtml("pop-confirm-cancel", "Cancel");
  $("pop-confirm-control").style.visibility = "";
  $("pop-confirm-content").className = "pop-content question";
  setText("pop-confirm-cap-text", "Clicktate");
  setHtml("pop-confirm-text", msg);
  showOverlayPop("pop-confirm");
  $("pop-confirm-yes").focus();
}
/*
 * verb: optional, default "delete"
 * noun: optional, default "record"
 * callbackArg: optional
 */
function showConfirmDelete(callbackFunction, verb, noun, callbackArg) {
  confirmCallbackArg = callbackArg;
  verb = (verb) ? verb : "delete";
  noun = (noun) ? noun : "record";
  showConfirm("Are you sure you want to " + verb + " this " + noun + "?", callbackFunction, null, null, null, null, null, null, null, null, null, callbackArg);
}
function showConfirmChecked(verb, callback) {
  showConfirmDeleteChecked(callback, verb);
}
function showConfirmDeleteChecked(callbackFunction, verb) {
  verb = (verb) ? verb : "delete";
  showConfirm("Are you sure you want to " + verb + " the checked selection(s)?", callbackFunction);
}
/*
 * callbackArg: optional, to pass back to callbackFn (e.g. ID of record to delete)
 */
function showConfirm(msg, callbackFunction, noCancel, caption, yesCaption, noCaption, cancelCaption, stay, important, okOnly, noTimeout, callbackArg) {
  setCfnObject(callbackFunction, noTimeout, callbackArg);
  $("pop-confirm").style.width = "";
  $("pop-confirm").style.cursor = "default";
  if (okOnly) {
    setHtml("pop-confirm-yes", "&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;");
    $("pop-confirm-no").style.display = "none";
    $("pop-confirm-cancel").style.display = "none";
  } else {
    $("pop-confirm-no").style.display = "";
    $("pop-confirm-cancel").style.display = (noCancel) ? "none" : "";
    setHtml("pop-confirm-yes", yesCaption ? yesCaption : "&nbsp;&nbsp;&nbsp;Yes&nbsp;&nbsp;&nbsp;");
    setHtml("pop-confirm-no", noCaption ? noCaption : "&nbsp;&nbsp;&nbsp;No&nbsp;&nbsp;&nbsp;");
    setHtml("pop-confirm-cancel", cancelCaption ? cancelCaption : "Cancel");
  }
  $("pop-confirm-control").style.visibility = (stay) ? "hidden" : "";
  $("pop-confirm-content").className = (important) ? "pop-content important" : "pop-content question";
  setText("pop-confirm-cap-text", caption ? caption : "Clicktate");
  setHtml("pop-confirm-text", msg);
  showOverlayPop("pop-confirm");
  $("pop-confirm-yes").focus();
}
function setCfnObject(callbackFunction, noTimeout, callbackArg, yesOnly) {  // used for popConfirm
  cfn = {fn:callbackFunction, noTimeout:noTimeout, arg:callbackArg, yesOnly:yesOnly};
}
function setCaption(id, text) {
  if (isBlank(text)) {
    setText(id, "Clicktate");
  } else {
    setText(id, "Clicktate - " + text);
  }
}
function closeConfirm(confirmed) {
  closeOverlayPop(true);
  if (cfn) {
    if (cfn.noTimeout) {
      execCfn(confirmed);    
    } else {
      setTimeout("execCfn(" + confirmed + ")", 50);
    }
  }
}
function execCfn(confirmed) {
  if (cfn) {
    var f = cfn.fn;
    var arg = cfn.arg;
    var yesOnly = cfn.yesOnly;
    cfn = null;
    if (yesOnly) {
      if (confirmed) f();
    } else {
      f(confirmed, arg);
    }
  }
}
function showMsg(content) {
  $("pop-msg").style.width = "";
  $("pop-msg-text").innerHTML = content;
  $("pop-msg-content").className = "pop-content information";
  focus("pop-msg-ok");
  showOverlayPop("pop-msg");
}
function showCritical(content, callbackFunction) {
  if (callbackFunction) {
    setCfnObject(callbackFunction);
  }
  $("pop-msg").style.width = "";
  $("pop-msg-text").innerHTML = content;
  $("pop-msg-content").className = "pop-content critical";
  showOverlayPop("pop-msg");
}
function closeMsg() {
  closeOverlayPop();
}
function showErrorMsg(content, callbackFunction, critical) {
  if (callbackFunction) {
    setCfnObject(callbackFunction);
  }
  $("pop-msg").style.width = "";
  $("pop-msg-text").innerHTML = content;
  $("pop-msg-content").className = (critical) ? "pop-content critical" : "pop-content information";
  focus("pop-msg-ok");
  showOverlayPop("pop-msg");
}
function showPrompt(msg, callbackFunction) {
  cfn = callbackFunction;
  $("pop-prompt").style.width = "";
  $("pop-prompt-text").innerHTML = msg;
  setValue("pop-prompt-input", "");
  showOverlayPop("pop-prompt");
  focus("pop-prompt-input");
}
function closePrompt(okayed) {
  var text = null;
  if (okayed) {
    text = value("pop-prompt-input");
  }
  closeOverlayPop();
  var f = cfn;
  cfn = null;
  f(text);
}
// fieldId, id of textbox to update (optional)
function showCalendar(date, fieldId, callbackFunction) {
  cfid = fieldId;
  cfn = callbackFunction;
  setCalendar(date);
  showOverlayPop("pop-cal", null, true);
}
// Simple version
function showCal(fieldId) {
  showCalendar($(fieldId).value, fieldId, null);
}
function closeCalendar() {
  closeOverlayPop();
}
function showOverlayPop(id, focusId, mousePos, noCurtain, useLastPos) {
  if (p) {
    cpids.push(p.id);
  }
  return showPop(id, focusId, true, null, noCurtain, mousePos, useLastPos);
}
function setZ() {
  var z = 255 + cpids.length * 2;
  if (c) c.style.zIndex = z;
  p.style.zIndex = z + 1;
}
function closeOverlayPop(force) {
  closePop(force);
  if (cpids.length > 0) {
    var cpid = cpids.pop();
    showPop(cpid, null, null, true);
  }
}
function zoomPop(popId, focusId, callbackFunction, noCurtain) {  
  if (p) {
    closePop();
  }
  p = $(popId);
  hideCombos();
  ze = {
      inc:0.15, 
      divId:popId,
      showf:buildFn("showPop", [popId, focusId, null, null, true, null, true]),
      cf:callbackFunction};
  ze.steps = 1 / ze.inc;
  ze.iter = 0;
  c = $("curtain");
  if (! noCurtain) {
    resizeToScreen(c);
    c.style.display = "block";
  }
  p.style.visibility = "hidden";
  p.style.display = "block";
  centerPop();
  var ft = valNoPx(p.style.top);
  var fl = valNoPx(p.style.left);
  p.style.zoom = ze.inc;
  mousePosPop(ze.inc);
  ze.t = valNoPx(p.style.top);
  ze.l = valNoPx(p.style.left);
  ze.offT = (ft - ze.t);
  ze.offL = (fl - ze.l);
  p.style.visibility = "";
  hidePageScroll();
  ze.intId = setInterval("zoomLoop()", 1);
}
function valNoPx(s) {
  return valOrZero(s.replace("px", ""));
}
function zoomLoop() {
  ze.iter++;
  var zoom = ze.inc * ze.iter;
  if (zoom > 1) zoom = 1;
  p.style.top = ze.t + ze.offT * zoom;
  p.style.left = ze.l + ze.offL * zoom;
  p.style.zoom = zoom;
  if (ze.iter < ze.steps) {
  } else {
    p.style.zoom = "";
    clearInterval(ze.intId);
    p = null;
    plpos = null;
    setTimeout(ze.showf, 10);
    setTimeout("zoomCleanup()", 15);
  }
}
function zoomCleanup() {
  if (ze.cf) {
    var f = ze.cf;
    ze.cf = null;
    f();
  }
  ze = null;
}
function popCancelBubble() {
  event.cancelBubble = true;
}
function showPop(popId, focusId, noClose, noCenter, noCurtain, mousePos, useLastPos) {
  if (p && noClose == null) {
    closePop();
    cpids = [];
  }
  p = $(popId);
  p.onmousedown = popCancelBubble;
  //pcap = $(p.id + "-cap");
  pcap = p.firstChild;
  pcap.onmousedown = startDrag;
  if (! cshow && noCurtain == null && cpids.length == 0) {
    c = $("curtain");
    c.style.display = "block";
    resizeToScreen(c);
    cshow = true;
  }
  p.style.display = "block";
  setZ();
  hideCombos();
  restoreCombos(p);
  if (useLastPos) {
    if (plpos) {
      p.style.left = plpos.left;
      p.style.top = plpos.top;
    }
  } else if (! noCenter) {
    if (mousePos) {
      mousePosPop();
    } else {
      centerPop();
    }
    plpos = {left:p.style.left, top:p.style.top};
  }
  if (focusId) {
    focus(focusId);
  }
  if (cpids.length == 0) {
    hidePageScroll();
  }
  return p;
}
function resizeToScreen(div) {
  var h = document.body.offsetHeight;
  if (document.documentElement.clientHeight > h) h = document.documentElement.clientHeight;
  div.style.height = h;
  var w = document.body.offsetWidth;
  if (document.documentElement.clientWidth > w) w = document.documentElement.clientWidth;
  div.style.width = w;
  return div;
}
function showCurtainlessPop(popId, mousePos) {
  return showPop(popId, null, null, null, true, mousePos);
}
function centerPop(z) {
  if (! z) z = 1;
  p.clientHeight;  // just referencing clientHeight keeps document scrollbars from appearing when pop contains margin stylings!
  var top = document.documentElement.clientHeight / 2 - (p.clientHeight * z) / 2;
  if (top < 0) top = 0;
  var left = document.documentElement.clientWidth / 2 - (p.clientWidth * z) / 2;
  if (left < 0) left = 0;
  var width = p.style.width;
  p.style.width = p.clientWidth;
  p.style.top = top + document.documentElement.scrollTop;
  p.style.left = left + document.documentElement.scrollLeft;
  if (width == "") p.style.width = "";
}
function mousePosPop(z) {  // position pop under mouse
  if (! z) z = 1;
  p.clientHeight;  // just referencing clientHeight keeps document scrollbars from appearing when pop contains margin stylings!
  if (! event) {
    centerPop(z);
    return;
  }
  //alert(event.clientY + "," + document.documentElement.scrollTop + "," + document.body.offsetHeight + "," + document.documentElement.clientHeight);
  //var ac = {x:event.clientX - p.clientWidth * z / 2, y:event.clientY - 20}; // + document.documentElement.scrollTop};
  var ac = {x:event.clientX - 50, y:event.clientY - 30}; // + document.documentElement.scrollTop};
  var cw = document.body.offsetWidth;
  //var ch = document.body.offsetHeight;
  var ch = document.documentElement.clientHeight;
  if ((ac.x + p.clientWidth * z) > cw) {
    ac.x = cw - p.clientWidth * z - 30;
  }
  if ((ac.y + p.clientHeight * z) > ch) {
    ac.y = ch - p.clientHeight * z - 5;
  }
  if (ac.x < 0) ac.x = 0;
  if (ac.y < 0) ac.y = 0;
  p.style.top = ac.y + document.documentElement.scrollTop;
  p.style.left = ac.x;
}
function isPopUp(id) {
  if (id) {
    return (p != null && p.id == id);
  }
  return (p != null);
}
function closePopByControlBox() {  // calls the shown pop's "X" anchor
  if (p == null) return;
  if (pcap == null || pcap.children.length == 0) {
    closePop();
    return;
  }
  var a = pcap.lastChild;
  if (a.className != "pop-close") {
    closePop();
    return;
  }
  a.click(); 
}
function closePop(force) {
  if (! force && workingStay) return;
  if (p == null) return;
  p.style.display = "none";
  p = null;
  if (cpids.length == 0) {
    if (c) c.style.display = "none";
    cshow = false;
    showPageScroll();
    if (bffn) {
      bffn();
    }
  }
  restoreCombos(document);
  overlayWorking(false);
}
function startDrag() {
  if (event.srcElement.className == "pop-close") {
    return;
  }
  isdrag = true;
  mx = event.clientX;
  my = event.clientY;
  px = p.offsetLeft;
  py = p.offsetTop;
  document.onmousemove = drag;
  document.onmouseup = endDrag;
  return false;
}
function drag() {
  if (isdrag) {
    p.style.left = px + event.clientX - mx;
    p.style.top = py + event.clientY - my;
    return false;
  }
}
function endDrag() {
  isdrag = false;
  document.onmousemove = "";
  document.onmouseup = "";
}

// Tabbar
function showTab(popId, ix) {
  var p = $(popId);
  var tabbar = $$$$("tabbar", p, "DIV")[0];
  var h2s = tabbar.getElementsByTagName("h2");
  var as = tabbar.getElementsByTagName("a");
  var panels = $$$$("tabpanel", p, "DIV");
  for (var i = 0; i < h2s.length; i++) {
    h2s[i].className = (i == ix) ? "checked" : "";
    as[i].className = (i == ix) ? "checked" : "";
    panels[i].style.display = (i == ix) ? "" : "none";
  }
}

// Calendar
var mToday;
var mSetting;
var mMonth;
var mYear;
var mNow;
var mcboMonth; 
var mcboYear;
var START_YEAR = 1910;
var BASE = 10;

// replaced by DateUi.validate
function formatDate(text) {  // validates and returns as dd-mmm-yyyy (or null if invalid)  
  var a;
  if (! isString(text)) {
    return;
  }
  if (text.indexOf("-") >= 0) {
    a = text.split("-");
  } else {
    a = text.split("/");
  }
  if (a.length != 3) {
    return;
  }
  if (a[1].length == 3) {  // dd-mmm-yyyy
    mYear = parseInt(a[2], BASE);
    mMonth = monthIndex(a[1]);
    day = parseInt(a[0], BASE);
  } else if (a[0].length == 4) {  // yyyy-mm-dd
    mYear = parseInt(a[0], BASE);
    mMonth = parseInt(a[1], BASE) - 1;
    day = parseInt(a[2], BASE);
  } else {  // mm-dd-yy or mm-dd-yyyy
    mYear = parseInt(a[2], BASE);
    mMonth = parseInt(a[0], BASE) - 1;
    day = parseInt(a[1], BASE);
    if (mYear < 100) {
      if (mYear > 20) {
        mYear += 1900;
      } else {
        mYear += 2000;
      }
    }
  }
  mSetting = new Date(mYear, mMonth, day);
  if (mSetting.toString() == "NaN") {
    mSetting = null;
    return;
  }
  return lpad(day) + "-" + monthShortName(mMonth) + "-" + mYear;
}

function setCalendar(date) {
  mSetting = null;
  mcboMonth = $("cboMonth");
  mcboYear = $("cboYear");
  dateSetting = date;
  mNow = new Date();
  mToday = new Date(mNow.getFullYear(), mNow.getMonth(), mNow.getDate());
  if (dateSetting) {
    if (dateSetting.substr(2, 1) == "-") {
      mYear = parseInt(dateSetting.substr(7, 4), BASE);  // parse from dd-mmm-yyyy
      mMonth = monthIndex(dateSetting.substr(3, 3));
      day = parseInt(dateSetting.substr(0, 2), BASE);
    } else {
      mYear = parseInt(dateSetting.substr(0, 4), BASE);  // parse from yyyy-mm-dd
      mMonth = parseInt(dateSetting.substr(5, 2), BASE) - 1;
      day = parseInt(dateSetting.substr(8, 2), BASE);
    }
    mSetting = new Date(mYear, mMonth, day);
    if (mSetting.toString() == "NaN") {
      mSetting = null;
    }
  }
  if (mSetting == null) {
    mYear = mNow.getFullYear();
    mMonth = mNow.getMonth();
  }
  loadCombos();
  formatCalendar();
}
function setToday() {
  mYear = mNow.getFullYear();
  mMonth = mNow.getMonth();
  formatCalendar();
}
function calToday() {  // dd-mmm-yyyy
  var n = new Date();
  var day = "0" + n.getDate();
  return day.substring(day.length - 2) + "-" + monthShortName(n.getMonth()) + "-" + n.getFullYear();
}
function calToday2() { // mm/dd/yyyy
  var n = new Date();
  var day = "0" + n.getDate();
  var m = "0" + (n.getMonth() + 1);
  return m.substring(m.length - 2) + "/" + day.substring(day.length - 2) + "/" + n.getFullYear();
}
function onClick() {
  td = window.event.srcElement;
  if (td.tagName == "TD") {
    day = parseInt(td.innerText, BASE);
    if (day > 0) {
      month = mMonth + 1;
      var returnMonth = (month < 10) ? "0" + month : String(month);
      var returnDay = (day < 10) ? "0" + day : String(day);
      // var returnValue = mYear + "-" + returnMonth + "-" + returnDay;
      var returnValue = returnDay + "-" + monthShortName(mMonth) + "-" + mYear;
      closeOverlayPop();
      if (cfid) { 
        $(cfid).value = returnValue;
      }
      if (cfn) {
        var f = cfn;
        cfn = null;
        f(returnValue);
      }
    }
  }
}
function onMouseOut() {
  td = window.event.srcElement;
  if (td.tagName == "TD") {
    if (td.className == "calSelected") {
      td.className = td.oldClassName;
    }
  }
}
function onMouseOver() {
  td = window.event.srcElement;
  if (td.tagName == "TD") {
    if (parseInt(td.innerText, BASE) > 0) {
      td.oldClassName = td.className;
      td.className = "calSelected";
    }
  }
}
function setDateByCombos() {
  mMonth = mcboMonth.selectedIndex;
  mYear = parseInt(mcboYear.value, BASE);
  formatCalendar();
}
function loadCombos() {
  if (mcboMonth.options.length == 0) {
    for (var month = 0; month <= 11; month++) {
      mcboMonth.add(createOption(monthName(month), month));
    }
  }
  if (mcboYear.options.length == 0) {
    for (var year = START_YEAR; year <= mNow.getFullYear() + 10; year++) {
      mcboYear.add(createOption(year, year));
    }
  }
}
function createOption(text, value) {
  opt = document.createElement("OPTION");
  opt.text = text;
  opt.value = value;
  return opt;
}
function formatCalendar() {
  var oneDay = 86400000;
  tdTitle.innerText = monthName(mMonth) + " " + mYear;  // title
  mcboMonth.selectedIndex = mMonth; 
  mcboYear.selectedIndex = mYear - START_YEAR;
  dFirstOfMonth = new Date(mYear, mMonth, 1);  // first Sunday to display
  dCounter = new Date(dFirstOfMonth.valueOf() - dFirstOfMonth.getDay() * oneDay);
  var todayShown = false;
  var row7class = "calHide";
  for (row = 2; row <= 7; row++) {
    for (cell = 0; cell <= 6; cell++) {
      if (dCounter.getMonth() == mMonth) {
        tblCalendar.rows(row).cells(cell).innerText = dCounter.getDate();
        if ((mSetting != null) && (dCounter.getMonth() == mSetting.getMonth()) && (dCounter.getFullYear() == mSetting.getFullYear()) && (dCounter.getDate() == mSetting.getDate())) {
          tblCalendar.rows(row).cells(cell).className = "calSetting";
        } else {
          if (dCounter.valueOf() == mToday.valueOf()) {
            tblCalendar.rows(row).cells(cell).className = "calToday";
          } else {
            if ((cell == 0) | (cell == 6)) {
              tblCalendar.rows(row).cells(cell).className = "calWeekend";
            } else {
              tblCalendar.rows(row).cells(cell).className = "calDay";
            }
          }
        }
        if (row == 7) {
          row7class = "calOffDay";
        }
        if (dCounter.valueOf() == mToday.valueOf()) {
          todayShown = true;
        }
      } else {
        tblCalendar.rows(row).cells(cell).innerHTML = "&nbsp;";
        tblCalendar.rows(row).cells(cell).className = (row == 7) ? row7class : "calOffDay";
      }
      dCounter.setDate(dCounter.getDate() + 1);
    }
  }
  $("calToday").style.visibility = (todayShown) ? "hidden" : "";
}
function prevMonth() {
  if (mMonth == 0) {
    mMonth = 11;
    mYear = mYear - 1;
  } else {
    mMonth = mMonth - 1;
  }
  formatCalendar();
}
function hideExtraRow() {
  for (cell = 0; cell <=6; cell++) {
    trExtraRow.cells[cell].className = "calHide";
    trExtraRow.cells[cell].innerHTML = "&nbsp;";
  }
}
function nextMonth() {
  if (mMonth == 11) {
    mMonth = 0;
    mYear = mYear + 1;
  } else {
    mMonth = mMonth + 1;
  }
  formatCalendar();
}
var monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
var monthsByAbbr = {"Jan":0,"Feb":1,"Mar":2,"Apr":3,"May":4,"Jun":5,"Jul":6,"Aug":7,"Sep":8,"Oct":9,"Nov":10,"Dec":11};
function monthName(index) {
  return monthNames[index];
}
function monthShortName(index) {
  return monthNames[index].substring(0, 3);
}
function monthIndex(abbr) {
  return monthsByAbbr[abbr];
}

// Clock
var clkField;
function showClock(fieldId, noMin) {
  clkField = $(fieldId);
  showOverlayPop("pop-clock", null, true);
  setClock(clkField.value);
  if (noMin) {
    setValue("clkMin", 0);
    hide("clkMin");
    hide("clkMin");
  } else {
    show("clkMin");
  }
}
function setClock(value) {  // expects "08:30 AM"
  var a = value.split(/:| /);
  var h = 8;  // index
  var m = "00";  // value
  if (a.length >= 1) {
    var i = parseInt(a[0], 10);
    h = (i >= 1 && i <= 11) ? i : 0;
  }
  if (a.length >= 2) {
    m = a[1];
  }
  if (a.length == 3 && a[2] == "PM") {
    h = h + 12;
  }
  // Adjust for pop-clock shift
  h = h - 6;
  if (h < 0) 
    h += 12;
  $("clkHour").options[h].selected = true;
  setValue("clkMin", m);
}
function saveClock() {
  var h = value("clkHour");
  clkField.value = h.substr(0, 2) + ":" + value("clkMin") + " " + h.substr(2, 2);
  closeOverlayPop();
}
