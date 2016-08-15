/**
 * AnchorTab 
 * Anchors that bring up overlaying panel
 * Requires: AnchorTab.css
 */
// Tab alignments 
AnchorTab.TAB_ALIGN_LEFT = 0;
AnchorTab.TAB_ALIGN_CENTER = 1;
// How to set <a> text after checkbox selection
AnchorTab.SEL_TEXT_AS_NONE = 0;    
AnchorTab.SEL_TEXT_AS_VALUES = 1;  
AnchorTab.SEL_TEXT_AS_LABELS = 2;
// Whether no sel shows as (red)
AnchorTab.SEL_SHOW_REQUIRED = true;
AnchorTab.SEL_NO_SHOW_REQUIRED = false;
// Command bar buttons  
AnchorTab.BUTTONS_OK_CANCEL = 0;    
AnchorTab.BUTTONS_CANCEL_ONLY = 1;
//
AnchorTab.SEL_DELIM_NONE = 'radiostyle';
/*
 * AnchorTab constructor
 * - anchorOrText: either <a> tag defaulted to caption text (e.g. 'Select an Option')
 *                 or text to assign to a new <a> tag created here
 *                 a.anchorTab will be set to this instance
 * - anchorClass, panelClass: optional
 * - tabAlign: optional, default TAB_ALIGN_LEFT
 * - checkRecs: optional; to call loadChecks and appendCmd with defaults 
 */
function AnchorTab(anchorOrText, anchorClass, panelClass, tabAlign, checkRecs) {
  var anchor;
  if (String.is(anchorOrText)) {
    anchor = createAnchor(null, null, anchorClass, anchorOrText);
  } else {
    anchor = anchorOrText;
  }
  AnchorTab._newInstance(this);
  this._createTags(anchor, panelClass);
  this.tabAlign = tabAlign || AnchorTab.TAB_ALIGN_LEFT;
  if (checkRecs) {
    this.loadChecks(checkRecs);
    this.appendCmd();
  } 
}
/*
 * AnchorTab class defs
 */
