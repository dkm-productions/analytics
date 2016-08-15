//var medQ;  // <span> parent element of clones
var medA;  // existing med anchor, null if new
var medAdd;  // add trigger, null if triggered by existing
var nextAddId;  // must be reset on clear
var rxKids;  // divs holding meds to include in RX

// Initialize vars
function medReset() {
  nextAddId = 0;
  rxKids = {};
}

// Show med popup
function showPopMed() {
  if (event) 
    event.returnValue = false;
  if (session.closed) return;
  var medTrigger = event.srcElement;
  // medQ = medTrigger.parentElement;
  loadMedHistory(session.meds); 
  if (medTrigger.className != "clone") { 

    // Popup triggered by existing med (update)
    medAdd = null;
    medA = medTrigger;
    showMed(medA.id, medA.getAttribute('medName'), medA.getAttribute('medAmt'), medA.getAttribute('medFreq'), medA.getAttribute('medAsNeed'), medA.getAttribute('medMeals'), medA.getAttribute('medRoute'), medA.getAttribute('medLength'), medA.getAttribute('medDisp'));
  } else {

    // Popup triggered by "add med" link (add)
    medA = null;
    medAdd = medTrigger;
    showMed();
  }
}
function medOkCallback(m) {
  if (medA == null) {
    medPushAddAction(medAdd.id, m);
    addMed(medAdd.id, m.name, m.amt, m.freq, m.route, m.length, m.asNeeded, m.meals, m.disp);
  } else {
    var undoText = "Update Med \"" + m.name +"\"";
    pushAction("changeMed" + argJoin([medA.id, m.name, m.amt, m.freq, m.route, m.length, m.asNeeded, m.meals, m.disp]), undoText);
    changeMed(medA.id, m.name, m.amt, m.freq, m.route, m.length, m.asNeeded, m.meals, m.disp);
  }
}
function medPushAddAction(medAddId, m) {
  var undoText = "Insert Med \"" + m.name +"\"";
  pushAction("addMed" + argJoin([medAddId, m.name, m.amt, m.freq, m.route, m.length, m.asNeeded, m.meals, m.disp]), undoText);
}
function medDeleteCallback() {
  var undoText = "Remove Med \"" + medA.getAttribute('medName') +"\"";
  pushAction("deleteMed('" + medA.id + "')", undoText);
  deleteMed(medA.id);  
}
function showPopRx() {
  event.returnValue = false;
  if (session.closed) return;
  var rxTrigger = event.srcElement;
  var id = (rxTrigger && rxTrigger.la) ? rxTrigger.la : null;
  var meds = createMedsFromRx(id);
  var r = {
      date:today,
      client:{
        name:session.cname,
        cbirth:session.cbirth},
      me:me,
      docs:docs,
      meds:meds};
  showRx(r);
}
function createMedFromLa(la) {
  return {
      name:la.medName,
      amt:la.medAmt,
      freq:la.medFreq,
      asNeeded:la.medAsNeed,
      meals:la.medMeals,
      route:la.medRoute,
      length:la.medLength,
      disp:la.medDisp,
      la:la};
}
function createMedsFromRx(triggerId) {
  var meds = [];
  if (triggerId) {
    meds.push(createMedFromLa($(triggerId)));
  } else {
    for (var kid in rxKids) {
      var rxKid = rxKids[kid];
      for (var i = 0; i < rxKid.children.length; i++) {
        var la = rxKid.children[i].med;
        var med = createMedFromLa(la);
        //if (la.id == triggerId) med.checked = true;
        meds.push(med);
      }
    }
  }
  return meds;
}
function rxCallback(meds) {
  var la;
  if (meds.length > 0) {
    for (var i = 0; i < meds.length; i++) {
      var m = meds[i];
      la = m.la;
      if (! la.ft) {
        medPushAddAction(medRefillId, m);
        la = addMed(medRefillId, m.name, m.amt, m.freq, m.route, m.length, m.asNeeded, m.meals, m.disp);
      }
      setFreeText(la.ftaid, m.rx, true);
    } 
    autosave();
    la.scrollIntoView(false);
  }
}

function getMedAddId(q) {
  return qidify(q.id) + 'o';
}
// Action methods (these are saved)
function clearNewCropMeds(q) {
  var medAddId = getMedAddId(q);
  var medAdd = $(medAddId);
  var medK = $(medAdd.getAttribute('kid'));
  if (medK.children) 
    clearMeds(medAddId);
}
function clearMeds(medAddId) {
  var medAdd = $(medAddId);
  var medK = $(medAdd.getAttribute('kid'));
  pushAction("clearMeds" + argJoin([medAddId]), null, true);
  clearChildren(medK);
  var q = questions[medAdd.getAttribute('qid')];
  if (q.ncmeds) {
    q.ncmeds = {};
  }
}
/*
 * Add meds returned from NewCrop 
 * @arg Question q where meds will be added
 * @arg Med[] meds 
 * @arg Question qOpposite where meds should/should not be duplicated (e.g. Plan Add Meds is opposite Current Meds) 
 * @arg bool requireDupe if qOpposite should have same med  
 */
