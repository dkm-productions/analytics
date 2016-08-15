/**
 * Template popups  
 */
var pq;
var pqMultFreeTable;
var pqSize;
var pqNoCurtain;
var pqUseLastPos;
var pqShowClearCmd;
var pqCallbackFn;
var pqDelCallbackFn;

var Q_TYPE_ALLERGY = 1;
var Q_TYPE_CALC = 2;
var Q_TYPE_CALENDAR = 3;
var Q_TYPE_FREE = 4;
var Q_TYPE_MED = 5;
var Q_TYPE_BUTTON = 6;
var Q_TYPE_COMBO = 7;
var Q_TYPE_DATA_HM = 8;
var Q_TYPE_HIDDEN = 9;

/*
 * Show question popup
 * - callback: optional, by default calls popQuestionCallback(q)
 * Returns top-element <div class='pop'> used
 */
function showQuestion(q, noCurtain, useLastPos, showClearCmd, callback, deleteCallback) {
  pq = q;
  /* This will work once QuestionPop can handle legacy med/allergy pops.
  q = Question.revive(q);
  QuestionPop.pop(q, function(q) {
    pq.sel = q.opts.sel;
    pq.unsel = q.opts.unsel;
    pq.del = q.opts.del;
    popQuestionCallback(pq);
  })
  return;
  */
  pqNoCurtain = noCurtain;
  pqUseLastPos = useLastPos;
  pqShowClearCmd = showClearCmd;
  if (callback) {
    pqCallbackFn = callback;
  } else {
    pqCallbackFn = popQuestionCallback;
  }
  if (deleteCallback) {
    pqDelCallbackFn = deleteCallback;
  } else {
    pqDelCallbackFn = null;
  }
  if (q.type) {
    switch (q.type) {
    case Q_TYPE_ALLERGY:
      pq.cbo = true;
      pq.csi = 4;
      pq.cboOnly = q.cboOnly || showClearCmd;
      break;
    case Q_TYPE_COMBO:
      pq.cbo = true;
      pq.csi = 0;
      pq.cboOnly = q.cboOnly || showClearCmd;
      break;
    case Q_TYPE_CALC:
      return showQuestionCalculator();
    case Q_TYPE_CALENDAR:
      return showQuestionCalendar();
    case Q_TYPE_FREE:
      return showQuestionFree();
    case Q_TYPE_MED:
      if (q.refill) {
        return showRxByQuestion(q, callback);
      } else {
        return showMedByQuestion(q, callback);
      }
    }
  }
  if (pq.opts.length == 2 && pq.loix != 0 && ! pq.mix) {
    pq.toggle = true;
  }
  if (pq.toggle && ! showClearCmd) {
    quiToggle();
    return;
  }
  pqBuildPop();
  return Pop._pop;
}
function pqCallback() {
  pqCallbackFn(pq);
}

// Question restore state functions
function qRestore(q, sel, del, sotext, motexts) {  // other texts only specified if selected
  if (q.mix != null) {
    qSetMulti(q, sel, del);
  } else {
    qSet(q, sel[0]);
  }
  var lsix = sel.length - 1;
  qAppendOtherOpts(q, sel[lsix]);
  if (sotext) {
    qChangeOptText(q, qSingleOtherIx(q), sotext);
  }
  if (motexts) {
    for (var i = lsix; motexts.length > 0; i--) {
      qChangeOptText(q, sel[i], motexts.pop());
    }
  }
}
function qRestoreArgs(q) {  // build serialized qRestore args for later restoration of q
  var other = qOtherTexts(q);
  var args = [];
  args.push(q.sel);
  args.push(q.del);
  args.push(other.single);
  args.push(other.multis);
  var s = toJSONString(args);
  return s.substring(1, s.length - 1);
}
function qSetByValue(q, value) {  // see if value exists in options; otherwise set free text
  if (q.opts == null) return;
  if (value == null) {
    q.sel = [];
    if (qIsFormattedPop(q)) {
      qChangeOptText(q, 0, '');
    }
  } else {
    if (qIsFormattedPop(q)) {
      qSetAndChangeOptText(q, 0, value);
    } else {
      var otherIx = qSingleOtherIx(q);
      var ix = qFindValue(q, value, 0, otherIx);
      if (ix > -1) {
        qSet(q, ix);
      } else {
        if (q.opts.length == 2 && q.loix == 1)  // e.g. a toggle question 
          otherIx = 2;
        qSetAndChangeOptText(q, otherIx, value);
      }
    }
  }
  return q.sel;
}
function qSetByValues(q, values) {
  if (values == null) {
    q.sel = [];
  } else if (! Array.is(values)) {
    qSetByValue(q, values)
  } else {
    var sel = [];
    for (var i = 0; i < values.length; i++) {
      var value = values[i];
      var ix = qFindValue(q, value, 0, q.opts.length - 2);
      if (ix == -1) {
        var ix = q.opts.length - 1;
        qChangeOptText(q, ix, value);
        qAppendOtherOpt(q);
      }
      sel.push(ix);
    }
    q.sel = sel;
    q.del = [];
    q.unsel = qCalcUnsel(q);
  }
}
function qSetByValueCombo(q, singleValue, multiValues) {
  q.cbo = true;
  var sel = qSetByValue(q, singleValue);
  if (multiValues) {
    for (var i = 0; i < multiValues.length; i++) {
      var value = multiValues[i];
      var ix = qFindValue(q, value, q.mix, q.opts.length - 2);
      if (ix == -1) {
        var ix = q.opts.length - 1;
        qChangeOptText(q, ix, value);
        qAppendOtherOpt(q);
      }
      sel.push(ix);
    }
  }
  q.sel = sel;
  q.unsel = [];
  q.del = [];
}
function qSet(q, ix) {
  q.sel = [ix];
  q.del = []; 
  q.unsel = qCalcUnsel(q);
}
function qSetMulti(q, sel, del) {
  q.sel = sel;
  q.del = del;
  q.unsel = qCalcUnsel(q);
}
function qReset(q) {
  q.sel = [];
  q.del = [];
  q.unsel = qCalcUnsel(q);
}
function qCalcUnsel(q) {  // based on q.ael and q.del settings
  var unsel = [];
  if (q.mix != null) {
    var sel = {arr:q.sel,ix:0,len:q.sel.length,value:q.sel[0]};
    var del = {arr:q.del,ix:0,len:q.del.length,value:((q.del.length>0)?q.del[0]:null)}; 
    for (var i = q.mix; i <= q.loix; i++) {
      if (i == sel.value) {
        arrnext(sel);
      } else if (i == del.value) {
        arrnext(del);
      } else {
        unsel.push(i);
      }
    }
  }
  return unsel;
}
function arrnext(a) {
  a.ix++;
  a.value = (a.ix < a.len) ? a.arr[a.ix] : null;  
}
function qSetFormattedOption(q, text) {  // set opt[0] text
  qSetAndChangeOptText(q, 0, text);
}
function qSetAndChangeOptText(q, ix, text) {
  qSet(q, ix);
  qChangeOptText(q, ix, text);
}
function qChangeOptText(q, ix, text) {
  qAppendOtherOpts(q, ix);
  if (text != qOptText(q.opts[ix])) {
    q.opts[ix].text = text;
    q.opts[ix].uid = text;
    q.opts[ix].blank = null;
  }
}

