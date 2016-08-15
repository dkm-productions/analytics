var allerQ;  // parent element of clones
var allerA;  // existing allergy anchor, null if new
var aq;  // original allergy question
var allerAdd;  // add trigger, null if triggered by existing
var FIRST_MED_IX = 4;  // where meds start in single options
var initAllerA;

// Initialize
function allerReset() {
  nextAllerAddId = 0;  // must be reset on clear
}

// Make an initial instance defaulted to "none"
function initAller(qid) {
  if (nextAllerAddId == 0) {
    var allerAddId = "q_" + qid + "o";
    allerAdd = document.getElementById(allerAddId);
    initAllerA = addAllergy(qid, allerAddId, 0, [], null, []);  // no need to save this action
    allerAdd.style.display = "none";  // Hide the add button; must change the default to reshow add button
  }
}

function addFacesheetAllergy(q, reactions) {
  aq = q;
  var ix = aq.sel[0];
  var sel = aq.sel.splice(1, aq.sel.length);
  var medOther = aq.opts[aq.mix - 1].text;
  var allerAddId = "q_" + aq.id + "o";
  if (initAllerA) {
    changeAllergy(aq.id, initAllerA.id, ix, sel, medOther, reactions);
    initAllerA = null;
  } else {
    addAllergy(aq.id, allerAddId, ix, sel, medOther, reactions);
  }
}

// Show allergy popup
function showPopAllergy() {
  event.returnValue = false;
  if (session.closed) return;
  var allerTrigger = event.srcElement;
  if (allerTrigger.getAttribute('qid')) {
    aq = questions[allerTrigger.getAttribute('qid')];    
  } else {
    aq = questions[allerTrigger.parentElement.parentElement.parentElement.getAttribute('qid')];
  }
  //allerQ = allerTrigger.parentElement;
  //q = questions[unqidify(allerQ.id)];
  aq.clone = true;
  aq.type = Q_TYPE_ALLERGY;
  aq.csi = 4;
  aq.cbo = true;
  if (allerTrigger.className != "clone") { 

    // Popup triggered by existing aller (update)
    allerAdd = null;
    allerA = allerTrigger;
    var sel = eval(allerA.sel);
    sel.unshift(allerA.ix);
    qRestore(aq, sel, [], allerA.medOther, allerA.effectOthers);
    //aq.opts[aq.mix - 1].text = allerA.medOther;
    //qAppendOtherOpts(q, aq.sel[aq.sel.length - 1]);  // temp
    aq.cix = 1;  // dummy clone index
  } else {
    
    // Popup triggered by "add" link (add)
    allerAdd = allerTrigger;
    allerA = null;
    aq.sel = [];
    aq.cix = null;
  }
  showQuestion(aq);
}

// Action buttons
function allerDoDelete() {
  deleteAllergy(allerA.id);
}
function allerDoOk(q) {
  var aq = q;
  var other = qOtherTexts(aq);
  //var medOther = aq.opts[aq.mix - 1].text;
  var medOther = other.single;
  var effectOthers = other.multis;
  var singleIx = aq.sel[0];
  var sel = aq.sel.splice(1, aq.sel.length);
  if (allerA == null) {
    // Create the new instance
    addAllergy(aq.id, allerAdd.id, singleIx, sel, medOther, effectOthers, true);
  } else {
    changeAllergy(aq.id, allerA.id, singleIx, sel, medOther, effectOthers, true);
  }
  Pop.close();
}

function setOtherMedText(q, text) {
  q.opts[q.mix - 1].text = text;
}

function asArray(e) {
  if (e != null && ! isArray(e)) {
    return [e];
  } else {
    return e;
  }
}

function getAllerAddId(q) {
  return qidify(q.id) + 'o';
}

