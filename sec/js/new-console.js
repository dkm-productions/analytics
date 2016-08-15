var shrinkhtml = null;
var shrinkEvent = null;
var startSuid = null;
var selSuid;  // currently selected section
var preset;  // template preset currently editing (rather than session)
var presetKey = null;  // new template preset for save as 
var savefn;  // confirm fn after dirty save

// User-initiated actions
function actionManual() {
  window.open("http://www.clicktate.com/ClicktateUserGuide.pdf");
}
function actionNew() {
  confirmDirty(actionNewConfirmed);
}
function actionNewConfirmed(confirmed) {
  if (confirmed) {
    if (session.real) {
      showNewNote(session.clientId, session.cname, null, null);
    } else {
      showPatientSelector(0);
    }
  }
}
function actionSyndrome() {
  SyndromePop.pop();
}
SyndromePop = {
  pop:function() {
    Html.Pop.from('pop-ss').pop();
  },
  close:function() {
    Pop.close();
  },
  download:function() {
    var type = _$('ss-type').value;
    buildDataSyncsOut();
    buildPendingOut(true);
    var cid = session.clientId;
    var sessdata = {
      cid:cid,
      type:type,
      sessionId:session.id,
      date:session.dos,
      dateCreated:session.dateUpdated,
      diagnoses:pendingDiagnoses,
      dsyncs:ssDsyncs
    };
    Pop.close();
    AdtDownloader.pop(cid, sessdata);
  }
}

