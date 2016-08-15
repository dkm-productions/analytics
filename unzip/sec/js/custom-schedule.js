var colorTrigger;
var colorChange;
function showCustomProfile() {
  setValue("csp-dow", lu_profile.dowStart);
  setValue("csp-week", lu_profile.weekLength);
  setValue("csp-start", milToStandard(lu_profile.slotStart));
  setValue("csp-end", milToStandard(lu_profile.slotEnd));
  setValue("csp-size", lu_profile.slotSize);
  setValue("csp-label", lu_profile.labelFormat);
  Pop.show("pop-csp");
}
function cspSave() {
  var start = military(value("csp-start"));
  var end = military(value("csp-end"));
  if (start == null) {
    Pop.Msg.showCritical("Cannot save; time slot \"from\" is invalid.");
    return;
  }
  if (end == null) {
    Pop.Msg.showCritical("Cannot save; time slot \"to\" is invalid.");
    return;
  }
  lu_profile.slotSize = value("csp-size");
  lu_profile.slotStart = start;
  lu_profile.slotEnd = end;
  lu_profile.dowStart = value("csp-dow");
  lu_profile.weekLength = value("csp-week");
  lu_profile.labelFormat = value("csp-label");
  postLookupSave("saveSchedProfile", lu_profile, uid);
  Pop.Working.show("Saving");
}
function schedProfileCallback() {
  reloadWindow();
}
function cspReset() {
  Pop.Confirm.showYesNoCancel("This will reset to the default settings. Are you sure?", cspResetConfirmed, true);
}
function cspResetConfirmed(confirmed) {
  if (confirmed) {
    sendRequest(3, "action=deleteSchedProfile&id=" + uid);
    Pop.Working.show();
  }
}
function closeCspPop() {
  Pop.close();
  if (colorChange) reloadWindow();
}

function showCustomApptTypes() {
  buildPopCat();
  Pop.show("pop-cat");
}
function buildPopCat() {
  var ul = clearChildren($("pop-cat-ul"));
  for (var id in lu_types) {
    var type = lu_types[id];
    var ckid = "pop-cat-ck" + id;
    var h = "<input id='" + ckid + "' tid='" + id + "' type='checkbox' /><label class='nopad'>Active?</label><input type='text' id='pop-cat-n" + id + "' size='40' value=\"" + type.name + "\" /><label class='nopad'>&nbsp;&nbsp;</label><input type='text' id='pop-cat-h" + id + "' size='1' value='" + type.h + "' /> <label class='nopad'>hours</label> <input type='text' id='pop-cat-m" + id + "' size='1' value='" + type.m + "' /> <label class='nopad'>minutes</label>";
    var li = addListItem(ul, null, h, "pop-cat-li-" + id, "space");
    li.tid = id;
    catToggleActive(setCheck(ckid, type.active), true);
  }  
}
function catUlClick() {
  var e = event.srcElement;
  if (e && e.getAttribute('tid')) {
    catToggleActive(e);
  }
}
function catToggleActive(e, nofocus) {
  setDisabledInput("pop-cat-n" + e.getAttribute('tid'), ! e.checked);
  setDisabledInput("pop-cat-h" + e.getAttribute('tid'), ! e.checked);
  setDisabledInput("pop-cat-m" + e.getAttribute('tid'), ! e.checked);
  if (! nofocus && e.checked) {
    focus("pop-cat-n" + e.tid);
  }
}
function catSave() {
  lu_types = buildLuTypes();
  postLookupSave("saveApptTypes", lu_types);
  Pop.close();
  Pop.Working.show("Saving");
}
function saveApptTypesCallback() {
  Pop.Working.close();  
  loadApptTypesCombo();
}
function loadApptTypesCombo() {
  var sel = $("appt-type");
  createOptsFromObjectArray("appt-type", lu_types, "name", sel.value, {"t":"(No Type)","k":""}, "active", 1); 
}
function buildLuTypes() {
  var types = {};
  var lis = $("pop-cat-ul").children;
  for (var i = 0; i < lis.length; i++) {
    var id = lis[i].tid;
    types[id] = buildLuType(id);
  }
  return types;  
}
function buildLuType(id) {
  return {
      "name":value("pop-cat-n" + id),
      "h":hval(value("pop-cat-h" + id)),
      "m":mval(value("pop-cat-m" + id)),
      "active":isChecked("pop-cat-ck" + id)
      };
}
function hval(s) {
  var h = valOrZero(s);
  return (h >= 0 && h <= 24) ? h : 0;
}
function mval(s) {
  var m = valOrZero(s);
  return (m >= 0 && m <= 59) ? m : 0;
}
function catReset() {
  Pop.Confirm.showYesNoCancel("This will reset to the default settings. Are you sure?", catResetConfirmed, true);
}
function catResetConfirmed(confirmed) {
  if (confirmed) {
    sendRequest(3, "action=deleteApptTypes&id=");
    Pop.close();
    Pop.Working.show();
  }
}
function removeApptTypesCallback(o) {
  lu_types = o;
  Pop.Working.close();  
  loadApptTypesCombo();  
}