// Question functions
function qSelText(q) {  // return selection option text
  if (q.opts == null) return null;
  if (q.cbo) {
    var text = qOptText(q.opts[q.sel[0]]);
    if (q.sel.length > 1) {
      text += ': ' + qStringOptText(q.opts, qSel(q).slice(1), true);
    }
    return text;
  } else {
    return qStringOptText(q.opts, qSel(q), true);
  }
}
function qComboText(q) {
  var single = (q.sel.length > 0) ? qOptText(q.opts[q.sel[0]]) : null;
  var multi = [];
  if (q.sel.length > 1) {
    multi = qSel(q).slice(1);
    for (var i = 0; i < multi.length; i++) {
      multi[i] = qOptText(q.opts[multi[i]]);
    }
  }
  return {'single':single, 'multi':multi};  
}
function qSelUid(q) {  // return selected option UID
  return q.opts[qSel(q)].uid;
}
function qOptText(o) {
  return (o.text) ? o.text : o.uid;
}
function qOtherTexts(q) {  // return selected other texts
  var sotext = null;
  var motexts = [];
  if (q.type != Q_TYPE_BUTTON) {
    var soix = qSingleOtherIx(q);
    for (var i = 0; i < q.sel.length; i++) {
      var oix = q.sel[i];
      if (oix > q.loix) {
        motexts.push(q.opts[oix].text);
      } else if (oix == soix) {
        sotext = q.opts[oix].text;
      }
    }
  }
  return {
      single:sotext,
      multis:motexts
      };
}
function qFormattedOptText(q) {
  return qOptText(q.opts[0]);
}
function qIsSel(q, ix) {
  return qSel(q).has(ix);
}
function qIsDel(q, ix) {
  return Array.is(q.del) && q.del.has(ix);
}
function qIsMultiSel(q, sel) {  // sel optional, returns true if a multi is selected
  sel = sel || qSel(q);
  if (q.mix != null) {
    for (var i = 0; i < sel.length; i++) {
      if (sel[i] >= q.mix) {
        return true;
      }
    }
  }
  return false;
}
function qSel(q) {
  return (q.sel.length == 0) ? q.def : q.sel;
}
function qSelOpts(q) {  // [opt,..]
  var opts = [];
  var indexes = qSel(q);
  for (var i = 0; i < indexes.length; i++) {
    var opt = q.opts[indexes[i]];
    opt.oix = indexes[i];
    opts.push(opt);
  }
  return opts;

}
function qOpts(q, indexes) {  // return opts from index array
  var opts = [];
  if (indexes) {
    for (var i = 0; i < indexes.length; i++) {
      opts.push(q.opts[indexes[i]]);
    }
  }
  return opts;
}
function qFindValue(q, value, startIndex, endIndex, uidOnly) {
  for (var i = startIndex; i <= endIndex; i++) {
    if ((! uidOnly && q.opts[i].text == value) || q.opts[i].uid == value) {
      return i;
    }
  }  
  return -1;
}
function qSingleOtherIx(q) {  // appropriate for combos and single-only
  if (qIsFormattedPop(q)) 
    return 0;
  return (q.cbo) ? q.mix - 1 : (q.mix != null) ? null : q.opts.length - 1;
}
function qAppendOtherOpts(q, ix) {
  for (var i = q.opts.length; i <= ix; i++) {
    qAppendOtherOpt(q);
  }
}
function qAppendOtherOpt(q) {
  q.opts.push({'uid':'other', 'desc':'other', 'text':'other'});
}
function qStringOptText(opts, oixs, fromSel, listType) {
  return qJoinOptText(qOptTextArray(opts, oixs, 0), fromSel, listType);
}
function qSelTextArray(q) {
  return qOptTextArray(q.opts, qSel(q), 0);
}
function qOptTextArray(opts, oixs, startIndex) {
  var v = [];
  for (var i = startIndex; i < oixs.length; i++) 
    v.push(qOptText(opts[oixs[i]]));
  return v;
}
function qOptUidArray(opts, oixs, startIndex) {
  var v = [];
  for (var i = startIndex; i < oixs.length; i++) 
    v.push(opts[oixs[i]].uid);
  return v;
}
function qClear(q) {
  qSetByValue(q, null);
}
function qJoinOptText(v, fromSel, listType) {
  if (listType == 3) {
    return v.join(' ');
  } else if (listType == 1 || listType == 2) {
    return v.join('<br/>');
  }
  switch (v.length) {
    case 1: 
      return v[0];
    case 2:
      return v.join(qAndOr(fromSel));
  }
  last = v.pop();
  return v.join(', ') + qAndOr(fromSel) + last;
}
function qAndOr(useAnd) {
  return useAnd ? ' and ' : ' or ';
}
function qIsFormattedPop(q) {
  return (q.type == Q_TYPE_CALC || q.type == Q_TYPE_CALENDAR || q.type == Q_TYPE_FREE);
}
function qGenderFix(q, isMale) {
  if (q.bt) q.bt = genderFix(q.bt, isMale);
  if (q.at) q.at = genderFix(q.at, isMale);
  if (q.btms) q.btms = genderFix(q.btms, isMale);
  if (q.atms) q.atms = genderFix(q.atms, isMale);
  if (q.btmu) q.btmu = genderFix(q.btmu, isMale);
  if (q.atmu) q.atmu = genderFix(q.atmu, isMale);
  if (q.opts) {
    for (var i = 0; i < q.opts.length; i++) {
      q.opts[i].text = genderFix(q.opts[i].text, isMale); 
    }
  }
}
function calculateBmi(qBmi, qW, qWu, qH, qHu) {
  if (qW.sel.length == 0 || qH.sel.length == 0) return null;  // height/weight not entered
  return calculateBmiValues(qFormattedOptText(qW), qSelUid(qWu), qFormattedOptText(qH), qSelUid(qHu)); 
}

// JSON builders
 function newCalendarQuestion(desc) {
  return {
      id:'0',
      uid:'%date',
      type:Q_TYPE_CALENDAR,
      calFmt:CAL_FMT_SENTENCE,
      desc:(desc) ? desc : 'Date',
      sel:[],
      unsel:[],
      def:[0],
      opts:[{uid:'Date',text:'(date)'}],
      loix:0
      };
}
function newFreetextQuestion(desc) {
  return {
      id:'0',
      uid:'$free',
      type:Q_TYPE_FREE,
      desc:(desc) ? desc : 'Free Text',
      sel:[],
      unsel:[],
      def:[0],
      opts:[{uid:'',text:''}],
      loix:0
      };
}
function newMedQuestion(desc) {
  return {
      id:'0',
      uid:'@med',
      type:Q_TYPE_MED,
      desc:(desc) ? desc : 'Medicine',
      sel:[],
      unsel:[],
      def:[0],
      opts:[{uid:'',text:''}],
      loix:0
      };
}