AnchorTab.prototype = {
  anchor:null,      // <a> tag
  panel:null,       // <div> for panel
  lbl:null,         // initial <a> text
  tabAlign:null,    // alignment of tab
  selTextAs:null,   // how to display selected options
  selDelim:null,    // sel text delimiter
  selShowReq:null,  // show no sel in (red)
  _shadow:null,     // <div> for panel shadow
  _tshadow:null,    // <div> for tab shadow
  _tab:null,        // <a> tab
  _i:null,          // instance index
  _lastText:null,   // last <a> text prior to pop()
  _lc:null,         // LabelChecks
  _checked:null,    // checked value/labels {'v':[value,..],'l':[label,..]}
  _onchange:null,
  _onpop:null,
  _onok:null,
  setOnchange:function(fn) {
    this._onchange = fn;
  },
  setOnpop:function(fn) {
    this._onpop = fn;
  },
  setOnok:function(fn) {
    this._onok = fn;
  },
  /*
   * Load panel with label checks
   * - recs: data records (see ui's createLabelChecks for formats)
   * - checkValueFromField, checkLabelFromField: optional (see ui's createLabelChecks) 
   * - selTextAs: optional, default SEL_TEXT_AS_LABELS
   * - selDelim: optional, default ', ' use SEL_DELIM_NONE for radio behavior (single option)
   * - selShowReq: optional, default SEL_SHOW_REQUIRED 
   */
  loadChecks:function(recs, checkValueFromField, checkLabelFromField, selTextAs, selDelim, selShowReq, cols) {
    if (checkValueFromField || checkLabelFromField)
      recs = Map.from(recs, checkValueFromField, checkLabelFromField);
    this.selTextAs = selTextAs || AnchorTab.SEL_TEXT_AS_LABELS;
    this.selDelim = selDelim || ', ';
    if (selShowReq === false)
      this.selShowReq = false;
    else
      this.selShowReq = selShowReq || (this.selTextAs != AnchorTab.SEL_TEXT_AS_NONE) ? AnchorTab.SEL_SHOW_REQUIRED : AnchorTab.SEL_NO_SHOW_REQUIRED;
    var self = this;
    this._lc = Html.LabelChecks.create(recs, cols).into(this.panel).aug({
      onclick_check:function(lcheck) {
        self.onclick_check.call(self, lcheck);
      }
    });
    this._setSelText();
  },
  onclick_check:function(c) {
    if (this.selDelim == AnchorTab.SEL_DELIM_NONE) {  // if "radios", maintain one check and close pop
      var values = (c.isChecked()) ? [c.getValue()] : [];
      this._lc.setChecked(values);
      this._ok();
    }
    if (this._onchange)
      this._onchange();
  },
  /*
   * Load panel with radio (actually <a>) options
   */
  loadRadios:function(recs, checkValueFromField, checkLabelFromField, selTextAs, selShowReq, cols) {
    this.loadChecks(recs, checkValueFromField, checkLabelFromField, selTextAs, AnchorTab.SEL_DELIM_NONE, selShowReq, cols);
  },
  /*
   * Return checked values [value,..]
   */
  getValue:function() {
    if (this.selDelim == AnchorTab.SEL_DELIM_NONE) { 
      return (this._checked.v.length > 0) ? this._checked.v[0] : null;
    } else {
      return this._checked.v;
    }
  },
  /*
   * Set checked values 
   * - values: [value,..]
   */
  setValue:function(values) {
    if (this._lc) {
      if (this.selDelim == AnchorTab.SEL_DELIM_NONE) 
        values = arrayify(values);
      this._lc.setChecked(values);
      this._setSelText();
    }
  },
  /*
   * Turn off all checks
   */
  resetChecks:function() {
    this.setValue();
  },
  /*
   * Load panel with HTML
   */
  loadHtml:function(html) {
    this.panel.innerHTML = html;
  },
  /*
   * Append an element to panel
   */
  append:function(e) {
    this.panel.appendChild(e);
  },
  /*
   * Append the command bar
   * - buttons: optional, default AnchorTab.BUTTONS_OK_CANCEL 
   * - okCallback, okText: optional
   */
  appendCmd:function(buttons, okCallback, okText) {
    buttons = buttons || AnchorTab.BUTTONS_OK_CANCEL;
    okText = okText || 'OK';
    var self = this;
    var div = Html.Div.create('pop-cmd');
    if (buttons == AnchorTab.BUTTONS_OK_CANCEL) {
      var ok = createAnchor(null, null, 'cmd ok', okText);
      ok.onclick = function(){self._ok(okCallback)};
      div.appendChild(ok);
      div.appendChild(Html.Span.create(null, ' '));
    }
    var cancel = createAnchor(null, null, 'cmd none', 'Cancel');
    cancel.onclick = function(){self._cancel()};
    div.appendChild(cancel);
    this.append(div);
  },
  /*
   * Pop up panel
   */
  pop:function() {
    if (AnchorTab._active) return;
    var a = this.anchor; 
    this._lastText = a.innerText;
    a.invisible();
    //a.innerText = this.lbl;
    //a.style.color = '#c0c0c0';
    a.hideFocus = true; 
    //addClass(a, 'at-anchor');
    if (this._checked) 
      this._lc.setChecked(this._checked.v);
    hideCombos();
    this._positionPanel(true);
    AnchorTab._active = this;
    if (this._onpop) 
      this._onpop();
  },
  /*
   * Close panel
   * - text: optional, text to assign to anchor
   */
  close:function(text) {
    var a = this.anchor;
    //a.style.color = '';
    a.innerText = text || this._lastText;
    a.visible();
    //removeClass(a, 'at-anchor');
    this._positionPanel(false);
    restoreCombos();
    AnchorTab._active = null;
    if (this._onclose) 
      this._onclose();
  },
  _ok:function(callback) {
    this.close();
    if (this._lc) {
      this._setSelText();
    }
    if (callback) {
      callback(this);
    }
    if (this._onok) {
      this._onok();
    }
  },
  _cancel:function() {
    this.close();
  },
  _setSelText:function() {
    this._checked = {'l':this._lc.getCheckedTexts(),'v':this._lc.getCheckedValues()};
    if (this.selTextAs == AnchorTab.SEL_TEXT_AS_NONE) {
      return;
    }
    var sels = (this.selTextAs == AnchorTab.SEL_TEXT_AS_LABELS) ? this._checked.l : this._checked.v;
    var req = this.selShowReq == AnchorTab.SEL_SHOW_REQUIRED && sels.length == 0;
    this._setAnchorText((sels.length == 0) ? this.lbl : sels.join(this.selDelim), req);
  },
  _setAnchorText:function(text, req) {
    this.anchor.innerText = text;
    this.anchor.style.color = (req) ? 'red' : '';
  },
  _createTags:function(a, panelClass) {
    var self = this;
    a.href = 'javascript:';
    a.onclick = function(){self.pop()};
    this.anchor = _$(a);
    this.lbl = a.innerText;
    var className = String.trim('at-panel ' + (panelClass || ''));
    this.panel = Html.Window.append(Html.Div.create(className));
    this.anchor.anchorTab = this;
    this.panel.anchorTab = this;
  },
  _getShadow:function(shadow) {
    if (shadow == null) {
      shadow = Html.Window.append(Html.Div.create('at-shadow'));
    }
    return shadow;
  },
  _getTab:function() {
    if (this._tab == null) {
      var className = String.trim(this.anchor.className + ' at-anchor');
      this._tab = _$(createAnchor(null, null, className, this.lbl));
      this._tab.style.margin = 0;
      var parent = this.anchor.parentElement;
      if (parent.className == 'fwidth') {
        parent = parent.parentElement;
      }
      Html.Window.append(this._tab);
    }
    return this._tab;
  },
  _positionPanel:function(show) {
    if (show) {
      var dd = Html.Window.getViewportDim();
      var st = Html.Window.getScrollTop();
      var da = this.anchor.getPos();
      this._shadow = this._getShadow(this._shadow);
      this._tshadow = this._getShadow(this._tshadow);
      this._tab = this._getTab();
      this._tab.setLeft(da.left - 4).setTop(da.top - 3);
      da = this._tab.getPosDim();
      var dp = this.panel.getDim();
      var min = (da.width + 2) + AnchorTab._TABINDENT;
      var p = this.panel;
      var s = this._shadow;
      var ts = this._tshadow;
      if (dp.width < min) {
        p.setWidth(min);
        dp = p.getDim();
      }
      var left = (this.tabAlign) ? (da.left + da.width / 2) - (dp.width / 2) - AnchorTab._TABINDENT / 2 : da.left - AnchorTab._TABINDENT;
      var top = da.top + da.height;
      if ((left + dp.width) > dd.width)
        left = dd.width - dp.width - 30;
      var ch = dd.height + st;
      if ((top + dp.height) > ch)
        top = ch - dp.height - 30;
      p.setLeft(left).setTop(top);
      s.setLeft(left - 2).setTop(top - 2).setHeight(dp.height + 2).setWidth(dp.width + 2);
      ts.setLeft(da.left - 2).setTop(da.top - 2).setHeight(da.height + 2).setWidth(da.width + 2);
      
    }
    AnchorTab._setVis(this._shadow, show);
    AnchorTab._setVis(this._tshadow, show);
    AnchorTab._setVis(this.panel, show);
    AnchorTab._setVis(this._tab, show);
  }
}
/*
 * AnchorTab statics
 */
AnchorTab._TABINDENT = 20;
AnchorTab._active = null;     // actively popped AnchorTab
AnchorTab._ict = 0;           // instance count
AnchorTab._newInstance = function(instance) {
  instance._i = AnchorTab._ict++;
  if (instance._i == 0) {
    Html.Window.attachEvent('mousedown', AnchorTab._onBodyMouse, document.body);
  }
}
AnchorTab._onBodyMouse = function() {
  if (AnchorTab._active) {
    if (! AnchorTab._active._lc.isDirty()) {
      if (findEventAncestorWith('anchorTab', AnchorTab._active) == null) 
        AnchorTab._active.close();
    }
    Html.Window.cancelBubble();
  }
}
AnchorTab._setVis = function(e, show) {
  e.style.visibility = (show) ? 'visible' : 'hidden';
}