function newNoteCallback(s) {
  setNewSession(s);
  //redirect(s.id);
}
function actionNewCopy() {
  Pop.Confirm.showYesNo("This action will create a new replicate of this note. Are you sure?", actionNewCopyConfirmed, true);
}
function actionNewCopyConfirmed(confirmed) {
  if (confirmed) {
    if (session.real) {
      Pop.Working.show("Copying note");
      finishNewSession(template.id, null, session.id, session.clientId, DateUi.getToday(), "");
    }
  }
}
function actionEditHeader() {
  showEditHeader(session.id, session.cname, session.dosdate, session.sendToId);
}
function editHeaderCallback(s) {
  session.sendTo = s.sendTo;
  session.sendToId = s.sendToId;
  renderSessionData();
}
function actionOpen() {
  confirmDirty(actionOpenConfirmed);
}
function actionOpenConfirmed(confirmed) {
  if (confirmed) {
    if (session.real) {
      showOpenNote(session.clientId);
    } else {
      choosePatient(1);
    }
  }
}
function openNoteCallback(s) {
  setNewSession(s);
  //redirect(s.id);
}
function aerrReload() {
  if (session && session.real) {
    setNewSession(session);
  }
}
function actionNewTemplate() {
  showNewCustomTemplate();
}
function newCustomTemplateCallback(p) {
  setTemplatePreset(p);
}
function actionApply() {
  showOpenCustomTemplate("Apply Custom Template", template.id);
}
function actionOpenTemplate() {
  confirmDirty(actionOpenTemplateConfirmed);
}
function actionOpenTemplateConfirmed(confirmed) {
  if (confirmed) {
    showOpenCustomTemplate();
  }
}
function openCustomTemplateCallback(p, caption) {
  setTemplatePreset(p, caption == "Apply Custom Template");
}
function actionSave() {
  if (session.closed) {
    return;
  }
  if (! session.real) {
    if (preset.id == null) {
      actionSaveAsTemplate();
      return;
    } else {
      finishActionSave();
      return;
    }
  }
  showSaveDialog();
}
function saveDialogCallback() {
  doWork("finishActionSave()", "Saving");
}
function finishActionSave() {
  if (session.real) {
    postRequest(6, "act=save&obj=" 
        + jsonUrl(newSaveSession(null, null))
        + "&a=" + jsonUrl(actions.stack));
  } else {
    postRequest(6, "act=psave&tpid=" + preset.id 
        + "&a=" + jsonUrl(actions.stack));
  }
  //var d = [];
  //for (var key in defaults) {
  //  d.push(defaults[key]);
  //}
  //postRequest("serverDefaults.php", "cid=" + session.clientId + "&tid=" + session.tid + "&a=" + jsonUrl(d));
  dirty = false;
}
function newSaveSession(html, out, dout) {
  return {
    "id":session.id,
    "t":session.title,
    "dos":session.dos,
    "tid":session.tid,
    "s":session.standard,
    "h":html,
    "out":out,
    "dout":dout
  };
}
function showPopHm(qid) {
  var t = new TableLoader("hm-tbody");
  var q = questions[qid];
  for (var hmid in q.hms) {
    var hm = q.hms[hmid];
    t.createTrTd("check");
    var c = createCheckbox("sel-hm", hmid);
    t.append(c);
    if (hm.checked) c.checked = true;
    t.createTd("fs", hm.proc);
    t.createTd(null, hm.date);
    t.createTd(null, null, hm.results);
  }
  Pop.show("pop-hm", null, null, function(e) {
    e.qid = qid;
  });
}
function hmInsert() {
  var sels = getCheckedValues("sel-hm", "hm-tbody");
  selectHms($("pop-hm").qid, sels);
  Pop.close();
}
function actionFree() {
  if (session.closed) {
    if (! showFT) return;
    showFT = false;
  } else {
    showFT = ! showFT;
  }  
  showHideFreeTags();
  lu_console.showFreeTags = showFT;
  saveConsoleCustom();
}
function showHideFreeTags(show) {
  menuSetCheck$("actionFree", showFT);
  var tags = $$("ft");
  if (tags == null) return;
  for (var i = 0; i < tags.length; i++) {
    if (tags[i].firstChild.className == "") {
      tags[i].style.display = "inline";
    } else {
      tags[i].style.display = (showFT) ? "inline" : "none";
    }
  }
}
function actionClearSendTo() {
  Pop.Confirm.showYesNo("Clearing the Send To field will remove this note from your document list when you exit. Is this OK?", actionClearSendToConfirmed, true);
}
function actionClearSendToConfirmed(confirmed) {
  if (confirmed) {
    postRequest(6,"act=clearSendTo&sid=" + session.id);  
  }
}
function clearSendToCallback() {
  session.sendTo = null;
  renderSessionData();
}
function actionClear() {
  if (session.closed) {
    return;
  }
  Pop.Confirm.showYesNo("This command will clear the existing document and start over. Are you sure?", actionClearConfirmed, true);
}
function actionClearConfirmed(confirmed) {
  if (confirmed) {
    // usc();
    var stack = actions.stack;   
    initObjects(false);
    actions.stack = popNonFsActions(stack); 
    renderTemplateMap();
    UsedParList.reset();
    prependMapAutoActions();
    autosave(true);
    if (actions.stack.length > 0) {
      setTimeout("startActions('Clearing note')", 5);
    }
  }
}
function actionAdd() {
  if (! session.closed) {
    return;
  }
  showAddendum();
}
function showAddendum() {
  setText("pop-add-cap-text", session.cname + " - " + session.title + " - " + "New Addendum");
  setValue("add-text", "");
  Pop.show("pop-add", "add-text");
}
function addendumSave() {
  var text = value("add-text");
  if (isBlank(text)) {
    Pop.Msg.showCritical("No addendum text was entered.", addendumErrorCallback);
    return;
  }
  text = esc(crlfToBr(text));
  Pop.close();
  Pop.Working.show("Saving");
  postRequest(6, "act=addendum&id=" + session.id + "&html=" + text); 
}
function addendumCallback() {
  Pop.Working.close();
  signCallback();
}
function addendumErrorCallback() {
  focus("add-text");
}
function actionSign() {
  if (session.closed) {
    return;
  }
  Pop.Confirm.showYesNoCancel("You are about to digitally sign this document and <b>lock it from further edits</b>. Are you sure?", actionSignConfirmed);  
}
function actionSignConfirmed(confirmed) {
  if (confirmed) {
    doWork("actionSignScan()", "Scanning note");
  }
}
function actionSignScan() {
  scanForOpenQuestions(sendSignRequest, "Sign and lock note anyway");
}
function scanForOpenQuestions(fn, caption) {
  var b = hasOpenQuestions();
  Pop.Working.close();
  if (b) {
    Pop.Confirm.showImportant("This note has <span style='color:red'>open or unanswered</span> questions.<br/>Do you still wish to proceed?",
    caption,
    null,
    "Cancel",
    null,
    false,
    fn);
  } else {
    fn(true);
  }  
}
function sendSignRequest(confirmed) {
  if (confirmed) {
    session.closed = 2;
    buildPendingOut();
    var dsyncOut = buildDataSyncsOut();
    var html = DocFormatter.consoleToHtml();
    $("doc").innerHTML = html;
    postRequest(6, "act=sign&obj=" 
        + jsonUrl(newSaveSession(html, pendingOut, dsyncOut))
        + "&a=" + jsonUrl(actions.stack));        
    dirty = false;
  }
}
function actionDelete() {
  if (session.closed) {
    return;
  }
  Pop.Confirm.showYesNoCancel("This action will <b>delete the document</b> and exit the console. Are you sure?", actionDeleteConfirmed);  
}
function actionDeleteConfirmed(confirmed) {
  if (confirmed) {
    if (session.real) {
      postRequest(6, "act=delete&sid=" + session.id);
    } else {
      postRequest(6, "act=pdelete&tpid=" + preset.id);
    }
  }
}
function actionViewMap() {
  if (menuToggleCheck()) {
    expandMap();
  } else {
    shrinkMap();
  }
}
function actionViewHeader() {
  var checked = menuToggleCheck();
  showHideHeader();
  lu_console.showHeader = checked;
  saveConsoleCustom();
}
function showHideHeader(show) {  // show optional
  show = getMenuCheckedOrChange$("actionViewHeader", show);
  var h = $(headerId);
  if (show == null || h == null) return;
  if (show) {
    h.className = "doc-header";
    h.style.height = "100px";
  } else {
    h.className = HIDDEN;
  }
}
function actionOrder() {
  var items = buildOrderItems();
  OrderSheet.pop(items);
}
function actionVisitSum() {
  Html.Window.working(true);
  autosave(true, function() {
    Html.Window.working(false);
    VisitSummaryPop.pop_forCid(session.clientId);
  })
}
function actionDownload() {
  event.returnValue = false;
  scanForOpenQuestions(finishActionDownload, "Download note anyway");
  //doWork("actionDownloadScan()", "Scanning note");  can't do this, IE thinks the download's not user-initiated and does the yellow bar thing.
}
//function actionDownloadScan() {
//  scanForOpenQuestions(finishActionDownload, "Download note anyway");
//}
function finishActionDownload(confirmed) {
  if (confirmed) {
    var s;
    if (session.closed >= 2) {
      s = $("doc").children;
    } else {  
      s = [$("dSections")];
    }
    var sig = sigName();
    download(s, sig);
  }
}
function actionDownloadPdf() {
  event.returnValue = false;
  scanForOpenQuestions(finishActionDownloadPdf, "Download note anyway");
}
function finishActionDownloadPdf(confirmed) {
  if (confirmed) {
    DocFormatter.consoleToPdf(session);
  }
}
function actionCopy() {
  doWork("actionCopyScan()", "Scanning note");
}
function actionCopyScan() {
  scanForOpenQuestions(finishActionCopy, "Copy to clipboard anyway");
}
function finishActionCopy(confirmed) {
  if (confirmed) {
    copy();
  }
}
var undoText;
function actionUndo() {
  if (session.closed) return;
  if (actions.undos.length == 0) return;
  var lastAction = actions.undos.pop();
  undoText = "Working<BR>Undo: " + lastAction;
  resetToolUndo();
  if (actions.undos.length == 0 && actions.undoStack) {  // undoing a remove par, restore original stack
    actions.stack = actions.undoStack;
    actions.undoStack = null;
  } else {
    //actions.undoStack = null;
    if (actions.stack.length == 0) return;
    var action = actions.stack.pop();
    setDirty();
    if (specialUndo(action)) {
      return;
    }
  }
  doWork("redoActions()", undoText);
}
function specialUndo(action) {
  var a = action.split("(");
  var fn = a[0];
  if (fn == "docDel" || fn == "getPar") {
    eval("undo" + action);
    return true;
  }
}
function undodocDel(start, end) {
  doWork("docDel('" + start + "','" + end + "',true)", undoText);
}
function undogetPar(pid) {
  var pidi = Pidi.from(pid);
  //var ct = (pidi.cloneix == null) ? 0 : pidi.cloneix;
  //doWork("removePar(" + pidi.pid + "," + ct + ",true)", undoText);
  Pop.Working.show(undoText);
  async(function() {
    removePar(pidi, null, true);
  })
}
function actionGetPar(pid, addClone, removeCloneIndex) {  // addClone=true to allow multiple adds, removeCloneIndex only passed when removing clone instance
  //var a = $("apl_" + pid);
  ParPreviewTrigger.off();
  var a = event.srcElement;
  var desc = "\"" + a.innerText + "\""; 
  if (a.className == "used" && ! addClone) {
    //askRemovePar(pid, desc, removeCloneIndex);
    askRemovePar(Pidi.from(pid), desc);
  } else {
    if (false) {  // todo
      //showParPreview(template.id, pid, session.noteDate, desc);
    } else {
      reqParId = pid;
      //if (addClone) {
        //doWork("getClonePar(" + pid + "," + desc + ")", "Getting paragraph " + desc, true);
      //} else {
        doWork("getPar(" + pid + "," + desc + ")", "Getting paragraph " + desc, true);
      //}
    }
  }
}
var rpar = {};
//function askRemovePar(pid, desc, removeCloneIndex) {
//  rpar.pid = pid;
//  rpar.desc = desc;
//  rpar.ct = removeCloneIndex ? removeCloneIndex : 0;
//  if (findGetParActionIndex(pid, rpar.ct) > -1) {
//    Pop.Confirm.showYesNoCancel("This action will <b>remove</b> " + desc + " and all associated actions related to this paragraph. Are you sure?", askRemoveParConfirmed);
//  } else {
//    Pop.Msg.showCritical("This paragraph was inserted in response to a prior question and cannot be removed.");
//  }
//}
function askRemovePar(pidi, desc) {
  if (findGetParActionIndex(pidi.pidi) > -1) {
    Pop.Confirm.showYesNoCancel("This action will <b>remove</b> " + desc + " and all associated actions related to this paragraph. Are you sure?", function(confirmed) {
      if (confirmed) {
        Pop.Working.show('Removing paragraph ' + desc);
        async(function() {
          removePar(pidi, null, null, desc);
        })
      }
    })
  } else {
    Pop.Msg.showCritical("This paragraph was inserted in response to a prior question and cannot be removed.");
  }
}
function askRemoveParConfirmed(confirmed) {
  if (confirmed) {
    doWork("removePar(" + rpar.pid + "," + rpar.ct + ",false," + rpar.desc + ")", "Removing paragraph " + rpar.desc);
  }
}
function actionSaveAsTemplate() {
  //if (session.real) {
  //  confirmDirty(actionSaveAsTemplateConfirmed);
  //} else {
    actionSaveAsTemplateConfirmed(true);
  //}
}
function actionSaveAsTemplateConfirmed(confirmed) {
  if (confirmed) {
    Pop.Prompt.show("Name of new customized template?", function(name) {
      if (denull(name) != "") {
        Pop.close();
        presetKey = newPresetKey(name);
        doWork("startSaveAsTemplate('" + addslashes(name) + "')", "Saving template");
      }
    })
  }
}
function actionSaveTemplateConfirmed(confirmed) {
  if (confirmed) {
    doWork("startSaveTemplate('" + addslashes(presetKey.name) + "')", "Saving template");
    if (preset != null) {
      preset.id = presetKey.id;
      preset.name = presetKey.name;
    }
  }
}
function startSaveAsTemplate(name) {
  postRequest(6, "act=saveas&n=" + name + "&tid=" + template.id 
      + "&a=" + jsonUrl(actions.stack));
  if (! session.real) {
    dirty = false;
  }
}
function startSaveTemplate(name) {
  postRequest(6, "act=psave&tpid=" + presetKey.id 
      + "&a=" + jsonUrl(actions.stack));
}
function actionExit() {
  confirmDirty(actionExitConfirmed);
}
var closecid;
function actionExitConfirmed(confirmed) {
  if (confirmed) {
    closecid = session && session.clientId;
    if (session && session.locked) {
      var sid = session.id;
      var closed = session.closed;
      session = null;
      closeSession(sid, closed);
      return;
    }
    closeWindow();
  }
}
function closeWindow() {
  window.close();
//  Html.Window.working(true);
//  if (closecid)
//    window.location.href = 'face.php?id=' + closecid;
//  else
//    window.location.href = 'documents.php';
}
function closeSession(id, closed) {  // set session=null to close window after close finishes
  Pop.Working.show("Closing");
  var html = (closed) ? '' : fixAmper(DocFormatter.consoleToHtml());
  postRequest(6, "act=close&sid=" + id + "&html=" + html);
}
function fixAmper(s) {  // replace & with %26 for passing in querystring
  return s.replace(/&/g,"%26");
}
function closeSessionCallback() {
  Pop.Working.close();
  if (session == null) {
    closeWindow();
  } else {
    resetUi();
  }
}
function confirmDirty(fn) {
  if (isDirty()) {
    savefn = fn;
    Pop.Confirm.showYesNoCancel("Document has unsaved changes. Do you want to <b>save the document</b> now?", confirmSaveDirty);  
  } else {
    fn(true);
  }
}
function confirmSaveDirty(confirmed) {
  if (confirmed == null) {
    savefn(false);
    savefn = null;
  } else {
    if (confirmed) {
      actionSave();
    } else {
      savefn(true);
      savefn = null;
    }
  }
}
function autosave(always, callback) {
  if (always || dirty) {
    if (session.real && ! session.closed) {
      tlog("--------------> autosave()", true);
      var html = DocFormatter.consoleToHtml();
      var iol = $('s_22'); 
      var radiology = $('s_33');
      var a = [];
      if (iol) {
        iol = DocFormatter.toHtml(iol);
        if (! String.isBlank(iol))
          a.push(iol);
      }
      if (radiology) {
        radiology = DocFormatter.toHtml(radiology);
        if (! String.isBlank(radiology))
          a.push(radiology);
      }
      var iols = (a.length) ? '<ul><li>' + a.join('</li><li>') + '</li></ul>' : null;
      var instructs = buildFriendlyPlan();
      buildPendingOut();
      var stub = session.stub;
      var obj = {
        'sid':session.id,
        'cid':session.clientId,
        'title':session.title,
        "a":Json.encode(actions.stack), 
        "html":html,
        'dos':session.dos,
        'out':pendingOut,
        'iols':iols,
        'instructs':instructs,
        'stub':stub};
      Ajax.post('Session', 'autosave', obj, function(ts) {
        if (callback) 
          callback();
        autosaveSessionCallback(ts);  
      })
      //postRequest(6, 'act=autosave&obj=' + jsonUrl(obj));
      dirty = false;
      setText("sb", "Auto-saving document...");
    }
  } else {
    if (callback)
      callback();
  }
}