// Question UI functions
function quiSetOpt(ix) {
  qSet(pq, ix);
  if (pq.cbo && ix >= pq.csi) {
    pqShowComboPage();
    return;
  }
  Pop.close();
  pqCallback();
}
function quiToggle() {
  qSet(pq, qIsSel(pq, 0) ? 1 : 0);
  pqCallback();
}
function quiSetSingleOther(ix) {
  var text = value('q-free');
  qSetAndChangeOptText(pq, ix, text);
  if (pq.cbo) {
    pqShowComboPage();
    return;
  }
  Pop.close();
  pqCallback();
}
function quiSetMulti() {
  var o = pqParseMultiChecks('ck');
  if (pq.cbo) {
    o.sel.unshift(pq.sel[0]);  // prepend with single-combo selection
  }
  qSetMulti(pq, o.sel, o.del);
  pqTestMultiOtherTextChange('otb');
  Pop.close();
  pqCallback();
}
function quiDelete() {
  var s = (pq.type == Q_TYPE_ALLERGY) ? 'allergy?' : 'entry?';
  Pop.Confirm.showYesNoCancel('Are you sure you want to remove this ' + s, quiDeleteConfirmed);
}
function quiDeleteConfirmed(confirmed) {
  if (confirmed) {
    Pop.close();
    if (pqDelCallbackFn) {
      pqDelCallbackFn(pq);
    } else {
      popQuestionDeleteCallback(pq);
    }
  } 
}
function quiCancel() {
  Pop.close();
}
function quiMultFreeClick() {
  var e = event.srcElement;
  if (e) {
    if (e.input) {
      quiCheckOther(e);
    } else if (e.check) {
      quiOtherClick(e);
    }
  }
}
function quiCheckOther(c) {
  if (c.checked) {
    var rows = pqMultFreeTable.rows();
    if (rows <= 9 && c.ix == pq.loix + rows) {
      pqAppendMultFree();
      pqResizeControls(true);
    }
    quiOtherFocus(c.input);
  }
  pqCheckOtherStyle(c);
  pqCheckApplyStyle();
}
function quiOtherClick(i) {
  if (i.check.checked) {
    quiOtherFocus(i);
  } else {
    i.check.checked = true;
    quiCheckOther(i.check);
  }
}
function quiOtherFocus(i) {
  i.focus();
  i.select();
}
function quiOtherBlur(i) {
  var t = i.value;
  i.value = '';
  i.value = t;
}
function quiMultiClick() {
  var e = event.srcElement;
  if (e) {
    if (e.anchor) {
      quiCheck(e);
    } else if (e.check) {
      quiLabelCheck(e);
    }
  }
}
function quiCheck(c) {
  c.focus();
  c.select();
  pqCheckToggle(c);
  pqCheckApplyStyle();
}
function quiLabelCheck(a) {
  quiCheck(a.check);
}
function pqBuildPop() {
  var fe;
  pqResizeControls(false);
  _$('pop-q-cap-text').setText(pq.desc);
  pqCalcSize();
  _$('pop-q-clear').showIf(pqShowClearCmd);
  if (pq.cbo) {
    if (pqSize.cbosingcols && ! pq.cboOnly) {
      _$('pop-csi-sing').show();
      _$('pop-cbo-sing-cap').setText(pqMultCaption());
      pqLoadOptsTable('pop-csi-sing-tbody', 0, pq.csi - 1, pqSize.cbosingcols);
    } else {
      _$('pop-csi-sing').hide();
    }
    _$('pop-q-optlist').hide();
    _$('pop-q-sing-opts').show();
    _$('pop-q-free').show();
    _$('pop-q-mult').show();
    _$('pop-q-mult-cap').hide();
    _$('pop-cbo-head').show();
    pqLoadMultOptsTable(pqSize.mcols);
    pqLoadComboPage1();
  } else {
    _$('pop-csi-sing').hide();
    _$('pop-cbo-head').hide();
    _$('pop-q-mult-cap').show();
    if (pq.multiOnly) {
      _$('pop-q-sing-opts').hide();
      _$('pop-q-optlist').hide();
    } else {
      if (pqSize.scols == 0) {
        _$('pop-q-sing-opts').hide();
        _$('pop-q-optlist').show().className = (pqSize.mopts > 30) ? 'optionsList optionsListSmall' : 'optionsList';
        fe = pqLoadOptsList(0, pqSize.sopts - 1);
      } else {
        _$('pop-q-optlist').hide();
        if (pqSize.sopts > 0) {
          _$('pop-q-sing-opts').show();
          fe = pqLoadOptsTable('pop-q-sing-tbody', 0, pqSize.sopts - 1, pqSize.scols);
        } else {
          _$('pop-q-sing-opts').hide();
        }
      }
    }
    if (pqSize.mopts == 0) {
      _$('pop-q-mult').hide();
      if (pq.toggle || pq.type == Q_TYPE_BUTTON) {
        _$('pop-q-free').hide();
      } else {
        _$('pop-q-free').show();
        pqLoadSingleOther(pqSize.sopts, 'Insert Free Text');
      }
    } else {
      _$('pop-q-free').hide();
      _$('pop-q-mult').show();
      _$('pop-q-mult-cap').setText(pqMultCaption());
      pqLoadMultOptsTable(pqSize.mcols);
    }
  }
  if (pq.clone) {
    _$('q-mult-apply').hide();
    _$('q-clone-cmd').show();
    _$('q-delete-span').showIf(pq.cix);
  } else {
    _$('q-mult-apply').show();
    _$('q-clone-cmd').hide();
  }
  _$('pop-cbo-p1').show();
  _$('pop-cbo-p2').show();
  pqShowPop(function() {
    if (fe) {
      fe.focus();
    }
    if (pq.cbo) {
      pqShowComboPage(pq.sel.length == 0 || pq.sel[0] < pq.csi);
      Pop.center();
    }
  });
}
function pqClear() {
  qClear(pq);
  Pop.close();
  pqCallback();
}
function pqLoadSingleOther(ix, caption) {
  var fn = quiSetSingleOther.curry(ix);
  Html.InputText.$('q-free').setValue(qOptText(pq.opts[ix])).bubble('onkeypresscr', fn).className = qIsSel(pq, ix) ? 'sel' : '';
  _$('q-cmd-free').setText(caption).onclick = fn;
}
function pqCalcSize() {
  var sopts = (pq.mix != null) ? pq.mix : pq.loix + 1;
  var mopts = (pq.mix != null) ? pq.opts.length - pq.mix : 0;
  if (pq.cbo) {
    sopts--;  // make room for single other
    if (pq.csi) {
      sopts -= pq.csi;  // remove non-combo singles
    }
  }
  var scols = 3;
  var mcols = 1;
  if (mopts > 60) {
    mcols = 4;
    scols = 6;
  } else if (mopts > 20) {
    mcols = 3;
    scols = 5;
  } else if (mopts > 10) {
    mcols = 2;
    scols = 4;
  } else if (sopts > 10) {
    scols = 4;
  }
  var cbosingcols = (pq.csi) ? scols : null;
  if (! pq.cbo && sopts > 16) {
    scols = 0;  // use optionList
  }
  pqSize = {
    'sopts':sopts,  // number of single options
    'scols':scols,  // number of columns for single opts (0=optionList)
    'cbosingcols':cbosingcols,  // number of columns for single options not part of combo
    'mopts':mopts,  // number of multi options
    'mcols':mcols   // number of columns for multi checks
    };
}
function pqShowPop(callback) {
  _$('pop-q-mult-scrolling').setClass('scrolling').style.height = '';
  var pos = (pqUseLastPos) ? Pop.POS_LAST : Pop.POS_CENTER;
  Pop.show('pop-q', null, pos, function() {
    pqResizeControls(true);
    _$('pop-q-optlist').scrollTop = 0;
    scrollToTr('opt-sel', -2);
    if (callback)
      callback();
  });
}
function pqResizeControls(set) {
  var dim = _$('pop-q').getDim();
  var ms = _$('pop-q-mult-scrolling');
  var w = dim.width;
  var h = dim.height;
  if (set) {
    var d = document.documentElement.clientHeight;
    if (ms.className == '' || ms.style.height == '') {
      if (h > d) {
        ms.className = 'scrolling';
        var j = ms.getHeight() - (h - d);
        if (j > 0) ms.setHeight(j);
      } else {
        ms.className = '';
      }
    }
  }
  _$('pop-q-cap').setWidth((set) ? w : null);
  _$('pop-q-clear').setWidth((set) ? w - 10 : null);
  _$('pop-csi-sing').setWidth((set) ? w - 10 : null);
  _$('pop-q-sing').setWidth((set) ? w - 10 : null);
  _$('pop-q-mult').setWidth((set) ? w - 10 : null);
  _$('q-free').setWidth((set) ? w - 15 : null);
  _$('q-cmd-free').setWidth((set) ? w - 20 : null);
  _$('q-mult-apply').setWidth((set) ? w - 35 : null);
  _$('pop-q-optlist').setWidth((set) ? w - 20 : null);
  var is = $$$2('otb', _$('pop-q-multfree-tbody'), 'INPUT');
  for (var i = 0; i < is.length; i++) 
    _$(is[i]).setWidth((set) ? w - 55 - 17 : null);
}
function pqLoadComboPage1() {
  pqLoadOptsTable('pop-q-sing-tbody', pq.csi, pq.mix - 2, pqSize.scols);
  pqLoadSingleOther(pq.mix - 1, 'Select Free Text');
}
function pqShowComboPage(first) {
  if (first) {
    pqLoadComboPage1();
    _$('pop-cbo-p1').show();
    _$('pop-cbo-p2').hide();
  } else {
    if (pq.type == Q_TYPE_ALLERGY) {
      _$('pop-q-cbo-cap').setText('Reactions for ' + qOptText(pq.opts[pq.sel[0]]) + ':');       
    } else {
      var mc = (pq.mc) ? pq.mc : 'Select';
      _$('pop-q-cbo-cap').setText(mc + ' for ' + qOptText(pq.opts[pq.sel[0]]) + ':');
    }
    _$('pop-cbo-p1').hide();
    _$('pop-cbo-p2').show();
  }
}
function pqLoadOptsTable(tbodyId, startIndex, endIndex, cols) {
  var focus = null;
  var rows = Math.ceil((endIndex - startIndex + 1) / cols);
  var ix = startIndex;
  var t = new TableLoader(tbodyId);
  for (var r = startIndex; r <= endIndex; r += cols) {
    t.createTr();
    for (var c = 0; c < cols; c++) {
      t.createTd();
      if (ix <= endIndex) {
        var a = pqCreateOptAnchor(ix);
        t.append(a);
        if (a.className == 'sel') {
          focus = a;
        }
        ix++;
      }
    }
  }
  return focus;
}
function pqLoadMultOptsTable(cols) {
  var rows = Math.ceil((pq.loix - pq.mix + 1) / cols);
  var ix = pq.mix;
  var t = new TableLoader('pop-q-mult-tbody');
  for (var r = 0; r < rows; r++) {
    t.createTr();
    for (var c = 0; c < cols; c++) {
      t.createTd();
      var i = ix + c * rows;
      if (i <= pq.loix) {
        pqAppendMultCheck(t, i, 'ck');
      }
     }
    ix++;
  }
  pqLoadMultFreeTable();
  pqCheckApplyStyle();
}
function pqAppendMultCheck(t, ix, id) {
  var c = pqCreateCheck(ix, id);
  var a = createAnchor(null, 'javascript:', null, null, pq.opts[ix].uid);
  t.append(c);
  t.createTdAppend('mult', a);
  c.anchor = a;
  a.check = c;
  pqCheckStyle(c);
}
function pqLoadMultFreeTable() {
  pqMultFreeTable = new TableLoader('pop-q-multfree-tbody');
  if (pq.type == Q_TYPE_BUTTON) {
    return;
  }
  var text;
  for (var ix = pq.loix + 1, j = pq.opts.length; ix < j; ix++) {
    text = qOptText(pq.opts[ix]);
    pqAppendMultFree(qIsSel(pq, ix), text);
  }
  if (text != 'other') {
    pqAppendMultFree();
  }
}
function pqAppendMultFree(checked, text) {
  pqMultFreeTable.createTrTd();
  var c = createCheckbox('ck');
  var i = createInput('otb', 'text', (text) ? text : 'other');
  pqMultFreeTable.append(c);
  pqMultFreeTable.createTdAppend(null, i);
  if (checked) c.checked = true;
  c.input = i;
  c.ix = pq.loix + pqMultFreeTable.rows();
  i.check = c;
  i.onblur = new Function('quiOtherBlur(this)');
  pqCheckOtherStyle(c);
}
function pqParseMultiChecks(id) {
  var cs = $$$2(id, _$('pop-q-mult-opts'), 'INPUT');
  var settingsByIx = {};
  var lastIx = cs[cs.length - 1].ix;
  for (var i = 0; i < cs.length; i++) {  // to sort elements by ix
    var ix = cs[i].ix;
    settingsByIx[ix] = cs[i].setting;
    if (ix > lastIx) lastIx = ix;
  }
  var o = {'sel':[], 'del':[]};
  for (var ix = pq.mix; ix <= lastIx; ix++) {
    var setting = settingsByIx[ix];
    switch (setting) {
    case 1:
      o.sel.push(ix);
      break;
    case 2:
      o.del.push(ix);
    }
  }
  return o;
}
function pqTestMultiOtherTextChange(id) {
  var is = $$$2(id, _$('pop-q-multfree-tbody'), 'INPUT');
  for (var j = 0; j < is.length; j++) {
    var i = is[j];
    if (i.check.checked) {
      qChangeOptText(pq, i.check.ix, i.value);
    }
  }
}
function pqCheckOtherStyle(c) {
  if (c.checked) {
    c.input.className = 'sel';
    c.setting = 1;
  } else {
    c.input.className = '';
    c.setting = 0;
  }
}
function pqCreateCheck(ix, id) {
  var c = createCheckbox(id);
  if (pq.tristate) {  // possible settings: 0 (unchecked), 1 (checked), 2 (checked+strikethru)
    c.tristate = true;
    c.setting = (qIsDel(pq, ix)) ? 2 : (qIsSel(pq, ix)) ? 1 : 0; 
  } else {  // possible settings: 0 (unchecked), 1 (checked)
    c.setting = (qIsSel(pq, ix)) ? 1 : 0;
  }
  c.ix = ix;
  return c;
}
function pqCheckToggle(c) {
  c.setting++;
  if ((c.tristate && c.setting > 2) || (! c.tristate && c.setting > 1)) {
    c.setting = 0;
  }
  pqCheckStyle(c);
}
function pqCheckApplyStyle() {
  var cks = getCheckedValues('ck', 'pop-q-mult-opts');
  setDisabled('q-mult-apply', cks.length == 0);
}
function pqCheckStyle(c) {
  var a = c.anchor;
  switch (c.setting) {
  case 0:
    c.checked = false;
    a.className = '';
    break;
  case 1:
    c.checked = true;
    a.className = 'sel';
    break;
  case 2:
    c.checked = true;
    a.className = 'del';
    break;
  }
}
function pqCreateOptAnchor(ix) {
  var cls;
  if (pq.sel.length == 0 && pqShowClearCmd) {
    cls = ''; 
  } else {
    cls = (qIsSel(pq, ix)) ? 'sel' : '';
  }
  var href = 'javascript:quiSetOpt(' + ix + ')';
  return createAnchor(null, href, cls, pq.opts[ix].uid);
}
function pqMultCaption() {
  if (! pq.mc) {
    return (pq.mix > 0) ? 'Or, check any that apply:' : 'Check any that apply:';  
  }
  var mc = removeHtmlFormatting(pq.mc);
  return (mc.substr(mc.length - 1) == ':') ? mc : mc + ':';
}
function pqLoadOptsList(startIndex, endIndex) {
  var focus = null;
  var ul = _$('pop-q-optlist-ul').clean();
  for (var ix = startIndex; ix <= endIndex; ix++) {
    var a = pqCreateOptAnchor(ix);
    ul.li().appendChild(a);
    if (a.className == 'sel') {
      a.id = 'opt-sel';
      focus = a;
    }
  }
  return focus;
}
/** 
 * Question: Calculator
 */
