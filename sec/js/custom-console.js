var wmap;  // working map
var cmapSuid;  // selected suid
function showCustomTemplateMap() {
  doHourglass("finishShowCustomTemplateMap()", $("ctCustom"));
}
function finishShowCustomTemplateMap() {  
  createWorkingMap();
  loadCmapSections();
  Pop.show("pop-cmap");
  closeHourglass();
}
function loadCmapSections() {
  var sel = $("cmap-sections");
  var section = null;
  clearOpts(sel);
  map.Sections.forEach(function(s) {
    if (s.Pars) {
      var opt = document.createElement("option");
      sel.options.add(opt);
      opt.text = s.name;
      opt.value = s.uid;
      if (s.uid == selSuid) 
        opt.selected = true;
    }
  })
  cmapSectionChange();
}
function cmapSectionChange() {
  cmapSuid = value("cmap-sections");
  setCheck("cmap-start", wmap._startSection == cmapSuid);
  loadCmapPars(cmapSuid);   
}
function loadCmapPars(suid) {
  var tbody = $("cmap-tbody");
  var pars = map.getPars(suid);
  var wpars = wmap.getPars(suid);
  clearRows(tbody);
  var offset = false;
  for (var i = 1; i >= 0; i--) {
    for (var pid in pars) {
      var wp = wpars[pid];
      if (wp.major == i) {
        var p = pars[pid];
        var tr = createTr(offset ? "offset" : "");
        var d = fixCmapDesc(p.desc || '');
        var c = "middle";
        if (wp.auto)
          c += " ai";
        else if (wp.major)
          c += " mp";
        tr.appendChild(createTdHtml(d, c));
        var h = "<input type='checkbox' name='mp' pid='" + pid + "'" + checkedIf(wp.major) + "/> <span class='" + ((wp.major) ? "mp" : "") + "'>Main?</span>";
        var td = createTdHtml(h, "check");
        tr.appendChild(td);
        h = "<input type='checkbox' name='ai' pid='" + pid + "'" + checkedIf(wp.auto) + "/> <span class='" + ((wp.auto) ? "ai" : "") + "'>Auto-Include?</span>";
        td = createTdHtml(h, "check");
        tr.appendChild(td);
        tbody.appendChild(tr);
        offset = ! offset;
      }
    }
  }
  $("cmap-ss").scrollTop = 0;
}
function cmapTableClick() {
  var e = event.srcElement;
  var pid = e.getAttribute('pid');
  if (e && pid != null) {
    if (e.name == "mp") {
      toggleCmapMajor(cmapSuid, pid);
    } else {
      toggleCmapAuto(cmapSuid, pid);
    }
  }
}
function cmapStartClick() {
  if (isChecked("cmap-start")) {
    wmap._startSection = cmapSuid;
  } else {
    wmap._startSection = null;
  }
}
function toggleCmapMajor(suid, pid) {
  var p = wmap.getPar(suid, pid);
  p.major = ! p.major;
}
function toggleCmapAuto(suid, pid) {
  var p = wmap.getPar(suid, pid);
  p.auto = ! p.auto;
}
function fixCmapDesc(d) {
  return removeHtmlFormatting(d);
}
function createWorkingMap() {
  wmap = WorkingTemplateMap.from(map);
//  wmap = {
//      "startSection":map.startSection,
//      "sections":{}};
//  for (var uid in map.sections) {
//    var s = map.sections[uid];
//    if (! isEmptyMap(s.pars)) {
//      wmap.sections[uid] = {
//          "uid":uid,
//          "pars":{}
//          };
//      for (var pid in s.pars) {
//        var p = s.pars[pid];
//        wmap.sections[s.uid].pars[pid] = {
//            "id":pid,
//            "major":(p.major == 1),
//            "auto":(p.auto == 1)
//            };
//      }
//    }
//  } 
}
function cmapSave() {
  var lumap = wmap.buildLookupMap();
  copyWorkingMapToReal();
  postLookupSave("saveLookupMap", lumap, template.id);
  doWork("finishCmapSave()", "Saving customizations");
}
function finishCmapSave() {
  renderTemplateMap(cmapSuid);
  Pop.closeAll();
}
function copyWorkingMapToReal() {
  wmap.testNoMajors();
  map = wmap.clone();
//  for (var uid in wmap.sections) {
//    var ws = wmap.sections[uid];
//    var s = map.sections[uid];
//    var allUnchecked = true;
//    for (var pid in ws.pars) {
//      var wp = ws.pars[pid];
//      var p = s.pars[pid];
//      p.major = wp.major;
//      p.auto = wp.auto;
//      if (wp.major) {
//        allUnchecked = false;
//      }
//    }
//    if (allUnchecked) {
//      for (var pid in s.pars) {
//        s.pars[pid].major = true;
//        ws.pars[pid].major = true;
//      }
//    }
//  }
//  map.startSection = wmap.startSection;
}
function cmapReset() {
  Pop.Confirm.showYesNoCancel("This will reset template map to the default settings. Are you sure?", cmapResetConfirmed, true);  
}
function cmapResetConfirmed(confirmed) {
  if (confirmed) {
    resetMap();
    sendRequest(3, "action=deleteLookupMap&id=" + template.id);
    doWork("finishCmapReset()", "Resetting map");
    return;
  }
  Pop.close();
}
function finishCmapReset() {
  renderTemplateMap(cmapSuid);
  Pop.Working.close();
}
function resetMap() {
  for (var uid in map.sections) {
    var s = map.sections[uid];
    for (var pid in s.pars) {
      var p = s.pars[pid];
      p.major = p.defMajor;
      p.auto = false;
    }
  }
}
function createLookupMap() {
  var lumap = {
      "startSection":map.startSection,
      "main":{},
      "auto":{}
      };
  for (var uid in wmap.sections) {
    var ws = wmap.sections[uid];
    lumap.main[uid] = [];
    lumap.auto[uid] = [];
    var mpuids = lumap.main[uid];
    var apuids = lumap.auto[uid];
    for (var pid in ws.pars) {
      var wp = ws.pars[pid];
      if (wp.major) {
        mpuids.push(map.sections[uid].pars[pid].uid);
      }
      if (wp.auto) {
        apuids.push(map.sections[uid].pars[pid].uid);
      }
    }
  }
  return lumap;
}
