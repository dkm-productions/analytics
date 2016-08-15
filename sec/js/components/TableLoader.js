/**
 * TableLoader
 *
 * OFFSET, BREAK, ROW-KEY, AND FILTER SIDEBAR
 *   var t = new TableLoader("fsp-tbody", "off", "fsp-div");
 *   t.defineFilter({"Diagnosis":null}, filterCallback);  // filterCallback optional
 *   for (var i = 0; i < rows.length; i++) {
 *     var row = rows[i];
 *     var offset = row.date + row.sessionId;
 *     var break = [row.date, row.sessionId];
 *     var filter = {"Diagnosis":row.diagnosis};
 *     var key = row.id;
 *     t.createTr(offset, break, filter, key);
 *     ..
 *   }
 *   t.loadFilterSidebar("fsp-filter-ul", TableLoader.NO_FILTER_COUNT);
 *   function filterCallback(t) {
 *     var keys = t.getVisibleRowKeys();
 *     var value = t.getTopFilterValue();
 *     ..
 *   }
 * 
 * SIMPLE (STRING) FILTER AND ROLODEX
 *   var t = new TableLoader("tbody");
 *   for (var i = 0; i < rows.length; i++) {
 *     var row = rows[i];
 *     t.createTr(null, null, row.categoryId, row.name);  // filter by category, rolodex by name
 *     ..
 *   }
 *   t.loadRolodex("rolo-ul");
 *   t.applyFilter(categoryId);
 *
 * BATCH USAGE
 *   var t = new TableLoader("tbody");
 *   t.batchLoad(fs.rows, renderRow);
 *   function renderRow(t, row) {
 *     t.createTrTd(row.name);
 *   }
 */
/*
 * Constructor
 * @arg <e>/'id' tbody
 * @arg string offRowClass (optional)
 * @arg <e>/'id' if enclosing table in a scrollable fixed-height DIV (optional)
 * @arg <e>/'id' to flicker fixed row on filter changes (optional) 
 */