function showCustomSchedStatus() {
  buildPopCss();
  Pop.show("pop-css");
}
function buildPopCss() {
  var ul = clearChildren($("pop-css-ul"));
  for (var id in lu_status) {
    var stat = lu_status[id];
    var ckid = "pop-css-ck" + id;
    var aid = "pop-css-a" + id;
    var bcolor = defaultColor(stat);
    var h = "<input id='" + ckid + "' sid='" + id + "' type='checkbox' /><label class='nopad'>Active?</label><input type='text' id='pop-css-n" + id + "' size='40' value=\"" + stat.name + "\" /><label class='nopad'>&nbsp;&nbsp;</label><a id='" + aid + "' href='#' onclick='popColor(\"" + aid + "\");return false' class='cmd none' style='background-color:" + bcolor + "'>Set Color</a>";
    var li = addListItem(ul, null, h, "pop-css-li-" + id, "space");
    li.sid = id;
    cssToggleActive(setCheck(ckid, stat.active), true);
  }  
}
function cssUlClick() {
  var e = event.srcElement;
  if (e && e.getAttribute('sid')) {
    cssToggleActive(e);
  }
}
function popColor(id) {
  var a = $(id);
  if (a.className == "cmd none disabled") return;
  colorTrigger = a;
  Pop.show("pop-cc", null, true);
}
function defaultColor(stat) {
  return (stat.bcolor != "") ? stat.bcolor : lu_null_color;
}
function setColor(bcolor) {
  colorTrigger.style.backgroundColor = bcolor;
  colorChange = true;
  Pop.close();
}
function cssToggleActive(e, nofocus) {
  var stat = lu_status[e.getAttribute('sid')];
  var a = $("pop-css-a" + e.getAttribute('sid'));
  if (e.checked) {
    setDisabledInput("pop-css-n" + e.getAttribute('sid'), false);
    a.className = "cmd none";
    a.style.backgroundColor = defaultColor(stat);
  } else {
    setDisabledInput("pop-css-n" + e.getAttribute('sid'), true);
    a.className = "cmd none disabled";
    a.style.backgroundColor = "";
  }    
  if (! nofocus && e.checked) {
    focus("pop-css-n" + e.getAttribute('sid'));
  }
}
function cssSave() {
  lu_status = buildLuStatus();
  postLookupSave("saveSchedStatus", lu_status);
  Pop.close();
  Pop.Working.show("Saving");
}
function saveSchedStatusCallback() {
  Pop.Working.close();
  loadSchedStatusCombo();
}
function loadSchedStatusCombo() {
  var sel = $("appt-status");
  createOptsFromObjectArray("appt-status", lu_status, "name", sel.value, {"t":"(No Status)","k":""}, "active", 1); 
}
function buildLuStatus() {
  var status = {};
  var lis = $("pop-css-ul").children;
  for (var i = 0; i < lis.length; i++) {
    var id = lis[i].sid;
    status[id] = buildLuStat(id);
  }
  return status;  
}
function buildLuStat(id) {
  var active = isChecked("pop-css-ck" + id);
  var bcolor = active ? $("pop-css-a" + id).style.backgroundColor : ""; 
  return {
      "name":value("pop-css-n" + id),
      "bcolor":bcolor,
      "active":active
      };
}
function cssReset() {
  Pop.Confirm.showYesNoCancel("This will reset to the default settings. Are you sure?", cssResetConfirmed, true);
}
function cssResetConfirmed(confirmed) {
  if (confirmed) {
    sendRequest(3, "action=deleteSchedStatus&id=");
    Pop.close();
    Pop.Working.show();
  }
}
function removeSchedStatusCallback(o) {
  lu_status = o;
  Pop.Working.close();  
  loadSchedStatusCombo();
  // todo refresh colors on page  
}