var qcValue = '0';
var qcWasPreset = false;
var qcLbs = false;
function showQuestionCalculator() {
  _$('pop-q-calc-cap-text').setText(pq.desc);
  qcSetPreset(qFormattedOptText(pq));
  if (pq.uid == '#Weight') {
    _$('disp-declbs').show();
    if (isChecked('disp-lbs')) {
      qcLbs = false;
      lbsdecClick();
    }
  } else {
    _$('disp-declbs').hide();
    qcLbs = false;
  }
  _$('pop-calc-clear').showIf(pqShowClearCmd);
  return Pop.showPosCursor('pop-q-calc', 'calckey7', true, pqNoCurtain, pqUseLastPos);
}
function qcSetPreset(value) {
  if (value == 'N/A') {
    qcValue = '';
  } else {
    qcValue = value.replace(/[^\d.-]/g,'');
    if (qcValue.length == 0) {
      qcValue = '0';
    }
  }
  qcDisplay();
  qcWasPreset = true;
  
}
function qcKey() {
  switch (event.keyCode) {
  case 48:
    qcPush('0');
    break;
  case 49:
    qcPush('1');
    break;
  case 50:
    qcPush('2');
    break;
  case 51:
    qcPush('3');
    break;
  case 52:
    qcPush('4');
    break;
  case 53:
    qcPush('5');
    break;
  case 54:
    qcPush('6');
    break;
  case 55:
    qcPush('7');
    break;
  case 56:
    qcPush('8');
    break;
  case 57:
    qcPush('9');
    break;
  case 96:
    qcPush('0');
    break;
  case 97:
    qcPush('1');
    break;
  case 98:
    qcPush('2');
    break;
  case 99:
    qcPush('3');
    break;
  case 100:
    qcPush('4');
    break;
  case 101:
    qcPush('5');
    break;
  case 102:
    qcPush('6');
    break;
  case 103:
    qcPush('7');
    break;
  case 104:
    qcPush('8');
    break;
  case 105:
    qcPush('9');
    break;
  case 110:
    qcPush('.');
    break;
  case 8:
    qcPush('b');
    break;
  case 190:
    qcPush('.');
    break;
  case 13:
    qcOk();
    break;
  case 78:
    qcPush('n');
    break;
  case 67:
    qcPush('c');
    break;
  }
}
function qcPush(v) {
  if (v == 'c') {
    qcValue = '0';
  } else if (v == 'n') {
    qcValue = '';
  } else if (v == '-') {
    if (qcValue.substring(0, 1) == '-') {
      qcValue = qcValue.substring(1);
    } else {
      qcValue = '-' + qcValue;
    }
  } else if (v == 'b') {
    qcValue = qcValue.substring(0, qcValue.length - 1);
    if (qcValue.length == 0) {
      qcValue = '0';
    }
  } else {
    if (qcValue.length < 10) {
      if (qcWasPreset) {
        qcValue = '0';
      }
      if (v == '.') {
        if (qcValue.indexOf('.') == -1) {
          qcValue += v;
        }
      } else {
        if (qcValue == '0') {
          qcValue = v;
        } else if (qcValue == '-0') {
          qcValue = '-' + v;
        } else {
          qcValue += v;
        }
      }
    }
  }
  qcWasPreset = false;
  qcDisplay();
}
function qcOk() {
  if (qcLbs) {
    lbsdecClick(true);
  }
  qSetFormattedOption(pq, text('pop-calc-display'));
  Pop.close();
  pqCallback();
}
function qcCancel() {
  Pop.close();
}
function qcDisplay() {
  if (qcValue.length == 0) {
    _$('pop-calc-display').setText('N/A');
  } else {
    var text = (qcLbs) ? showLbs(qcValue) : addCommas(qcValue);
    _$('pop-calc-display').setText(text);
  }
}
function addCommas(n) {
  var x = n.split('.');
  var x1 = x[0];
  var x2 = x.length > 1 ? '.' + x[1] : '';
  var rgx = /(\d+)(\d{3})/;
  while (rgx.test(x1)) {
    x1 = x1.replace(rgx, '$1' + ',' + '$2');
  }
  return x1 + x2;
}
function lbsdecClick(override) {
  var lbs = (override) ? false : isChecked('disp-lbs');
  if (lbs != qcLbs) {
    var x = qcValue.split('.');
    var x1 = val(x[0]);
    var x2 = (x.length > 1) ? val(x[1]) : 0;
    if (lbs) {
      qcValue = x1 + '.' + (parseFloat('.' + x2) * 16);
    } else {
      qcValue = '' + (x1 + (x2 / 16));
    }
    qcLbs = lbs;
    qcDisplay();
  }
}
function showLbs(n) {
  var x = n.split('.');
  var x2 = '';
  if (x.length > 1) {
    if (x[1] == '') {
      x2 = '0oz';
    } else {
      x2 = val(x[1]) + 'oz';
    }
  }
  return x[0] + 'lbs ' + x2;
}

// Question: Freetext
function showQuestionFree() {
  return showFreetext(questionFreeCallback, qFormattedOptText(pq), pq.desc);
}
function questionFreeCallback(text) {
  qSetFormattedOption(pq, String.trim(text));
  pqCallback();  
}

// Question: Calendar
var m_today;
var m_now;  
var m_setting;  // Date preset
var m_month;  // Selected month
var m_year;  // Selected year
var m_fmt;  // see CAL_FMT
var CAL_FMT_NONE = 0;      // 'December 12, 2010',    'December of 2010'     '2010'
var CAL_FMT_SENTENCE = 1;  // 'on December 12, 2010'  'in December of 2010' 'in 2010'  
var CAL_FMT_ENTRY = 2;     // '12-Dec-2010'           'Dec 2010'             '2010'
var M_START_YEAR = 1910;
var ONE_DAY =  86400000;