function TableLoader(tbody, offRowClass, div, trHead) {
  // Private interface
  var pv = {
    tbody:_$(tbody),
    div:_$(div),
    divId:(div) ? div.id : null,
    workingInit:false,
    table:null,
    trHead:_$(trHead),
    offRowClass:offRowClass || '',
    offset:null,
    filter:null,
    filterSimple:true,  // true=string filter, false=object filter
    filterSummary:null,
    filterLevel:null,
    filterSidebar:null,
    filterTopbar:null,
    filterCallback:null,
    visibleRowKeys:[],
    rowsByKey:{},
    rolodexRows:null,  // {"A":<TR>,"B":<TR>,...}
    FILTER_LABEL_ALL:"",
    resetOffset:function() {
      pv.offset = {
          toggle:false,
          value:null,
          breaks:null,
          breakThru:-1,  // last col index to suppress value (due to repeated break) 
          rowClass:pv.offRowClass}
    },
    applyToOffset:function(offsetValue, breakColValues) {
      if (offsetValue != null) {
        if (offsetValue != pv.offset.value) {
          pv.offset.toggle = ! pv.offset.toggle;
          pv.offset.value = offsetValue;
        }
      } else {
        pv.offset.toggle = ! pv.offset.toggle;
      }
      if (breakColValues) {
        pv.offset.breakThru = pv.calcBreakThru(breakColValues, pv.offset.breaks);
        pv.offset.breaks = breakColValues;
      }
    },
    offsetClass:function() {
      return pv.offset.toggle ? pv.offset.rowClass : "";
    },
    buildFilterObject:function(filter) {
      var o = {};
      for (var lbl in filter) {
        var filterValue = filter[lbl];
        o[lbl] = pv.buildFilterValueObject(filterValue);
      }
      return o;
    },
    buildFilterValueObject:function(filterValue) {  // converts (1) [value,key] to {"key":key,"value":value} or (2) "value" to {"value":value} 
      var fv = {};
      if (Array.is(filterValue)) {
        fv.value = filterValue[0];
        fv.key = filterValue[1];
      } else {
        fv.value = filterValue;        
      }
      return fv;
    },
    resetFilterSummary:function() {
      pv.filterSummary = {};
      var level = 0;
      for (var lbl in pv.filter) {
        level++;
        pv.filterSummary[lbl] = {};
        pv.filterSummary[lbl][pv.FILTER_LABEL_ALL] = {ct:0};
        if (pv.filter[lbl] && pv.filter[lbl].value) {
          pv.filterLevel = level;
        }
      }
    },
    reapplyRows:function() {  // redo TR filter styles, breaks and offsets
      pv.resetOffset();
      if (pv.rolodexRows) {
        pv.rolodexRows = {};
      }
      for (var i = 0, i2 = pub.rows(); i < i2; i++) {
        var tr = pv.tbody.children[i];
        var show = pv.applyFilterStyle(tr);
        if (show) {
          pv.applyToOffset(tr.offsetValue, tr.breakColValues);
          if (! tr.className || tr.className == pv.offset.rowClass) {
            if (pv.offset.rowClass != '') {
              if (tr.className != "hide") {
                tr.className = pv.offsetClass();
              }
            }
          }
          if (tr.roloIndex && pv.rolodexRows[tr.roloIndex] == null) {
            pv.rolodexRows[tr.roloIndex] = tr;
          }
        }
        if (tr.breakColValues) {
          for(var j = 0, j2 = tr.breakColValues.length; j < j2; j++) {
            var td = tr.children[j];
            if (td.hid != null) {
              td.innerHTML = (j <= pv.offset.breakThru) ? "&nbsp;" : td.hid;
            }
          }
        }
      }
    },
    applyFilterStyle:function(tr) {
      var show = true;
      if (pv.filter != null && tr.filter != null) {
        if (pv.filterSimple) {
          if (tr.filter != pv.filter) {
            show = false;
          } 
        } else {
          var match = 1;
          for (var lbl in tr.filter) {
            var value = pv.filter[lbl].value;
            var trValue = tr.filter[lbl].value;
//            if (value && trValue && trValue != value) {
            if (value && trValue != value) {            
              show = false;
              break;
            }
            match++;
          }
          var level = 0;
          for (var lbl in pv.filter) {
            level++;
            var value = pv.filter[lbl].value;
            var trValue = tr.filter[lbl].value;
            if (level <= match || show) {
              var sumValues = pv.filterSummary[lbl];
              if (trValue != null) {
                sumValues[pv.FILTER_LABEL_ALL].ct++;
                var sumCount = sumValues[trValue];
                if (Object.isUndefined(sumCount)) {
                  sumValues[trValue] = {ct:1, key:tr.filter[lbl].key};  // initialize filter value count
                } else {
                  sumValues[trValue].ct++;
                }
              }
            }
          }
        }
      }
      if (show) {
        tr.style.display = "";
        if (tr.key) {
          pv.visibleRowKeys.push(tr.key);
        }
      } else {
        tr.style.display = "none";
      }
      return show;
    },
    buildRolodexAnchor:function(divId, ix) {
      var href = "TableLoader.rscroll('" + divId + "', this)";
      var a = createAnchor(null, null, null, ix, null, href);
      return a;
    },
    buildFilterAnchor:function(lbl, value, count, key, className) {
      var text;
      if (value == pv.FILTER_LABEL_ALL) {
        text = (count != null) ? "All" : "(All)";
      } else {
        text = value;
      }
      if (count != null) text += " (" + count + ")";
      if (className == null) 
        className = (nullify(pv.filter[lbl].value) == nullify(value)) ? "fsel" : null;
      //var href = buildHrefFn("TableLoader.applyFilterById", [pv.tbody.id, lbl, value, key]);
      var href = '';
      var a = createAnchor("is_fa", href, className, null, text);
      a.onclick = pub.applyFilterValue.bind(pub, lbl, value, key);
      a.value = value;
      return a;
    },
    classifyFilterAnchors:function() {
      for (var lbl in pv.filter) {
        var value = pv.filter[lbl].value;
        var lblUl = pv.filterSidebar.lblUls[lbl];
        var a = $$$("is_fa", lblUl, "A");
        for (var i = 0; i < a.length; i++) {
          a[i].className = (value == nullify(a[i].value)) ? "fsel" : "";
        }
      }
    },
    calcBreakThru:function(b1, b2) {
      if (b1 == null || b2 == null) return -1;
      for (var i = 0; i < b1.length; i++) {
        if (b1[i] != b2[i]) break;
      }
      return i - 1;
    }
  };
  // Public interface
  var pub = {
    filterAllLabel:'(Any)',
    filterHideAll:false,
    filterOnset:null,
    tr:null,
    td:null,
    // filter (simple): "string"
    // filter (object, values only):   {label:value,label:value,..}          e.g. {"Users":"Jones, Joe",..}
    // filter (object, values + keys): {label:[value,key],label:[value,key]} e.g. {"Users":["Jones, Joe",100],..}
    // filterCallbackFn: optional, to pass new filter to callback after table refiltered
    // sidebarOnClickFn: optional, to pass filter value/key when sidebar clicked. this fn must return true to proceed with applying filter to table (use false to shortcircuit)  
    defineFilter:function(filter, filterCallbackFn, sidebarOnClickFn) {
      if (Object.is(filter)) {
        pv.filter = pv.buildFilterObject(filter);
        pv.filterSimple = false;
      } else {
        pv.filter = filter;
        pv.filterSimple = true;
      }
      pv.resetFilterSummary();
      if (filterCallbackFn) pv.filterCallback = filterCallbackFn;
      if (sidebarOnClickFn) pv.sidebarOnClick = sidebarOnClickFn;
    },
    // Define filter by function (e.g. have tableloader invoke filter function internally)
    defineFilterFn:function(fn, callback) {
      pv.filterFn = fn;
      this.defineFilter(fn(), callback);
    },
    // eofCallback: optional, to add extra callback at batch end with null arg
    batchLoad:function(col, fn, eofCallback) {  
      TableLoader.tlStartBatch(this, col, fn, eofCallback);
    },
    // offsetValue: optional, will hold off on row color change if same as last row's offsetValue
    // breakColValues: optional, array of column values to auto-suppress if same as last row's
    // filter: optional, either value (for simple) or {lbl:value,lbl:value,...} only supply non-null filter values
    // key: optional, to assign a key value to this row 
    // index: optional, to assign value for using with a rolodex index
    createTr:function(offsetValue, breakColValues, filter, key, index) {
      pv.applyToOffset(offsetValue, breakColValues);
      if (key != null && pv.rowsByKey[key]) {
        this.tr = pv.rowsByKey[key];
        clearChildren(this.tr);
      } else {
        this.tr = Html.Tr.create(pv.offsetClass());
        this.tr.unselectable;
        pv.tbody.appendChild(this.tr);
      }  
      this.tr.offsetValue = (offsetValue != null) ? offsetValue : null;
      this.tr.breakColValues = (breakColValues) ? breakColValues : null;
      if (key != null) {
        this.tr.key = key;
        pv.rowsByKey[key] = this.tr;
      }
      if (filter != null) this.setRowFilter(filter);
      if (index != null) this.setRolodexIndex(index);
    },
    getRowByKey:function(key) {
      return pv.rowsByKey[key];
    },
    setRowFilter:function(filter) {
      if (pv.filterFn) 
        filter = pv.filterFn(filter);
      this.tr.filter = (pv.filterSimple) ? filter : pv.buildFilterObject(filter);
      pv.applyFilterStyle(this.tr);
    },
    setRolodexIndex:function(text) {
      var ix = text.substring(0, 1).toUpperCase();
      this.tr.roloIndex = ix;
      if (pv.rolodexRows == null) {
        pv.rolodexRows = {};
      }
      if (pv.rolodexRows[ix] == null) {
        pv.rolodexRows[ix] = this.tr;
      }
    },
    createTd:function(className, innerText, innerHtml, asTh) {
      if (this.tr.children.length <= pv.offset.breakThru) {
        if (asTh == null) {
          this.td = createTdHtml("&nbsp", className);
        } else {
          this.td = createTh(null, className);
          this.td.innerHTML = '&nbsp';
        }
      } else {
        this.td = (asTh == null) ? createTd(innerText, className) : createTh(innerText, className);
        if (innerHtml) {
          this.td.innerHTML = innerHtml;
        }
      }
      if (! asTh)
        this.td.hid = (innerText != null) ? innerText : innerHtml;
      this.tr.appendChild(this.td);
      return this.td;
    },
    createTrTd:function(className, innerText, innerHtml) {
      this.createTr();
      return this.createTd(className, innerText, innerHtml);
    },
    createTdAppend:function(className, o1, o2, o3) {
      this.createTd(className);
      this.append(o1, o2, o3);
      return this.td;
    },
    setTrHead:function(trHead) {
      pv.trHead = trHead;  
    },
    getTrHead:function() {
      return pv.trHead;
    },
    append:function(o1, o2, o3) {
      if (this.tr.children.length - 1 <= pv.offset.breakThru) {
        if (o1) { 
          this.td.hid = o1.outerHTML;
          if (o2) this.td.hid += o2.outerHTML;
          if (o3) this.td.hid += o3.outerHTML;
        }
        return;
      }
      if (o1) this.td.appendChild(o1);
      if (o2) this.td.appendChild(o2);
      if (o3) this.td.appendChild(o3);
    },
    rows:function() {  // return row count
      return pv.tbody.children.length;
    },
    trs:function() {
      return pv.tbody.children;
    },
    removeTrs:function(keys) {
      var tr;
      for (var i = 0; i < keys.length; i++) {
        tr = pv.rowsByKey[keys[i]];
        if (tr) {
          pv.tbody.removeChild(tr);
        }
      }
      pv.reapplyRows();
    },
    reapply:function() {
      pv.reapplyRows();
    },
    applyFilterValue:function(lbl, value, key, fromSidebarClick) {  // hide rows whose filter[lbl] != value (if value != null)
      //if (! isWorking) {
      //  TableLoader.applyFilterById(pv.tbody.id, lbl, value);
      //  return;
      //}
      if (lbl) {
        var found = false;
        for (var k in pv.filter) {
          if (lbl == k) {
            pv.filter[lbl].value = value;
            pv.filter[lbl].key = key;
            found = true;
          } else if (found) {
            pv.filter[k].value = null;  // null out remainder of lbls (assumes they are hiearchical) 
            pv.filter[k].key = null;
          }
        }
      }
      if (fromSidebarClick && pv.sidebarOnClick) {
        if (! pv.sidebarOnClick(value, key)) {  // sidebarOnClick must return true to proceed
          this.working(false);
          this.loadFilterSidebar();
          return;
        }  
      }
      pv.resetFilterSummary();
      pv.visibleRowKeys = [];
      pv.reapplyRows();
      if (pv.filterSidebar) {
        this.loadFilterSidebar();
      }
      if (pv.filterTopbar) {
        this.loadFilterTopbar(pv.filterTopbar.ul, pub.getFilterValues());
      }
      this.scrollToTop();
      if (fromSidebarClick) {
        //closeHtml.Window.working();
        this.working(false);
      }
      if (pv.rolodexRows) {
        this.loadRolodex();
      }
      this.flicker();
      if (pv.filterCallback) {
        pv.filterCallback(this);
      }
      if (pub.filterOnset)
        pub.filterOnset(pv.filter);
      pub.scrollToTop();
      pub.working();
    },
    flicker:function() {
      if (pv.trHead) {
        flicker_(pv.trHead);
      }
    },
    applyFilter:function(filter) {
      if (filter == null) {
        this.resetFilter();
      } else {
        if (pv.filterSimple) {
          pv.filter = filter;
        } else {
          for (var lbl in pv.filter) {
            var fv = pv.buildFilterValueObject(filter[lbl]);
            pv.filter[lbl].value = fv.value;
            pv.filter[lbl].key = fv.key;
          }
        }
        this.applyFilterValue();
      }
    },
    loadRolodex:function(ulId) {
      var ul;
      if (ulId) { 
        ul = $(ulId);
      } else {
        ul = pv.rolodex.ul;
      }
      clearChildren(ul);
      for (var ix in pv.rolodexRows) {
        var li = addListItem(ul, null);
        li.rolodexRow = pv.rolodexRows[ix];
        li.appendChild(pv.buildRolodexAnchor(pv.divId, ix));
      }
      if (! pv.rolodex) {
        pv.rolodex = {ul:ul};
      }
    },
    /*
     * Build filter topbar using EntryForm of <select> tags
     * @arg <e>/'id' ul: topbar <ul> tag
     * @arg {fid:$,..} filter: current filter values (optional)
     */
    loadFilterTopbar:function(ul, filter) {
      var ef = new EntryForm(_$(ul), 'nopad');
      ef.li();
      for (var lbl in pv.filterSummary) {
        var values = pv.filterSummary[lbl];
        var all = values[pv.FILTER_LABEL_ALL].ct;
        if (all > 0) {
          var labels = [];
          for (var value in values) {
            var text = (value == pv.FILTER_LABEL_ALL) ? '(All)' : value;
            if (value != pv.FILTER_LABEL_ALL || ! pub.filterHideAll) {
              labels.push(text + '|' + value);
            }
          }
          labels.sort();
          var list = {};
          for (i = 0; i < labels.length; i++) {
            var a = labels[i].split('|');
            var value = a[1];
            var text = (a[0] == '(All)') ? pub.filterAllLabel : a[0];
            list[value] = text;
          }
          ef.appendField(lbl, Html.Select.create(list), lbl);
        }
      }
      if (filter) {
        ef.setRecord(filter);
        pub.setFilterValues(ef.getVisibleRecord());
      }
      var self = this;
      ef.setOnChangeAny(
        function() {
          pub.working(true, function(ef) {
            self.applyFilter(ef.getRecord());
          }.curry(ef)) 
        })
      pv.filterTopbar = ef;
      if (pub.filterHideAll)
        self.applyFilter(self.buildFilterFromTopbar());
    },
    setFilterTopbar:function(filter) {
      if (pv.filterTopbar)
        pv.filterTopbar.setRecord(filter);
    },
    /*
     * Return filter {lbl:value,..} as specified by topbar
     * - record: optional, will calculate if not passed
     */
    buildFilterFromTopbar:function(record) {
      if (record == null)
        record = pv.filterTopbar.getRecord();
      if (pub.filterOnset)
        pub.filterOnset(record);
      for (var lbl in record) 
        if (record[lbl] == "") 
          record[lbl] = null;
      return record;
    },
    /*
     * Build filter sidebar using check imaged anchors
     * - ulId: id of sidebar <ul> tag
     * - noCount: true to suppress record count by filter values
     */
    loadFilterSidebar:function(ulId, noCount) { 
      var ul;
      if (ulId) {
        ul = _$(ulId);  
      } else {
        ul = pv.filterSidebar.ul;
        noCount = pv.filterSidebar.noCount;
      }
      var lblUls = {};
      var cls = "head";
      ul.clean();
      var hide = false;
      for (var lbl in pv.filter) {
        var text = lbl;
        if (lbl == "hide") {
          cls = "none";
          text = "";
        }
        //var li = addListItem(ul, null, "<div>" + text + "</div>", null, cls);
        var li = ul.li(cls).add(Html.Div.create().html(text));
        var lblUl = Html.Ul.create().into(li);
        if (hide) {
          li.style.display = 'none';
          lblUl.style.display = 'none';
        }
        hide = hide || nullify(pv.filter[lbl].value) == null;
        lblUls[lbl] = lblUl;
        cls = "head push";
      }
      for (var lbl in pv.filterSummary) {
        var lblUl = lblUls[lbl];
        var lblLi = lblUl.parentElement;
        var values = pv.filterSummary[lbl];
        var all = values[pv.FILTER_LABEL_ALL].ct;
        if (all == 0) {
          lblLi.style.display = "none"; 
        } else {
          var i = -1;
          for (var value in values) {
            i++;
            if (i > 1) break;
          }
          lblLi.style.display = "";
          if (i == 1) {
            var count = (noCount) ? null : values[value].ct;
            var li = lblUl.li();
            var key = values[value].key;
            var a = pv.buildFilterAnchor(lbl, value, count, key, 'fsel');
            li.add(a);
          } else {
            Map.eachByKey(values, function(ovalue, value) {
              var count = (noCount) ? null : ovalue.ct;
              var li = lblUl.li();
              var key = ovalue.key;
              var a = pv.buildFilterAnchor(lbl, value, count, key);
              li.add(a);
            })
          }
        }
        if (nullify(pv.filter[lbl].value) == null)
          break;
      }
      ul.lblUls = lblUls;
      if (! pv.filterSidebar) {
        pv.filterSidebar = {
            ul:ul,
            noCount:noCount};
      }
      if (pv.filterSidebar.ul.onload)
        pv.filterSidebar.ul.onload();
    },
    getFilterSummary:function() {  // return {lbl:{"ct":ct,"key":key},..} 
      return pv.filterSummary;
    },
    getTopFilterSummary:function() {
      for (var lbl in pv.filterSummary)
        return pv.filterSummary[lbl];
    },
    getTopFilterCount:function() {
      var s = this.getTopFilterSummary();
      return s[pv.FILTER_LABEL_ALL].ct;
    },
    getVisibleRowKeys:function() {  // return array of row keys showing after filter
      return pv.visibleRowKeys;
    },
    getFilter:function() {
      return pv.filter;
    },
    getTopFilterLabel:function() {
      for (var lbl in pv.filter) {
        return lbl;        
      }
    },
    getTopFilterValue:function() {
      return pv.filter[this.getTopFilterLabel()].value;
    },
    getTopFilterKey:function() {
      return pv.filter[this.getTopFilterLabel()].key;
    },
    getFilterValues:function() {
      var o = {};
      for (var lbl in pv.filter) 
        o[lbl] = pv.filter[lbl].value;
      return o;
    },
    setFilterValues:function(o) {
      var changed, value;
      for (var lbl in o) {
        value = nullify(o[lbl]);
        if (pv.filter) {
          if (pv.filter[lbl].value != value) {
            pv.filter[lbl].value = value;
            changed = true;
          }
        }
      }
      if (changed) {
        pub.applyFilterValue();
      }
    },
    allFilterValuesNull:function() {
      for (var lbl in pv.filter) {
        if (pv.filter[lbl].value != null) {
          return false;
        }
      }
      return true;
    },
    applyTopFilterValue:function(value) {
      this.applyFilterValue(this.getTopFilterLabel(), value);
    },
    resetFilter:function() {
      for (var lbl in pv.filter) {
        pv.filter[lbl].value = null;
      }
      this.applyFilterValue();
    },
    working:function(on, fn) {
      Html.Window.working(on);
      if (fn)
        setTimeout(fn, 1);
    },
    reset:function() {
      clearAllRows(pv.tbody);
      pv.rowsByKey = {};
      pub.scrollToTop();
      pv.resetOffset();
    },
    scrollToTop:function() {
      if (pv.div) {
        pv.div.scrollTop = 0;
      }    
    },
    scrollToBottom:function() {
      if (pv.div) {
        pv.div.scrollTop = pv.div.scrollHeight;
      }    
    },
    scrollToTr:function(tr) {
      if (pv.div) {
        scrollToElement(pv.div, tr, (pv.trHead ? pv.trHead.getHeight() : 0));
      }
      return tr;
    }
  };
  // Constructor calls
  pv.tbody.tableLoader = pub;  // attach this loader to the <TBODY>
  if (pv.trHead && Page) 
    Page.addFlickerEvent(pv.trHead);
  pub.reset();
  return pub;
}
// Statics
TableLoader.EOF_CALLBACK = true;
TableLoader.NO_FILTER_COUNT = true;
TableLoader.AS_TH = true;
TableLoader.applyFilterById = function(tbodyId, lbl, value, key) {
  $(tbodyId).tableLoader.working(true);
  setTimeout(buildFn("$('" + tbodyId + "').tableLoader.applyFilterValue", [lbl, nullify(value), key, true]), 10);
}
TableLoader.rscroll = function(divId, a) {
  var tr = a.parentElement.rolodexRow;
  scrollToElement(divId, tr);
  fade(tr);
}
TableLoader.tlNextBatchId = 0;
TableLoader.tlBatches = {};
TableLoader.tlStartBatch = function(instance, col, fn, eofCallback) {
  var batch = {
      id:TableLoader.tlNextBatchId++, 
      instance:instance, 
      col:col, 
      fn:fn, 
      ix:0, 
      len:col.length, 
      eofCallback:eofCallback};
  instance.working(true);
  TableLoader.tlBatches[batch.id] = batch;
  TableLoader.tlIterBatch(batch.id);
}
TableLoader.tlIterBatch = function(id) {
  var b = TableLoader.tlBatches[id];
  if (b.ix >= b.len) {
    b.instance.working(false);
    if (b.eofCallback) b.fn(b.instance, null);
    TableLoader.tlBatches[id] = null;
    return;    
  } 
  var rec = b.col[b.ix++];
  b.fn(b.instance, rec);
  setTimeout("TableLoader.tlIterBatch(" + id + ")", 1);
}