// AJAX callbacks
function workingCallback(value) {
  working.style.background = value ? 
      "url(img/icons/working10.gif) black center center no-repeat" :
      "url(img/icons/logo-tiny.gif) white center center no-repeat";
}
function savePresetCallback(p) {
  Pop.Working.close();
  preset.dateUpdated = p.dateUpdated;
  preset.updatedBy = p.updatedBy;
  renderSessionData();
  if (savefn != null) {
    savefn(true);
    savefn = null;
  } else {
    if (preset != null) {
      resetMenus();
    }
  }
}
function saveSessionCallback(s) {
  Pop.Working.close();
  if (s.dos != session.dos) {
    resetDosSpans(s.dos);
    session.dos = s.dos;
    session.dosfull = s.dosfull;
    session.dosdate = s.dosdate;
  }
  session.title = s.title;
  session.standard = s.standard;
  session.dateUpdated = s.dateUpdated;
  session.updatedBy = s.updatedBy;
  renderSessionData();
  if (savefn != null) {
    savefn(true);
    savefn = null;
  }
  // TODO: tiani call
}
function autosaveSessionCallback(ts) {
  tlog("--------------> autosaveSessionCallback()");
  session.autosaved = ts;
  renderSessionData();
}
function signCallback() {
  redirect(session.id);
}
function redirect(sid) {
  window.location = "new-console.php?sid=" + sid + "&" + Math.random();
}
function deleteCallback() {
  window.close();
}
function addPresetCallback(tp) {
  Pop.Working.close();
  if (preset != null) {
    preset = tp;
    resetMenus();
  }
  Pop.Msg.showInfo("Custom template \"" + tp.name + "\" saved.");
}
function addPresetExistsCallback(id) {
  Pop.Working.close();
  presetKey.id = id;
  Pop.Confirm.showYesNoCancel("A template by that name already exists. Do you want to overwrite?", actionSaveTemplateConfirmed);
}