function showQuestionCalendar() {
  if (Object.isUndefined(pq.calFmt)) 
    setCalFmt();
  m_fmt = pq.calFmt;
  _$('pop-q-calendar-cap-text').setText(pq.desc);
  mcalPreset(qFormattedOptText(pq));
  _$('pop-cal-clear').showIf(pqShowClearCmd);
  _$('pop-cal-cancel').showIf(! pqShowClearCmd);
  return Pop.showPosCursor('pop-q-calendar', null, true, pqNoCurtain, pqUseLastPos);
}
function setCalFmt() {
  switch (pq.uid.substr(pq.uid.length - 1)) {
    case '%':
      pq.calFmt = CAL_FMT_NONE
      break;
    case '_':
      pq.calFmt = CAL_FMT_ENTRY;
      break;
    default:
      pq.calFmt = CAL_FMT_SENTENCE;
      break;
  }
}
function calMouseScroll(year) {
  if (event && event.wheelDelta > 0) {
    if (year) {
      event.cancelBubble = true;
      m_year = m_year + 1;
      mformatCalendar();      
    } else {
      mnextMonth();
    }
  } else {
    if (year) {
      event.cancelBubble = true;
      m_year = m_year - 1;
      mformatCalendar();            
    } else {
      mprevMonth();    
    }
  }
}
function calParse(v, fmt) {  // return new Date() for supplied text
  var dv = new DateValue(v);
  var setting = dv.getDate();
  if (setting) {
    m_year = dv.getYear();
    m_month = dv.getMonth();
    day = dv.getDay();
  }
  return setting;
}
function mcalPreset(v) {
  m_now = new Date();
  m_today = new Date(m_now.getFullYear(), m_now.getMonth(), m_now.getDate());
  m_setting = calParse(v, m_fmt);
  if (m_setting == null || m_setting=='Invalid Date') {
    m_year = m_now.getFullYear();
    m_month = m_now.getMonth();
  }
  mcalLoadCombos();
  mformatCalendar();
}
function calClose(value) {
  if (value)
    qSetFormattedOption(pq, value);
  else
    qClear(pq);
  Pop.close();
  pqCallback();
}
function calUnk() {
  var text;
  switch (m_fmt) {
    case CAL_FMT_NONE:
      text = 'date unknown';
    case CAL_FMT_SENTENCE:
      text = 'on an unknown date';
    case CAL_FMT_ENTRY:
      text = null;
  }
  calClose(text);
  //calClose(UNKNOWN_TEXT);
}
function calMonthOnly() {
//  var value = aMonth.innerText + ' of ' + aYear.innerText;
//  if (m_fmt == CAL_FMT_SENTENCE) value = 'in ' + value;
  var dv = new DateValue([m_year, m_month]);
  calClose(dv.toString(m_fmt));
}
function calYearOnly() {
//  var value = aYear.innerText;
//  if (m_fmt == CAL_FMT_SENTENCE) value = 'in ' + value;
  var dv = new DateValue([m_year]);
  calClose(dv.toString(m_fmt));
}
function calOnClick() {
  td = window.event.srcElement;
  if (td.tagName == 'TD') {
    day = parseInt(td.innerText, 10);
    if (day > 0) {
      var dv = new DateValue([m_year, m_month, day]);
      calClose(dv.toString(m_fmt));
//      if (m_fmt == CAL_FMT_SENTENCE) {
//        var value = calFullDate(day);
//        calClose(value);
//      } else {
//        month = m_month + 1;
//        returnMonth = (month < 10) ? '0' + month : String(month);
//        returnDay = (day < 10) ? '0' + day : String(day);
//        var value = returnMonth + '/' + returnDay + '/' + m_year;
//        calClose(value);
//      }
    }
  }
}
function calFormatSqlDate(dt) {
  if (dt == null) return null;
  var m = dt.getMonth() + 1;
  var d = dt.getDate();
  var y = dt.getFullYear();
  return '' + y + '-' + lpad(m) + '-' + lpad(d);
}
function calFormatShortDate(dt) {
  if (dt == null) return null;
  var m = dt.getMonth() + 1;
  var d = dt.getDate();
  var y = dt.getFullYear();
  return lpad(m) + '/' + lpad(d) + '/' + y;
}
function calFormatFullDate(dt) {
  if (dt == null) return null;
  var m = dt.getMonth();
  var d = dt.getDate();
  var y = dt.getFullYear();
  return 'on ' + DateUi.getMonthName(m) + ' ' + d + ', ' + y;
}
function calFullDate(day) {
  var value = 'on ' + aMonth.innerText + ' ' + day + ', ' + aYear.innerText;
  return value;
}
function monMouseOut() {
  td = window.event.srcElement;
  if (td.tagName == 'TD') {
    if (td.className == 'styleSelected') {
      td.className = td.oldClassName;
    }
  }
}
function monMouseOver() {
  td = window.event.srcElement;
  if (td.tagName == 'TD') {
    if (parseInt(td.innerText, 10) > 0) {
      td.oldClassName = td.className;
      td.className = 'styleSelected';
    }
  }
}
function msetDateByCombos() {
  m_month = popcboMonth.selectedIndex;
  m_year = parseInt(popcboYear.value, 10);
  mformatCalendar();
}
function mcalLoadCombos() {
  popcboMonth.innerHTML = '';
  popcboYear.innerHTML = '';
  for (month = 0; month <= 11; month++) {
    popcboMonth.add(mcreateOption(mmonthName(month), month));
  }
  for (year = M_START_YEAR; year <= m_now.getFullYear() + 10; year++) {
    popcboYear.add(mcreateOption(year, year));
  }
}
function mcreateOption(text, value) {
  opt = document.createElement('OPTION');
  opt.text = text;
  opt.value = value;
  return opt;
}
function mformatCalendar() {
  aMonth.innerText = mmonthName(m_month);
  aYear.innerText = m_year;
  _$('calSelMonth').setText(aMonth.innerText + ' of ' + m_year);
  _$('calSelYear').setText(m_year);
  popcboMonth.selectedIndex = m_month;
  popcboYear.selectedIndex = m_year - M_START_YEAR;
  dFirstOfMonth = new Date(m_year, m_month, 1);
  dCounter = new Date(dFirstOfMonth.valueOf() - dFirstOfMonth.getDay() * ONE_DAY);
  for (row = 3; row <= 8; row++) {
    for (cell = 0; cell <= 6; cell++) {
      if (dCounter.getMonth() == m_month) {
        tblCal.rows[row].cells[cell].innerText = dCounter.getDate();
        if ((m_setting != null) && (dCounter.getMonth() == m_setting.getMonth()) && (dCounter.getFullYear() == m_setting.getFullYear()) && (dCounter.getDate() == m_setting.getDate())) {
          tblCal.rows[row].cells[cell].className = 'styleSetting';
        } else {
          if (dCounter.valueOf() == m_today.valueOf()) {
            tblCal.rows[row].cells[cell].className = 'styleToday';
          } else {
            if ((cell == 0) | (cell == 6)) {
              tblCal.rows[row].cells[cell].className = 'styleWeekend';
            } else {
              tblCal.rows[row].cells[cell].className = 'styleDay';
            }
          }
        }
      } else {
        tblCal.rows[row].cells[cell].innerHTML = '&nbsp;';
        tblCal.rows[row].cells[cell].className = 'styleOffDay';
      }
      dCounter.setDate(dCounter.getDate() + 1);
    }
    if (row == 7) {
      if (dCounter.getMonth() != m_month) {
        mhideExtraRow();
        return;
      }
    }
  }
}
function mprevMonth() {
  if (m_month == 0) {
    m_month = 11;
    m_year = m_year - 1;
  } else {
    m_month = m_month - 1;
  }
  mformatCalendar();
}
function mnextMonth() {
  if (m_month == 11) {
    m_month = 0;
    m_year = m_year + 1;
  } else {
    m_month = m_month + 1;
  }
  mformatCalendar();
}
function mhideExtraRow() {
  for (cell = 0; cell <= 6; cell++) {
    mtrExtraRow.cells[cell].className = 'styleHide';
    mtrExtraRow.cells[cell].innerHTML = '&nbsp;';
  }
}
function mmonthName(month) {
  switch (month) {
    case 0:
      return 'January';
    case 1:
      return 'February';
    case 2:
      return 'March';
    case 3:
      return 'April';
    case 4:
      return 'May';
    case 5:
      return 'June';
    case 6:
      return 'July';
    case 7:
      return 'August';
    case 8:
      return 'September';
    case 9:
      return 'October';
    case 10:
      return 'November';
    case 11:
      return 'December';
  }
}
function mmonthIndex(month) {
  if (month == 'January') return 0;
  if (month == 'February') return 1;
  if (month == 'March') return 2;
  if (month == 'April') return 3;
  if (month == 'May') return 4;
  if (month == 'June') return 5;
  if (month == 'July') return 6;
  if (month == 'August') return 7;
  if (month == 'September') return 8;
  if (month == 'October') return 9;
  if (month == 'November') return 10;
  if (month == 'December') return 11;
}

// Free text
// showFreetext(callback, text, caption, deletable)
// callback supplied with text (null if deleted)
var freeCallback;
var freeDeletable;
function showFreetext(callback, text, caption, deletable) {
  freeCallback = callback;
  freeDeletable = deletable;
  Html.InputText.$('freetext').setValue(text);
  _$('pop-free-cap-text').setText(caption);
  _$('free-delete-span').showIf(deletable);
  _$('pop-free-clear').showIf(pqShowClearCmd);
  return Pop.showPosCursor('pop-free', 'freetext', true, pqNoCurtain, pqUseLastPos);
}
function freeOk() {
  Pop.close();
  var text = value('freetext').replace(/(\r\n|[\r\n])/g, ' ').replace(/^\s+|\s+$/g, '');
  freeCallback(text);
}
function freeCancel() {
  Pop.close();
}
function freeDelete() {
  Pop.close();
  freeCallback(null);
}

// Medicine picker
// Before showing: loadMedHistory(meds)  // JDataMed[] 
// For updates: showMed(id, name, amt, freq, asNeeded, meals, route, length, disp) 
// For adds: showMed()
// Callbacks: medOkCallback(med)
//            medDeleteCallback(medId)
var medId;
var medHist = [];
/*
 * Alternate show method, using question
 * Supply q.med props for update, q.med=null for add 
 * Callbacks: callback(q)  // q.med will be updated for add/change, nulled for delete
 */