// Action methods (these are saved)
function addNewCropAllergies(q, allergies) {
  clearNewCropAllergies(q);
  var allerAddId = getAllerAddId(q);
  var allerA;
  if (allergies) {
    for (var i = 0; i < allergies.length; i++) {
      var a = allergies[i];
      var r = (a.reactions) ? "'" + a.reactions + "'" : 'null';
      pushAction("addAllergyByData('" + q.id + "','" + a.agent + "'," + r + ")");
      addAllergyByData(q.id, a.agent, a.reactions);
    }
  }
}
function clearNewCropAllergies(q) {
  var allerAddId = getAllerAddId(q);
  var allerAdd = $(allerAddId);
  var allerK = $(allerAdd.getAttribute('kid'));
  if (allerK.children) 
    clearAllergies(allerAddId);
}
function clearAllergies(allerAddId) {
  var allerAdd = $(allerAddId);
  if (allerAdd) {
    var allerK = $(allerAdd.getAttribute('kid'));
    pushAction("clearAllergies" + argJoin([allerAddId]), null, true);
    clearChildren(allerK);
    allerReset();
    initAller(allerAdd.getAttribute('qid'));
  }
}
function addAllergy(qid, allerAddId, ix, sel, medOther, effectOthers, save) {
  if (effectOthers=='other' && nextAllerAddId == 1) return;
  if (medOther=='other') medOther=null;
  if (effectOthers=='other') effectOthers=[];
  var selJson = toJSONString(sel);
  effectOthers = asArray(effectOthers);
  aq = questions[qid];
  aq.cbo = true;
  qRestore(aq, combine(ix, sel), [], medOther, effectOthers);
  if (save) {
    var undoText = "Add Allergy \"" + qSelText(aq) + "\""; 
    pushAction("addAllergy(" + qid + ",'" + allerAddId + "'," + ix + "," + selJson + "," + toJSONString(medOther) + "," + toJSONString(effectOthers) + ")", undoText);
  }
  var allerAdd = $(allerAddId);
  //var allerQ = allerAdd.parentElement;
  var allerK = $(allerAdd.getAttribute('kid'));
  var div = document.createElement("div");
  allerK.appendChild(div);
  var allerA = document.createElement("a");
  //setOtherMedText(questions[qid], medOther);
  allerA.id = allerAddId + nextAllerAddId++;
  if (me.isErx()) {
    allerA.href = "javascript:";
    allerA.style.color = 'black';
  } else {
    allerA.href = "javascript:";
    allerA.onclick = showPopAllergy;
  }
  allerA.className = "listAnchor2";
  allerA.setAttribute("ix", nextAllerAddId);
  allerA.setAttribute("ixText", getOptText(questions[qid].opts[ix]));
  allerA.setAttribute("sel", selJson);
  allerA.setAttribute("selText", selTextJson(questions[qid], sel));  // for saving reactions to outdata
  allerA.setAttribute("medOther", medOther);
  allerA.setAttribute("effectOthers", effectOthers);
  allerA.innerText = qSelText(aq);
  div.appendChild(allerA);
  //allerQ.insertBefore(allerA, allerAdd);
  return allerA;
}
function combine(ix, sel) {
  var s = [ix];
  for (var i = 0; i < sel.length; i++) {
    s.push(sel[i]);
  }
  return s;
}
function changeAllergy(qid, allerId, ix, sel, medOther, effectOthers, save) {
  if (medOther=='other') medOther=null;
  if (effectOthers=='other') effectOthers=[];
  var selJson = toJSONString(sel);
  effectOthers = asArray(effectOthers);
  aq = questions[qid];
  aq.cbo = true;
  qRestore(aq, combine(ix, sel), [], medOther, effectOthers);
  if (save) {
    var undoText = "Change Allergy to \"" + qSelText(aq) + "\""; 
    pushAction("changeAllergy(" + qid + ",'" + allerId + "'," + ix + "," + selJson + "," + toJSONString(medOther) + "," + toJSONString(effectOthers) + ")", undoText);
  }
  //setOtherMedText(questions[qid], medOther);
  var allerA = $(allerId);
  if (! allerA) return;
  //allerA.setAttribute('ix', ix);
  allerA.setAttribute('ixText', getOptText(questions[qid].opts[ix]));
  allerA.setAttribute('sel', selJson);
  allerA.setAttribute('selText', selTextJson(questions[qid], sel));  // for saving reactions to outdata
  allerA.setAttribute('medOther', medOther);
  allerA.setAttribute('effectOthers', effectOthers);
  allerA.innerText = qSelText(aq);
  // Redisplay add aller button
  var allerAddId = "q_" + qid + "o";
  allerAdd = $(allerAddId);
  allerAdd.style.display = (ix == 0 || allerAdd.erx) ? "none" : "";
}
function selTextJson(q, sel) {
  var selText = [];
  for (var i = 0; i < sel.length; i++) {
    selText.push(getOptText(q.opts[sel[i]]));
  }
  return toJSONString(selText);
}
function deleteAllergy(allerId) {
  var allerA = $(allerId);
  if (! allerA) return;
  var undoText = "Delete Allergy \"" + allerA.innerText + "\"";
  pushAction("deleteAllergy('" + allerId + "')", undoText);
  var div = allerA.parentElement;
  var allerQ = div.parentElement;
  allerQ.removeChild(div);
  var allerAddId = "q_" + aq.id + "o";
  allerAdd = $(allerAddId);
  allerAdd.style.display = "";
}