// UI functions
function resetMenus() {
  disable("actionViewHistory");
  disable("actionOptions");
  renderSessionData();
  if (actions.undos.length == 0) {
    disable("actionUndo");
  } else {
    enable("actionUndo");
  }
  disable("actionDel");
  // Editing session
  if (session.real) {
    $$$("actionSave", menutool, "A")[0].innerText = "Save Note As...";
    $$$("actionDelete", menutool, "A")[0].innerText = "Delete Note";
    enable("actionDownload");
    enable("actionCopy");
    enable("actionNewCopy");
    if (session.closed || session.ro) {
      $("templatemap").style.display = "";
      $("actionViewMap").className = "check-off";
      disable("actionViewMap");
      disable("actionViewHeader");
      disable("actionSave");
      disable("actionDownload");
      if (session.closed >= 3) {
        disable("actionSign", true);
        enable("actionAdd", true);
      } else {
        disable("actionSign");
        disable("actionAdd", true);
      }
      disable("actionDelete");
      disable("actionSaveAsTemplate");
      disable("actionUndo");
      disable("actionClear");
      disable("actionFree");
      disable("actionEditHeader");
      disable("actionOrder");
      disable("actionVisitSum");
    } else {
      $("templatemap").style.display = "block";
      $("actionViewMap").className = "check-on";
      enable("actionViewMap");
      enable("actionViewHeader");
      enable("actionSave");
      enable("actionSign", true);
      disable("actionAdd", true);
      enable("actionDelete");
      enable("actionSaveAsTemplate");
      enable("actionClear");
      enable("actionFree");
      enable("actionEditHeader");
      enable("actionOrder");
      enable("actionVisitSum");
    }
  } else {
    // Editing custom template
    $("templatemap").style.display = "block";
    $("actionViewMap").className = "check-on";
    $$$("actionSave", menutool, "A")[0].innerText = "Save Template";
    $$$("actionDelete", menutool, "A")[0].innerText = "Delete Template";
    disable("actionSign");
    disable("actionAdd");
    disable("actionDownload");
    disable("actionCopy");
    disable("actionNewCopy");
    disable("actionOrder");
    disable("actionVisitSum");
  }
  applyPermissions();  // Do this last to override above
}
function applyPermissions() {
  if (! me.Role.Artifact.noteReplicate)
    disable("actionNewCopy")
  if (! me.Role.Artifact.noteSign) 
    disable("actionSign");
  if (! me.Role.Artifact.noteAddendum) 
    disable("actionAdd");
  if (! me.Role.Artifact.noteCreate) { 
    disable("actionDelete");
    disable("actionNew");
    disable("actionNewCopy");
    disable("actionOpen");
  }
  if (! me.Role.Artifact.templates) { 
    disable("actionNewTemplate");
    disable("actionOpenTemplate");
    disable("actionSaveAsTemplate");
    disable("actionApply");
  }
}
function shrinkMap() {
  shrink("templatemap");
  $("actionViewMap").className = "check-off";
}
function expandMap() {
  expand('templatemap');
  $("actionViewMap").className = "check-on";
}
function renderSessionData() {
  var title;
  var d = $("doccap");
  var dh = $("doccaph");
  var sb = $("sb");
  if (session.title == null || session.title == "") {
    session.title = template.name;
  }
  if (session.real) {
    title = session.cname + " - " + session.title + " - " + session.dosfull;
    var h = session.cname + " - ";
    if (! session.closed && ! session.ro) {
      h += "<a href='javascript:showSaveDialog()' class='acthbig'>" + session.title + "</a>";
    } else {
      h += session.title;
    } 
    h += " - ";
    if (! session.closed && ! session.ro) {
      h += "<a href='javascript:showSaveDialog(\"sv-dos\")' class='acthbig'>" + session.dosfull + "</a>";
    } else {
      h += session.title;
    } 
    var h2 = "<b>Send To:</b>&nbsp; ";
    var st = (session.sendTo == null) ? "[None]" : session.sendTo;
    if (! session.ro) {
      h2 += "<a href='javascript:actionEditHeader()' class='acth'>" + st + "</a>";
    } else {
      if (session.sendTo != null) {
        h2 += "<a href='javascript:actionClearSendTo()' class='acth'>" + st + "</a>";
      } else {
        h2 += "<b>" + st + "</b>";
      }
    }
    h2 += "</span>";
    d.innerHTML = h;
    d.style.color = "";
    dh.innerHTML = h2;
    sb.innerHTML = buildHistoryLine(session); 
  } else if (preset != null) {
    title = preset.name + " - Custom Template - " + template.name;
    d.style.color = "#f42941";
    d.innerText = title;
    sb.innerHTML = buildHistoryLine(preset); 
  }
  document.title = title;
}
function buildHistoryLine(o) {
  var h = "";
  if (o.createdBy) {
    h += "<b>Created:</b> " + o.dateCreated + " by " + o.createdBy + "&nbsp; ";
  }
  if (o.autosaved) {
    h += "<b>Last Auto-Saved:</b> " + o.autosaved;
    o.autosaved = null;
  } else {
    if (o.dateUpdated && (o.createdBy == null || o.updatedBy)) {
      h += "<b>Last Updated:</b> " + o.dateUpdated; 
      if (o.updatedBy) {
        h += " by " + o.updatedBy;
      }
    }
  }
  return h;
}
function renderTemplateMap(startSuid) {  // startSuid passed when refreshing map from customization
  tlog("renderTemplateMap", true);
  if (session.closed) {
    return;
  }
  tlog("clearing");
  var divx = clearChildren($("parindex"));
  var divp = clearChildren($("parlist"));
  var ul = clearChildren($("sectionsul"));
  var hx = [];
  var hp = [];
  tlog("building");
  map.Sections.forEach(function(s) {
    if (s.Pars)
      renderTemplateMapPars(s.uid, hx, hp, "ms-" + s.sectionId, s.Pars, s.Pars.length);
  })
  tlog("done building");
  divx.innerHTML = hx.join("");
  divp.innerHTML = hp.join("");
  tlog("used");
  if (startSuid == null) {
    //clearChildren($("usedlistpars"));
    startSuid = map._startSection;
  } else {
    syncUsedClass();
  }
  renderSection(startSuid);
  $("parlist").className = "";
  //doUsedQueue();
}
function syncUsedClass() {
  var pids = UsedParList.getUsedPids();
  pids.forEach(function(pid) {
    setUsed(pid);
  })
//  var lis = $("usedlistpars").children;
//  if (lis != null && lis.length > 0) {
//    for (var i = 0; i < lis.length; i++) {
//      var p = lis[i].firstChild;
//      setUsed(p.id.substring(4));
//    }
//  }
}
function setUsed(pid) {
  var all = $("apl_" + pid);
  if (all == null) {
    return null;
  } 
  var a = $("mpl_" + pid);
  if (a != null) {
    a.className = "used";
  }
  all.className = "used";
  return all;
}
function removeUsed(pid, ct) {
  var a = $("mpl_" + pid);
  if (a != null) {
    a.className = "";
  }
  a = $("apl_" + pid);
  if (a != null) {
    a.className = "";
  }
//  a = $(getUsedAnchorId(pid, ct));
//  if (a != null) {
//    var li = a.parentElement;
//    li.parentElement.removeChild(li);
//  }
}
function getUsedAnchorId(pid, ct) {
  if (ct == 0) {
    ct = 1;
  }
  return "upl" + ct + "_" + pid; 
}
function showSection(suid) {
  tlog("showSection(" + suid + ")", true);
  setTimeout(buildFn("finishShowSection", [suid]));
  //doWork("finishShowSection('" + suid + "')", "Building", true);
}
function finishShowSection(suid) {
  renderSection(suid);
  //Pop.Working.close();
}
function renderSection(suid) {
  tlog("renderSection", true);
  selSuid = suid;
  var ul = clearChildren($("sectionsul"));
  map.Sections.forEach(function(s) {
    var uid = s.uid;
    if (s.Pars) {
      var title = uid.toUpperCase().substring(0, 5);
      var id = "ms-" + s.sectionId;
      var li = addListItem(ul, null, "", id);
      var ulp = $(id + "-p");
      var ulx = $(id + "-x");
      if (uid == suid || suid == null) {
        li.className = "sel";
        li.innerHTML = title;
        ulp.style.display = "block";
        ulx.style.display = "block";
        $("partitle").innerHTML = s.name;
        suid = uid;
        selSuid = uid;
      } else {
        li.className = "";
        li.innerHTML = "<a href=\"javascript:showSection('" + uid + "')\" title=\"" + s.name + "\">" + title + "</a>";
        ulp.style.display = "none";
        ulx.style.display = "none";
      }
    }
  })
  $("parlist").scrollTop = 0;
}
function renderTemplateMapPars(suid, dhx, dhp, id, pars, parct) {
  var ixid = id + "-ix-";
  var hx = [];
  var hp = [];
  var hpmain = [];
  var hpall = [];
  //var ulx = createList(divx, id + "-x");
  //var ulp = createList(divp, id + "-p", "parlistul");
  //var ulpmain = createList(ulp);
  //var ulpall = createList(ulp);
  //ulx.style.display = "none";
  //ulp.style.display = "none";
  var showRolo = (parct > 15);
  hx.push("<ul id='" + id + "-x'>");
  hx.push("<li class=top><a ");
  if (! showRolo) hx.push("style='visibility:hidden' "); 
  hx.push("href='javascript:scrollToPar()'>^</a></li>");
  hp.push("<ul id='" + id + "-p' class=parlistul>");
  hpmain.push("<li class=section>Main</li>");
  hpall.push("<li class=section>Complete list</li>");
  var showUsed = false;
  var major = 0;
  var minor = 0;
  var last = "";
  var first = true;
  pars.forEach(function(par) {
    var pid = par.parId;
    if (pid.substr(0, 1) == 'P')
      pid = pid.substr(1);
    if (pid == '3030' && me.userGroupId > 2 && me.userGroupId != 2233) {
      par.desc = null;
    }
    if (par.desc) {
      var ix = par.desc.substring(0, 1).toUpperCase();
      var liid = null;
      if (ix.charCodeAt(0) < 65) {
        ix = "#";
      }
      if (showRolo && ix != last) {
        liid = ixid + ix;
        //addListItem(ulx, null, "<a href=\"javascript:scrollToPar('" + liid + "')\">" + ix + "</a>");
        hx.push("<li><a href=\"javascript:scrollToPar('" + liid + "')\">" + ix + "</a></li>");
        last = ix;
        if (! first) {
          //addListItem(ulpall, null, "", null, "section2");
          hpall.push("<li class=section2></li>");
        }
      }
      //addListItem(ulpall, null, buildGetParAnchor("apl_", pid, par.desc), liid);
      var lic = (par.cloneable) ? " class=clone" : "";
      lic += ' suid=' + suid + ' puid=' + par.uid;
      hpall.push("<li" + lic + " id=" + liid + ">" + buildGetParAnchor("apl_" + pid, pid, par.desc, false, par.cloneable) + "</li>");
      if (par.major) {
        //addListItem(ulpmain, null, buildGetParAnchor("mpl_", pid, par.desc));
        hpmain.push("<li" + lic + ">" + buildGetParAnchor("mpl_" + pid, pid, par.desc, false, par.cloneable) + "</li>");
        major++;
      } else {
        minor++;
      }
      first = false;
    }
  })
  //addListItem(ulpmain, null, "", null, "spacer");
  hpmain.push("<li class=spacer></li>");
  for (var i = 0; i < 30; i++) {
    //addListItem(ulpall, null, "", null, "spacer2");
    hpall.push("<li class=spacer2></li>");
  }
  if (major > 0 && minor > 0) {
    hp.push("<ul>" + hpmain.join("") + "</ul>");
    //ulpmain.style.display = "none";
  }
  hp.push("<ul>" + hpall.join("") + "</ul></ul>");
  hx.push("</ul>");
  dhx.push(hx.join(""));
  dhp.push(hp.join(""));
}
function buildGetParAnchor(id, pid, desc, used, addClone, removeCloneIndex) {
  var h = "<a id='" + id + "' href=\"javascript:\" onclick=\"actionGetPar(" + pid;
  if (addClone) {
    h += ",true)\" clone=1";
  } else if (removeCloneIndex) {
    h += ",false," + removeCloneIndex + ")\"";
  } else {
    h += ")\"";
  }
  if (used) h += " class='used'"; 
  h += ">" + desc + "</a>";
  return h;
}
//function doUsedQueue() {
//  for (var i = 0; i < usedQueue.length; i++) {
//    var uq = usedQueue[i];
//    addToUsed(uq.pid, uq.ct);
//  }
//}
function addToUsed(pid, ct) {  // ct = instance count
  var a = setUsed(pid);
  if (a == null) {
    usedQueue.push({pid:pid,ct:ct});  // anchor not rendered yet, queue for later
  } else {
    var id = getUsedAnchorId(pid, ct);
    if ($(id) == null) {
      var li = document.createElement("li");
      var text = a.innerText;
      var html;
      if (a.clone) {
        text += " #" + ct;
        html = buildGetParAnchor(id, pid, text, true, false, ct);
      } else {
        html = buildGetParAnchor(id, pid, text, true);
      }
      li.innerHTML = html;
      $("usedlistpars").appendChild(li);
    }
  }
}
function isDirty() {
  if (session == null) return false;
  return ! session.closed && dirty;
}
function resize() {
  var wh = window.innerHeight || document.documentElement.clientHeight;
  var h = wh - 80; 
  _$("templatemap-shrunk").setHeight(h);
  _$("parindex").setHeight(h - 70 - 23);
  _$("parlist").setHeight(h - 169 - 23);
  _$("doc").setHeight(h - 84);    
}
function scrollToPar(id) {
  scrollTo("parlist", id);
}
function shrink(id) {
  shrinkEvent = newShrinkEvent(id, true);
  shrinkEvent.intId = setInterval("shrinkLoop()", 1);
  shrinkhtml = $("parlist").innerHTML;
  $("parlist").innerHTML = "";
}
function shrinkLoop() {
  shrinkEvent.mar = shrinkEvent.mar - shrinkEvent.inc;
  shrinkEvent.inc = shrinkEvent.inc * 1.4;
  shrinkEvent.divE.style.marginLeft = shrinkEvent.mar + "px";
  if (shrinkEvent.mar - shrinkEvent.inc < shrinkEvent.limit) {
    clearInterval(shrinkEvent.intId);
    shrinkEvent.divS.style.display = "";
    shrinkEvent.divE.style.display = "none";
    resize();
  }
}
function expandLoop() {
  if (shrinkEvent.finish) {
    expandFinish();
    return;
  }
  shrinkEvent.mar = shrinkEvent.mar + shrinkEvent.inc;
  shrinkEvent.inc = shrinkEvent.inc * 1.4;
  if (shrinkEvent.mar > shrinkEvent.limit) {
    shrinkEvent.divE.style.marginLeft = "";
    shrinkEvent.finish = true;
  } else {
    shrinkEvent.divE.style.marginLeft = shrinkEvent.mar + "px";
  }
}
function expandFinish() {
  clearInterval(shrinkEvent.intId);
  $("parlist").innerHTML = shrinkhtml;
  resize();
}
function expand(id) {
  shrinkEvent = newShrinkEvent(id, false);
  shrinkEvent.divE.style.display = "";
  shrinkEvent.divS.style.display = "none";
  shrinkEvent.intId = setInterval("expandLoop()", 1);
}
function newShrinkEvent(id, shrinking) {
  var divE = $(id + "-expanded");
  var mar;
  var limit;
  var inc = 5;
  if (shrinking) {
    mar = 0;
    limit = -divE.clientWidth;
  } else {
    mar = -divE.clientWidth + 40;
    limit = 0;
  }
  return {
    "divE":divE,
    "divS":$(id + "-shrunk"),
    "mar":mar,
    "limit":limit,
    "inc":inc,
    "intId":null
  };
}
function newPresetKey(name) {
  return {
    "id":null,
    "name":name,
    "tid":template.id
  };
}
function resetSearch() {
  if (value("tsearch") == "") {
    setValue("tsearch", "Search...").className = "fade";
  }
}
function focusSearch() {
  if ($("tsearch").className == "fade") {
    setValue("tsearch", "").className = "";
  } else {
    $("tsearch").select();
  }
}
function searchTemplate() {
  $("tsearch-a").focus();
  var text = ($("tsearch").className == "fade") ? "" : value("tsearch");
  showTemplateExplorer(text, template.id, session.noteDate, selSuid);
}
function saveConsoleCustom() {
  postLookupSave("saveConsoleCustom", lu_console);
}
function closeOverlayPop() {
  Pop.close();
}