var medQ;
var medQCallback;
var medJustName = false;
function showMedByQuestion(q, callback) {  
  medQ = q;
  medQCallback = callback;
  medJustName = q.medNameOnly;
  var m = q.med;
  if (m) {
    return showMed(m.id, m.name, m.amt, m.freq, m.asNeeded, m.meals, m.route, m.length, m.disp, true);
  } else {
    return showMed(null, null, null, null, null, null, null, null, null, true);
  }
}
function showMed(id, name, amt, freq, asNeeded, meals, route, length, disp, fromQ) {  // pass nothing for 'add'
  if (! fromQ) {
    medQ = null;
    medQCallback = null;
    medJustName = false;
  }
  Html.InputText.$('medName').bubble('onfocus', medShow.curry('medName', 0));
  Html.InputText.$('medAmt').bubble('onfocus', medShow.curry('medAmt', 1));
  Html.InputText.$('medFreq').bubble('onfocus', medShow.curry('medFreq', 2));
  Html.InputText.$('medRoute').bubble('onfocus', medShow.curry('medRoute', 3));
  Html.InputText.$('medLength').bubble('onfocus', medShow.curry('medLength', 4));
  Html.InputText.$('medDisp').bubble('onfocus', onfocusMedDisp);
  if (id) {
    medId = id;
    Html.InputText.$('medName').setValue(name);
    Html.InputText.$('medAmt').setValue(amt);
    Html.InputText.$('medFreq').setValue(freq);
    Html.InputCheck.$('medAsNeed').setCheck(asNeeded);
    Html.InputCheck.$('medMeals').setCheck(meals);
    Html.InputText.$('medRoute').setValue(route);
    Html.InputText.$('medLength').setValue(length);
    //Html.InputText.$('medDisp').setValue(disp);
    _$('med-delete-span').show();
    doMedSearch();    
  } else {
    medId = null;
    Html.InputText.$('medName').setValue('');
    Html.InputText.$('medAmt').setValue('');
    Html.InputText.$('medFreq').setValue('');
    Html.InputCheck.$('medAsNeed').setCheck(false);
    Html.InputCheck.$('medMeals').setCheck(false);
    Html.InputText.$('medRoute').setValue('');
    Html.InputText.$('medLength').setValue('');
    Html.InputText.$('medDisp').setValue('');
    _$('med-delete-span').hide();    
  }
  showMedHistory();
  _$('popM2').hideIf(medJustName);
  Pop.showPosCursor('popMed', null, true, pqNoCurtain, pqUseLastPos);
  _$('medName').setFocus();
  if (fromQ) {
    return Pop._pop;
  }
}
function medShow(id, sel) {
  _$(id).select();
  for (var i = 0; i <= 4; i++) {
    var m = _$('m' + i);
    m.style.display = (sel == i) ? 'block' : 'none';
  }
}
function upMedName(a) {
  Html.InputText.$('medName').setValue(a.innerText);
  focus('medAmt');
}
function upMedAmt(a) {
  Html.InputText.$('medAmt').setValue(a.innerText);
  focus('medFreq');
}
function upMedFreq(a) {
  Html.InputText.$('medFreq').setValue(noBr(a.innerText));
  focus('medRoute');
}
function upMedRoute(a) {
  Html.InputText.$('medRoute').setValue(a.innerText);
  focus('medLength');
}
function upMedLength(a) {
  Html.InputText.$('medLength').setValue(a.innerText);
  //focus('medDisp');
  focus('medName');
}
function onfocusMedDisp() {
  if (value('medDisp') != '') return;
  Html.InputText.$('medDisp', calcMedDisp(value('medAmt'), value('medFreq')).setValue(value('medLength')));
  focus('medDisp');
}
function noBr(s) {
  return s.split('<br>')[0];
}
function calcMedDisp(amt, freq, length) {
  var a = amt;
  var i = 0.;
  var u = '';
  var cf = 5;
  // Determine period
  var l = length;
  var days = 30;  // assume 30 days
  if (l == '1 day') {
    days = 1;
  } else if (l == '2 days') {
    days = 2;
  } else if (l == '3 days') {
    days = 3;
  } else if (l == '4 days') {
    days = 4;
  } else if (l == '5 days') {
    days = 5;
  } else if (l == '6 days') {
    days = 6;
  } else if (l == '7 days') {
    days = 7;
  } else if (l == '10 days') {
    days = 10;
  } else if (l == '12 days') {
    days = 12;
  } else if (l == '14 days') {
    days = 14;
  } else if (l == '21 days') {
    days = 21;
  } else if (l == '28 days') {
    days = 28;
  } else if (l == '30 days') {
    days = 30;
  } else if (l == '60 days') {
    days = 60;
  } else if (l == '90 days') {
    days = 90;
  }
  if (a == '1/4') {
    i = 1/4;
  } else if (a == '1/3') {
    i = 1/3;
  } else if (a == '1/2') {
    i = 1/2;
  } else if (a == '1') {
    i = 1;
  } else if (a == '1 1/2') {
    i = 1.5;
  } else if (a == '2') {
    i = 2;
  } else if (a == '3') {
    i = 3;
  } else if (a == '4') {
    i = 4;
  } else if (a == '5') {
    i = 5;
  } else if (a == '6') {
    i = 6;
  } else if (a == '7') {
    i = 7;
  } else if (a == '8') {
    i = 8;
  } else {
    u = ' ml';
    if (a == '1/4 tsp') {
      i = 1/4 * cf;
    } else if (a == '1/3 tsp') {
      i = 1/3 * cf;
    } else if (a == '1/2 tsp') {
      i = 1/2 * cf;
    } else if (a == '3/4 tsp') {
      i = 3/4 * cf;
    } else if (a == '1 tsp') {
      i = cf;
    } else if (a == '1 1/4 tsp') {
      i = 5/4 * cf;
    } else if (a == '1 1/3 tsp') {
      i = 5/3 * cf;
    } else if (a == '1 1/2 tsp') {
      i = 3/2 * cf;
    } else if (a == '1 3/4 tsp') {
      i = 7/4 * cf;
    } else if (a == '2 tsp') {
      i = 2 * cf;
    } else if (a == '3 tsp') {
      i = 3 * cf;
    } else if (a == '0.4 ml') {
      i = 0.4;
    } else if (a == '0.5 ml') {
      i = 0.5;
    } else if (a == '0.8 ml') {
      i = 0.8;
    } else if (a == '1 ml') {
      i = 1;
    } else if (a == '1.2 ml') {
      i = 1.2;
    } else if (a == '1 1/2 ml') {
      i = 1.5;
    } else if (a == '1.6 ml') {
      i = 1.6;
    } else if (a == '2 ml') {
      i = 2;
    } else if (a == '2 1/2 ml') {
      i = 2.5;
    } else if (a == '3 ml') {
      i = 3;
    } else if (a == '3 1/2 ml') {
      i = 3.5;
    } else if (a == '4 ml') {
      i = 4;
    } else if (a == '4 1/2 ml') {
      i = 4.5;
    } else if (a == '5 ml') {
      i = 5;
    } else {
      return 'QS x ' + plural(days, 'day');
    }
  }
  // Calculate 1-day amt
  var f = freq;
  var m = 0.;
  if (f == 'every hour') {
    m = i * 24;
  } else if (f == 'every 2 hours') {
    m = i * 12; 
  } else if (f == 'every 3 hours') {
    m = i * 8; 
  } else if (f == 'every 4 hours') {
    m = i * 6; 
  } else if (f == 'every 6 hours') {
    m = i * 4; 
  } else if (f == 'every 8 hours') {
    m = i * 3; 
  } else if (f == 'every 12 hours') {
    m = i * 2; 
  } else if (f == 'daily') {
    m = i; 
  } else if (f == 'BID') {
    m = i * 2; 
  } else if (f == 'TID') {
    m = i * 3; 
  } else if (f == 'QID') {
    m = i * 4; 
  } else if (f == 'five times daily') {
    m = i * 5; 
  } else if (f == 'QAM') {
    m = i; 
  } else if (f == 'QHS') {
    m = i; 
  } else if (f == 'every 2 days') {
    m = i * 1/2; 
  } else if (f == 'every 3 days') {
    m = i * 1/3; 
  } else if (f == 'Mon/Thur') {
    m = i * 8/30; 
  } else if (f == 'MWF') {
    m = i * 12/30; 
  } else if (f == 'once weekly') {
    m = i * 4/30; 
  } else if (f == 'once monthly') {
    m = i * 1/30; 
  } else if (f == 'every 2 weeks') {
    m = i * 2/30; 
  } else if (f == 'every 10 days') {
    m = i * 3/30; 
  } else {
    return 'QS x ' + plural(days, 'day');
  }
  // Multiply by period
  m = m * days;
  m = Math.round(m * 100) / 100;
  return m + u;
}
function clearMedResults() {
  _$('medListNone').setText('');
  _$('medListUl').html('');
  _$('medListFoot').setText('');
  _$('medListTitle').html('<i>Type in a partial name and hit ENTER for results.</i>');
}

// When ENTER pressed, submit search
function testMedKey() {
  var kc = event.keyCode;
  if (kc == 13) {
    doMedSearch();
    return;
  } else if (kc > 31) {
    clearMedResults();
  }
}
function justMedName(name) {
  name = name.toUpperCase();
  if (name.indexOf('(') >= 0)
    return String.trim(name.toUpperCase().split(' (')[0]);
  words = name.split(' ');
  for (var i = 0, j = words.length; i < j; i++) {
    if (! isNaN(words[i].substr(0, 1)))
      break;
  }
  if (i < j)
    words.splice(i, j - i);
  return words.join(' ');
}
function doMedSearch() {
  var text = justMedName(value('medName'));
  if (text != '') {
    clearMedResults();
    _$('medListTitle').html('Searching for: <b>' + text + '</b>');
    _$('medListTitle').html('Searching for: <b>' + text + '</b>');
    sendRequest(2, 'action=searchMeds&id=' + text);
  }
}