function addNewCropMeds(q, meds, qOpposite, requireDupe) {
  clearNewCropMeds(q);
  q.ncmeds = {};
  var medAddId = getMedAddId(q);
  var medA;
  if (meds) {
    for (var i = 0; i < meds.length; i++) {
      var m = meds[i];
      var key = m.name + m.text;
      var dupe = qOpposite && qOpposite.ncmeds && qOpposite.ncmeds[key];
      dupe = dupe || false;
      if (qOpposite && requireDupe != dupe) {
        // 
      } else {
        pushAction("addMed" + argJoin([medAddId, m.name, m.amt, m.freq, m.route, m.length, m.asNeeded, m.meals, m.disp, m.text]), null, true);
        medA = addMed(medAddId, m.name, m.amt, m.freq, m.route, m.length, m.asNeeded, m.meals, m.disp, m.text);
        if (m.rx && medA.ftaid) {
          setFreeText(medA.ftaid, m.rx, true);
        }
        q.ncmeds[key] = 1;
      }
    }
  }
}
function addMed(medAddId, name, amt, freq, route, length, asNeeded, withMeals, disp, text) {
  name = denull(name);
  amt = denull(amt);
  freq = denull(freq);
  route = denull(route);
  length = denull(length);
  withMeals = withMeals;
  disp = denull(disp);
  var medAdd = $(medAddId);
  var medQ = medAdd.parentElement;
  var medK = $(medAdd.getAttribute('kid'));
  var div = document.createElement("div");
//  if (medQ.refillable == "1") {
//    rxKids[medAdd.getAttribute('kid')] = medK;  // medK will be included in RX  
//  }
  var q = questions[medAdd.getAttribute('qid')];
  if (q.ncmeds) {
    q.ncmeds[name + text] = 1;
  }
  medK.appendChild(div);
  var medA = document.createElement("a");
  //var br = document.createElement("br");
  //medQ.insertBefore(br, medAdd);
  medA.disp = (medQ.disp == "1"); 
  medA.innerText = "- " + parseMedText(medA.disp, name, amt, freq, route, length, asNeeded, withMeals, disp, text) + " ";
  if (q.erx) {
    medA.href = "javascript:";
    medA.style.color = 'black';
  } else {
    medA.href = "javascript:";
    medA.onclick = showPopMed;
  }
  medA.id = medAddId + nextAddId++;
  medA.className = "listAnchor";
  medA.setAttribute('medName', name);
  medA.setAttribute('medAmt', amt);
  medA.setAttribute('medFreq', freq);
  medA.setAttribute('medAsNeed', asNeeded);
  medA.setAttribute('medMeals', withMeals);
  medA.setAttribute('medRoute', route);
  medA.setAttribute('medLength', length);
  medA.setAttribute('medDisp', disp);
  //medQ.insertBefore(medA, medAdd);
  div.appendChild(medA);
  div.med = medA;
  //medA.br = br;  // direct ref to element (for deleting) 
  // Include anchor 
  // Add RX link
  if (medQ.rx == "1") {
    var rxA = document.createElement("a");
    rxA.innerText = "Print RX";
    rxA.href = ".";
    //rxA.id = medA.id + "rx";
    rxA.id = "rxanchor";
    rxA.attachEvent("onclick", showPopRx);
    rxA.className = "clone";
    rxA.setAttribute("la", medA.id);
    //medQ.insertBefore(rxA, medAdd);
    div.appendChild(rxA);
    //div.rx = rxA;  // direct ref to rx anchor (for deleting) 
  } 
  // Add FT
  var ft = document.createElement("span");
  ft.className = "v";
  ft.id = "ft";
  var ftaid = "med" + medA.id; 
  ft.innerHTML = freeTextAnchorHtml(ftaid);
  ft.style.display = (showFT) ? "inline" : "none";
  //medQ.insertBefore(ft, medAdd);
  if (medQ.rxFt != "1") {
    var wrapper = document.createElement('span');
    wrapper.className = 'h';
    wrapper.appendChild(ft);
    div.appendChild(wrapper);
  } else {
    div.appendChild(ft);
  }
  medA.ft = ft;
  medA.ftaid = ftaid + "ft";
  return medA;
}
function changeMed(medId, name, amt, freq, route, length, asNeeded, withMeals, disp) {
  if (disp == null) disp = "";
  var medA = $(medId);
  if (! medA) return;
  medA.innerText = "- " + parseMedText(medA.disp, name, amt, freq, route, length, asNeeded, withMeals, disp);
  medA.setAttribute('medName', name);
  medA.setAttribute('medAmt', amt);
  medA.setAttribute('medFreq', freq);
  medA.setAttribute('medAsNeed', asNeeded);
  medA.setAttribute('medMeals', withMeals);
  medA.setAttribute('medRoute', route);
  medA.setAttribute('medLength', length);
  medA.setAttribute('medDisp', disp);
}
function deleteMed(medId) {
  var medA = $(medId);
  if (! medA) return;
  var div = medA.parentElement;
  var medQ = div.parentElement;
  medQ.removeChild(div);
}
function parseMedText(showDisp, name, amt, freq, route, length, asNeeded, withMeals, disp, text) {
  var t = name;
  if (text) {
    t += " (" + text + ")";
  } else {
    if (amt != "") 
      t += " " + amt;
    if (freq != "") 
      t += " " + freq;
    if (route != "") 
      t += " " + route;
    if (asNeeded && asNeeded != '0') 
      t += " as needed";
    if (withMeals) 
      t += " with meals";
    if (length != "") 
      t += " for " + length;
  }
  if (showDisp) {
    if (disp != null && disp != "") 
      t += " Disp: " + disp;
  }
  return t;
}