// Action buttons
function medDelete() {
  Pop.Confirm.showYesNoCancel('Are you sure you want to remove this medication?', medDeleteConfirmed);
}
function medDeleteConfirmed(confirmed) {
  if (confirmed) {
    Pop.close();
    if (medQCallback) {
      medQ.med = null;
      medQCallback(medQ, true);    
    } else {
      medDeleteCallback(medId);
    }
  }
}
function medCancel() {
  Pop.close();
}
function medOk() {
  var name = String.trim(value('medName'));
  if (name == '') {
    Pop.Msg.showCritical('Cannot save without specifying medication name.');
    return;
  }
  Pop.close();
  var m = {
      'id':medId,
      'name':value('medName'),
      'amt':value('medAmt'),
      'freq':value('medFreq'),
      'route':value('medRoute'),
      'length':value('medLength'),
      'asNeeded':isChecked('medAsNeed'),
      'meals':isChecked('medMeals'),
      'disp':value('medDisp'),
      'text':null};
  m.text = medBuildText(m.amt, m.freq, m.route, m.length, m.asNeeded, m.withMeals, m.disp);
  if (medQCallback) {
    medQ.med = m;
    if (medQ.medNameOnly) 
      medQ.med.text = null;
    //var text = (medJustName) ? m.name : m.name + ': ' + m.text;
    //qSetFormattedOption(medQ, text);
    medQCallback(medQ);    
  } else {
    medOkCallback(m);
  } 
}
function medBuildText(amt, freq, route, length, asNeeded, withMeals, disp) {
  var t = '';
  if (! isBlank(amt)) {
    t += ' ' + amt;
  }
  if (! isBlank(freq)) {
    t += ' ' + freq;
  }
  if (! isBlank(route)) {
    t += ' ' + route;
  }
  if (asNeeded && asNeeded != '0') {
    t += ' as needed';
  }
  if (withMeals && withMeals != '0') {
    t += ' with meals';
  }
  if (! isBlank(length)) {
    t += ' for ' + length;
  }
  //if (! isBlank(disp)) {
  //  t += ' (Disp: ' + disp + ')';
  //}
  return String.trim(t);
}
function loadMedHistory(meds) {
  if (meds) {
    medHist = meds;
  }
}
var MedList = {
  create:function(container, title, cls) {
    return Html.Tile.create(container).extend(function(self) {
      return {
        init:function() {
          self.list = Html.Ul.create().into(self);
          self.titlebox = Html.Div.create(cls).setText(title).into(self.list.li()).hide();
        },
        add:function(m) {
          Html.Anchor.create(null, m.name, medHistSel.curry(m)).into(self.list.li());
          self.titlebox.show();
        }
      }
    })
  }
}
function showMedHistory() {
  _$('medListTitle').hide();
  _$('medListNone').hide();
  _$('medListTitle').html('');
  _$(medListUl);
  medListUl.clean();
  medListUl.active = MedList.create(medListUl, 'Active');
  medListUl.inactive = MedList.create(medListUl, 'Inactive', 'medpad');
  Array.forEach(medHist, function(m, i) {
    var m = medHist[i];
    if (m.active)
      medListUl.active.add(m);
    else
      medListUl.inactive.add(m);
  })
}
function medHistSel(m) {
  //var m = medHist[i];
  Html.InputText.$('medName').setValue(m.name);
  Html.InputText.$('medAmt').setValue(m.amt);
  Html.InputText.$('medFreq').setValue(m.freq);
  Html.InputCheck.$('medAsNeed').setCheck(m.asNeeded);
  Html.InputCheck.$('medMeals').setCheck(m.meals);
  Html.InputText.$('medRoute').setValue(m.route);
  Html.InputText.$('medLength').setValue(m.length);
  Html.InputText.$('medDisp').setValue(m.disp);
  doMedSearch();
}

// AJAX return
function parseMeds(o) {
  if (o.meds == null) {
    medListNone.innerText = 'No matching medications found.';
    return;
  }
  var m;
  var h = '';
  var limit = o.meds.length;
  if (limit > 100) {
    limit = 100;
    medListFoot.innerText = 'Limit exceeded, please narrow your search.';
  }
  for (var i = 0; i < limit; i++) {
    m = o.meds[i];
    h += '<li><a href="javascript:" onclick="upMedName(this)">' + m.name;
    if (m.dosage != ' ' && m.dosage != '') {
      h += ' (' + m.dosage + ')';
    }
    h += '</a></li>';
  }
  medListUl.innerHTML = h;
}

// Prescription writer
// To call: showRx(rx)
// rx
//   date
//   JClient client
//   JUser me
//   JDataMed[] meds
//     checked  // optional boolean to default check
//   showMedList  // optional boolean to show print med list button (default false) 
// Callback: rxCallback(meds)
//   JDataMed[] meds  // just the selected ones printed
//     rx             // new field: freetext, e.g. (RX 11/1/2009 Disp: 1200, Refills: None)
var rx;
var rxcb;
var rq;
/*
 * Alternate show method, using question
 * Pass rx arg as prop of q (q.rx)
 * Callback: callback(q)  // q.meds contains return value (see callback description above)
 */
function showRxByQuestion(q, callback) {
  rq = q;
  showRx(q.rx, callback, true);
}
function showRx(r, callback, fromQ) {
  if (! fromQ) {
    rq = null;
  }
  rx = r;
  if (callback) {
    rxcb = callback;
  } else {
    rxcb = rxCallback;
  }
  //if (me.isErx()) {
  hideForErx();
  //}
  loadRxDocs();
  setRxHead();
  //checkAllCol1(Html.InputCheck.$('rx-med-tbl-ck').setCheck(false));
  _$('rx-refills').selectedIndex = 0;
  _$('rx-dnss').checked = false;
  _$('rx-daws').checked = false;
  _$('med-cmd-print-list').showIf(r.showMedList);
  Pop.showPosCursor('pop-rx');
  setRxMeds();
  //setDisps(setValue('rx-disps', '30 days'));
  if (! me.isErx())
    setDisps(Html.InputText.$('rx-disps').setValue(''));
}
function hideForErx() {
  _$('rx-med-th-disp').hide();
  _$('rx-med-th-refill').hide();
  _$('rx-med-th-dns').hide();
  _$('rx-med-th-daw').hide();
  _$('rx-med-th-disp0').hide();
  _$('rx-med-th-refill0').hide();
  _$('rx-med-th-dns0').hide();
  _$('rx-med-th-daw0').hide();
  _$('rx-med-cmd-rx').hide();
}
function loadRxDocs() {
  if (! rx.docs) {
    rx.docs = [me];
  }
  if (rx.me == null) {
    rx.me = me;
  }
  var sel = (rx.docid) ? rx.docid : rx.me.userId;
  Html.Select.$('rx-docs').load(Map.from(rx.docs, null, 'name')).setValue(sel);
  //createOptsFromObjectArray('rx-docs', rx.docs, 'name', sel);
  setRxDoc();
}
function setRxDoc() {
  var doc = rx.docs[value('rx-docs')];
  _$('rx-head-lic').setText(doc.licLine);
  Html.InputText.$('rx-doc-name').setValue(doc.name);
  Html.InputText.$('rx-doc-lic').setValue(doc.licLine);
}
function setRxHead() {
  if (rx.date == null) {
    rx.date = DateUi.getToday(1);
  }
  Html.InputText.$('rx-submit-date').setValue(rx.date);
  Html.InputText.$('rx-submit-client').setValue(rx.client.name);
  Html.InputText.$('rx-submit-dob').setValue(rx.client.cbirth);
  Html.InputText.$('rx-prac-name').setValue(rx.me.User.UserGroup.name);
  Html.InputText.$('rx-prac-addr').setValue(rx.me.User.UserGroup.Address.addr1);
  var phones = rxFormatPhones(rx.me.User.UserGroup.Address);
  Html.InputText.$('rx-prac-phone').setValue(phones);  
  _$('rx-head-prac').setText(rx.me.User.UserGroup.name);
  _$('rx-head-prac-addr').setText(rx.me.User.UserGroup.Address.addr1);
  _$('rx-head-prac-phone').html(phones);
  //setText('rx-head-date', rx.date);
  //setText('rx-head-client', rx.client.name);
  //setText('rx-head-client-dob', 'DOB: ' + rx.client.cbirth);
}
function rxFormatPhones(addr) {
  var a = [addr.phone1];
  if (addr.phone2All != null) a.push(addr.phone2All);
  if (addr.phone3All != null) a.push(addr.phone3All);
  return a.join(' &#x2022; ');
}
function setRxMeds() {
  var t = new TableLoader('rx-med-tbody', 'off', 'rx-med-div');
  var checkAll = true;
  for (var i = 0; i < rx.meds.length; i++) {
    if (rx.meds[i].checked) {
      checkAll = false;
      break;
    }
  }
  for (var i = 0; i < rx.meds.length; i++) {
    var med = rx.meds[i];
    med.length = med.length || '';
    med.autoCalcDisp = (med.length == '' || med.length == '30 days' || med.length == '90 days');
    if (String.denull(med.disp) == '') {
      med.disp = calcMedDisp(med.amt, med.freq, med.length);
    }
    med.olength = med.length;
    med.odisp = med.disp;
    // med.sig = calcRxSig(med);
    //med.text = calcRxSig(med);
    //if (me.isErx()) 
      med.sig = (med.text) ? med.text : calcRxSig(med);
    t.createTrTd('check');
    t.tr.id = 'rx-tr-' + i;
    t.tr.className = '';
    var click = 'clickRxMed(this)';
    var c = createCheckbox('sel-rx-med', i, null, click);
    t.append(c);
    if (med.checked || checkAll) c.checked = true;
    t.createTd('medname', med.name);
    t.td.id = 'rx-td-name-' + i;
    t.td.onclick = new Function('clickRxName(this)');
    t.append(createRxHidden('rx-name', i, med.name, 'name'));
    t.createTd();
    t.append(createRxHidden('rx-sig', i, med.sig, 'sig'));
    t.append(createSpan(null, med.sig, 'rx-span-sig-' + i));
    if (1 || me.isErx()) t.td.style.width = '100%';
    t.createTd();
    t.append(createRxInput('rx-disp', i, med.disp, 'disp'));
    if (1 || me.isErx()) t.td.style.display = 'none';
    t.createTd();
    t.append(createRefillSelect(i));
    if (1 || me.isErx()) t.td.style.display = 'none';
    t.createTd('check noborder');
    t.append(createRxCheckbox('rx-dns', i, 'dns'));
    if (1 || me.isErx()) t.td.style.display = 'none';
    t.createTd('check noborder');
    t.append(createRxCheckbox('rx-daw', i, 'daw'));
    if (1 || me.isErx()) t.td.style.display = 'none';
    clickRxMed(c);
  }
}
function checkAllDns(c) {
  rxCheckAll('rx-dns', c.checked); 
}
function checkAllDaw(c) {
  rxCheckAll('rx-daw', c.checked); 
}
function rxCheckAll(idPrefix, value) {
  for (var i = 0; i < rx.meds.length; i++) {
    Html.InputCheck.$(idPrefix + '-' + i).setCheck(value);
  }
}
function setRefills(c) {
  var v = c.options[c.selectedIndex].text;
  for (var i = 0; i < rx.meds.length; i++) {
    Html.InputText.$('rx-refill-' + i).setValue(v);
  }
}
function setDisps(c) {
  var length = c.value;
  for (var i = 0; i < rx.meds.length; i++) {
    var med = rx.meds[i];
    if (med.autoCalcDisp) {
      if (c.value == '') {
        med.disp = med.odisp;
        med.length = med.olength;
      } else {
        med.disp = calcMedDisp(med.amt, med.freq, length);
        if (! isBlank(med.length)) {
          med.length = length;
        }
      }
      med.sig = calcRxSig(med);
      Html.InputText.$('rx-disp-' + i).setValue(med.disp);
      Html.InputText.$('rx-sig-' + i).setValue(med.sig);
      _$('rx-span-sig-' + i).setText(med.sig);
    }
  }
}
function calcRxSig(med) {
  var mt = medBuildText(med.amt, med.freq, med.route, med.length, med.asNeeded, med.meals);
  mt = String.trim(mt.replace(/for long-term/, ''));
  mt = String.trim(mt.replace(/MWF/, 'on Mon, Wed, Fri'));
  return mt;
}
function createRefillSelect(ix) {
  opts = [
      {'k':'0','v':'None'},
      {'k':'1','v':'1'},
      {'k':'2','v':'2'},
      {'k':'3','v':'3'},
      {'k':'4','v':'4'},
      {'k':'5','v':'5'},
      {'k':'6','v':'6'},
      {'k':'7','v':'7'},
      {'k':'8','v':'8'},
      {'k':'9','v':'9'},
      {'k':'10','v':'10'},
      {'k':'11','v':'11'},
      {'k':'12','v':'12'}];
  var s = createSelectByKvs('rx-refill-' + ix, null, opts);
  s.name = cna('refill', ix); 
  return s;
}
function createRxInput(idPrefix, ix, value, name) {
  var i = createInput(idPrefix + '-' + ix, 'text', value, 'w100');
  i.name = cna(name, ix);
  return i;
}
function createRxHidden(idPrefix, ix, value, name) {
  var h = createInput(idPrefix + '-' + ix, 'hidden', value);
  h.name = cna(name, ix);
  return h;
}
function createRxCheckbox(idPrefix, ix, name) {
  var c = createCheckbox(idPrefix + '-' + ix, '1');
  c.name = cna(name, ix);
  return c;
}
function cna(name, ix) {
  return name + '[' + ix + ']';
}
function clickRxMed(c) {  
  setDisabledOnly('rx-name-' + c.value, ! c.checked);
  setDisabledOnly('rx-sig-' + c.value, ! c.checked);
  setDisabledInput('rx-disp-' + c.value, ! c.checked);
  setDisabledInput('rx-refill-' + c.value, ! c.checked);
  setDisabledOnly('rx-dns-' + c.value, ! c.checked);
  setDisabledOnly('rx-daw-' + c.value, ! c.checked);
  setDisabled('rx-sig-' + c.value, ! c.checked);
  _$('rx-td-name-' + c.value).className = (c.checked) ? 'medname' : 'medname unselname';
  _$('rx-tr-' + c.value).className = (c.checked) ? 'off' : '';
  disableRxButton();
  if (! c.checked) _$('rx-med-tbl-ck').checked = false;
}
function clickRxName(td) {
  var c = td.previousSibling.firstChild;
  c.checked = ! c.checked;
  clickRxMed(c);
}
function disableRxButton() {
  var sel = getCheckedValues('sel-rx-med', 'rx-med-tbody');
  if (sel.length == rx.meds.length) _$('rx-med-tbl-ck').checked = true;
  setDisabled('med-cmd-print-rx4', sel.length == 0);  
  setDisabled('med-cmd-print-rx1', sel.length == 0);  
}
function printRx(docType, pageLayoutIndex) {
  Html.InputText.$('rx-doc-type').setValue(docType);
  Html.InputText.$('rx-pp').setValue(pageLayoutIndex);
  window.open('', 'rxw', 'top=0,left=0,resizable=1,toolbar=1,scrollbars=1,menubar=1');
  window.setTimeout(buildFn('printRxSubmit', [docType]), 10);
}
function printRxSubmit(docType) {
  _$('frm-rx').submit();
  if (docType != 1) {
    rxSaveCallback();
  }
  Pop.close();
}
function rxSaveCallback() {
  var cbMeds = [];
  var sel = getCheckedValues('sel-rx-med', 'rx-med-tbody');
  for (var j = 0; j < sel.length; j++) {
    var i = sel[j];
    var med = rx.meds[i];
    med.disp = value('rx-disp-' + i);
    med.refills = value('rx-refill-' + i);
    med.dns = isChecked('rx-dns-' + i);
    med.daw = isChecked('rx-dns-' + i);
    med.rx = rxFreetext(med);
    cbMeds.push(med);
  }
  if (rq) {
    rq.meds = cbMeds;
    rxcb(rq);    
  } else {
    rxcb(cbMeds);
  }
}
function rxFreetext(med) {
  return '(RX ' + rx.date + ' Disp: ' + med.disp + ', Refills: ' + med.refills + ')'; 
}
/* ui.js remnamt */
function $$$2(id, parent, tagName) {  // getElementsById within parent
  var e = parent.getElementsByTagName(tagName);
  var r = [];
  for (var i = 0; i < e.length; i++) {
    if (id == e[i].id) {
      r.push(e[i]);
    }
  }
  return r;
}
function scrollToTr(id, offset) {  // scroll to a TBODY <TR> for a table inside a fixed-height scrollable DIV
  var tr = _$(id);
  if (tr) 
    return scrollToTr_(tr, offset);
}
function scrollToTr_(tr, offset) {
  var table = tr.parentElement.parentElement;
  var div = table.parentElement;
  var headHeight = 0;
  if (table.children.length == 2 && table.firstChild.tagName == "THEAD" && table.firstChild.children.length > 0) {
    headHeight = table.firstChild.firstChild.clientHeight;
  }
  if (! offset) offset = 0; 
  div.scrollTop = tr.offsetTop - headHeight + offset;
  return tr;
}
function genderFix(text, isMale) {
  if (text == null) return text;
  text = " " + text + " ";
  if (isMale) {
    text = text.replace(/Woman /g, "Man ");
    text = text.replace(/ woman /g, " man ");
    text = text.replace(/She /g, "He ");
    text = text.replace(/ she /g, " he ");
    text = text.replace(/Her /g, "His ");
    text = text.replace(/ her /g, " his ");
    text = text.replace(/ herself/g, " himself");
  } else {
    text = text.replace(/Man /g, "Woman ");
    text = text.replace(/ man /g, " woman ");
    text = text.replace(/He /g, "She ");
    text = text.replace(/ he /g, " she ");
    text = text.replace(/His /g, "Her ");
    text = text.replace(/ his /g, " her ");
    text = text.replace(/ himself/g, " herself");
    text = text.replace(/ him /g, " her ");
  }
  return text.substring(1, text.length - 1);
}
function removeHtmlFormatting(d) {
  d = d.replace(/<b>/g, "");
  d = d.replace(/<\/b>/g, "");
  d = d.replace(/<u>/g, "");
  d = d.replace(/<\/u>/g, "");
  return d;
}
function createCheckbox(id, value, className, onClick) {
  var i = createInput(id, "checkbox", value, className);
  if (onClick) i.onclick = new Function(onClick);
  return i;
}
function createInput(id, type, value, className) {
  var i = document.createElement("input");
  i.type = type;
  i.value = String.denull(value);
  if (id) i.id = id;
  if (className) i.className = className;
  return i;
}
function $$$(id, parent, tagName) {  // getElementsById within parent
  var e = parent.getElementsByTagName(tagName);
  var r = [];
  for (var i = 0; i < e.length; i++) {
    if (id == e[i].id) {
      r.push(e[i]);
    }
  }
  return r;
}
function getCheckedValues(id, parentId) {  
  parentId = parentId || id + "_span";  
  var c = $$$(id, _$(parentId), "INPUT");
  var a = [];
  for (var i = 0; i < c.length; i++) {
    if (c[i].checked) {
      a.push(c[i].value);
    }
  }
  return a;
}
function setDisabled(id, test) {  // set CSS disabled style
  var e = _$(id);
  if (test) {
    e.disabled = true;
    e.addClass('disabled');
  } else {
    e.disabled = false;
    e.removeClass('disabled');
  }
  return e;
}
