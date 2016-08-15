/**
 * OLD UI.JS ------------ to deprecate
 */
/*
 * Returns e having ID
 */
function $(id) {
  return document.getElementById(id);
}
/** 
 * Element getters and class functions
 */
/*
 * Returns [e,..] for specific tag having ID across document
 */
function $$(id) {  // getElementsById 
  var a = document.all[id];
  return (a == null) ? [] : a;
}
/*
 * Returns [e,..] for specific tag having ID within parent
 */
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
/*
 * getElementsByClass
 * Returns [e,..] for specific tag of supplied class within parent
 */
function $$$$(className, parent, tagName, startsWith) {
  parent = parent || $('bodyContent');
  var all = parent.getElementsByTagName(tagName);
  var r = [];
  var e;
  for (var i = 0; (e = all[i]) != null; i++) {
    if (hasClass(e, className, startsWith)) {
      r.push(e);
    }
  }
  return r;
}
function $_(e) {
  return isString(e) ? $(e) : e;
}
/*
 * Set class for element having ID
 */
function setClass(id, className) {
  var e = $(id);
  $(id).className = className;
  return e;
}
/*
 * Determine if element uses supplied class name
 * - startsWith: optional; default false, true to allow partial match
 */
function hasClass(e, className, startsWith) {  // true if e specifies className, startsWith optional boolean
  var extra = (startsWith) ? '*' : '(?:$|\\s)';  
  var hasClassName = new RegExp('(?:^|\\s)' + className + extra);
  var ec = e.className;
  if (ec && ec.indexOf(className) != -1 && hasClassName.test(ec)) {
    return true;
  }  
}
/*
 * Add a class to element
 */
function addClass(e, className) {
  if (! hasClass(e, className)) {
    e.className = trim(e.className + ' ' + className);
  }
  return e;
}
/*
 * Remove a class from element
 */
function removeClass(e, className) {
  e.className = trim(e.className.replace(className, ''));
}
/**
 * Page functions
 */
/*
 * Attach focus and blur event handlers to page
 * - focusfn: to call when page receives focus
 * - blurfn: to call when page loses focus
 */
var wfb; 
function attachWindowFocusBlur(focusfn, blurfn) {
  wfb = {
    activeElement:document.activeElement,
    focusFn:focusfn,
    blurFn:blurfn,
    blurred:false};
  document.onfocusin = windowFocus;
  document.onfocusout = windowBlur;
}
function windowFocus() {
  if (wfb.blurred) {
    wfb.blurred = false;
    wfb.focusFn();
  }
}
function windowBlur() {
  if (wfb.activeElement != document.activeElement) {
    wfb.activeElement = document.activeElement;
    return;
  }
  wfb.blurred = true;
  if (wfb.blurFn) 
    wfb.blurFn();
}
/*
 * Sets page title and appends ' &bull Clicktate'
 */
function setPageTitle(title) {
  document.title = title + ' \u2022 Clicktate';
}
/**
 * Type testers
 */
function isUndefined(x) {
  var u; 
  return x === u;
}
function isArray(x) {
  return x != null && x.constructor == Array;
}
function isFunction(f) {
   try {  
     return /^\s*\bfunction\b/.test(f);  
   } catch (e) {
     return false;  
   }    
}
function isObject(o) {
  return o && typeof o === "object" && (! isArray(o));
}
function isObjectOrArray(o) {
  return o && typeof o === "object"; 
}
function isHtmlElement(o) {
  return o && o.nodeName != null; 
}
function isString(s) {
  return typeof s == "string";
}
/**
 * Array/Map functions
 */
function arrayify(e) {
  return (isArray(e)) ? e : ((e == null) ? [] : [e]);  
}
/*
 * Test for empty array
 */
function isEmpty(a) {  
  return a == null || a.length == 0;
}
/*
 * Returns rec[field] if rec exists, null if rec doesn't
 */
function get(rec, field) {  
  return (rec) ? rec[field] : null;
}
/*
 * Test for empty map (associated array)
 */
function isEmptyMap(arr) {  
  for (var a in arr) {
    return false;
  }
  return true;
}
/*
 * Given [1,2,3] return lookup map [1:1,2:2,3:3]
 */
function makeMap(a) {
  var aa = {};
  for (var i = 0; i < a.length; i++) aa[a[i]] = a[i];
  return aa;
}
/*
 * Given a=[1,2,3] and map={1:[a,b],2:[a,x,y]}, return [a,b,x,y,3]
 */
function expandFromMap(a, map) {
  if (map == null || isEmpty(a)) {
    return a;
  }
  var out = [];
  var mapOut = {};
  for (var i = 0; i < a.length; i++) {
    var b = (map[a[i]] == null) ? [a[i]] : map[a[i]];
    for (var j = 0; j < b.length; j++) {
      if (mapOut[b[j]] == null) {
        out.push(b[j]);
        mapOut[b[j]] = 1;
      }
    }
  }
  return out;
}
/*
 * Merge two maps
 */
function merge(map1, map2) {
  var map = {};
  for (var i in map1) {
    map[i] = map1[i];
  }
  for (var i in map2) {
    map[i] = map2[i];
  }
  return map;
}
/*
 * Merge map into result map
 */
function mergeInto(result, map) {
  for (var i in map) {
    result[i] = map[i];
  }
}
/*
 * Concats to array if item is array, else pushes it
 * - array: must be initialized as [] (not null)
 */
function append(array, item) {  
  if (isArray(item)) {
    array = array.concat(item);
  } else {
    array.push(item);
  }
}
/*
 * Conditional array push
 */
function pushIfNotNull(a, e) {
  if (e != null) {
    a.push(e);
  }
}
/*
 * Pushes single item into map at map[index] 
 */
function pushInto(map, index, item) {
  if (map[index] == null) 
    map[index] = [];
  map[index].push(item);
}
function unshiftInto(map, index, item) {
  if (map[index] == null) 
    map[index] = [];
  map[index].unshift(item);  
}
/*
 * Appends array into map at map[index] 
 */
function appendInto2(map, index, array) {
  if (map[index] == null) 
    map[index] = array;
  else 
    map[index] = map[index].append(array);
}
/*
 * Removes null elements (including, optionally, empty strings) from array
 * - retainEmptyStrings: optional, default false 
 */
function removeNullsFromArray(a, retainEmptyStrings) { 
  if (a != null) {
    var b = [];
    var ins = ! retainEmptyStrings;
    for (var i = 0; i < a.length; i++) {
      if (a[i] == null || (ins && a[i] == '')) {
      } else {
        b.push(a[i]);
      }
    }
    return b;
  }
  return null;
}
/*
 * Returns index position of needle in haystack array, if found
 * Returns -1 if not found
 */
function find(haystack, needle) {  
  if (! isEmpty(haystack)) {
    for (var i = 0, j = haystack.length; i < j; i++) {
      if (haystack[i] == needle) return i;
    }
  }
  return -1;
}
/*
 * Returns true if needle exists anywhere in haystack
 */
function inArray(haystack, needle) {
  return (find(haystack, needle) > -1);
}
/*
 * Returns deep clone of supplied object
 * - preserveHtmlRefs: optional, default false
 *   If false, HTML element refs of original object will be removed in clone
 *   If true, HTML element refs of original object will be preserved (i.e., will point to same element; the HTML element will not be cloned)
 */
function clone(o, preserveHtmlRefs) {
  var n = (o.constructor == Array) ? [] : {};
  for (var i in o) {
    if (isObjectOrArray(o[i])) {
      if (isHtmlElement(o[i])) {
        n[i] = (preserveHtmlRefs) ? o[i] : null;
      } else {
        n[i] = clone(o[i]);
      }
    } else {
      n[i] = o[i];
    }
  }
  return n;
}
/**
 * String functions
 */
function isBlank(text) {
  return text == null || trim(text).length == 0;
}
function trim(text) {
  return (text != null) ? text.replace(/\xa0/g, '').replace(/^\s+|\s+$/g, "") : null;
}
function quote(text) {
  return '"' + text + '"';
}
function bool(test) {
  return (test) ? '1' : '0';
}
function stringToBool(s) {
  return (s == 'true');
}
/*
 * Replace null with empty string (or to, if supplied)
 * - to: optional
 */
function denull(value, to) {
  if (to == null) {
    return (value == null) ? "" : value + "";    
  } else {
    return (value == null) ? to : value;
  }
}
function nullify(value) {
  return (value == null) ? null : (trim(value + "") == "") ? null : value;
}
/*
 * Given a=['alpha','beta'], glue='+', return 'alpha+beta' 
 */
function joinWith(a, glue) {
  return (a) ? a.join(glue) : "";
} 
/*
 * Returns '1 noun' or '2 nouns'
 */
function plural(amt, noun) {
  if (amt == 1) {
    return amt + " " + noun;
  } else {
    return amt + " " + noun + "s";
  }
}
/*
 * Escape singlequotes (for use inside an outer pair of singlequotes)
 */
function esc(text) {  
  return (text != null) ? text.replace(/\'/g, "\\'") : null;
}
/*
 * Encodes doublequotes (for use inside an outer pair of doublequotes)
 */
function addslashes(str) {
  return (str + '').replace(/([\\"'])/g, "\\$1").replace(/\0/g, "\\0");
}
/*
 * String to numerics
 */
function val(s) {
  return parseInt(s, 10);
}
function valOrZero(s) {  
  if (isNaN(s)) {
    return 0;
  }
  return parseInt(s, 10);
}
function floatValOrZero(s) {
  if (isNaN(s)) {
    return 0;
  }
  return parseFloat(s);
}
/*
 * Make a two-digit number by leftpadding single digit with '0'
 */
function lpad(i) {
  return (i < 10) ? "0" + i : i;
}
/*
 * Remove 'in/on' prefix from long date
 */
function extractDate(date) {  
  if (date) {
    var inon = date.substr(0, 3);
    if (inon == "in " || inon == "on ") {
      return date.substring(3);
    }
  }
  return date;
}
/*
 * De-sex language
 */
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
/**
 * HTML element functions
 */
function appendAndRef(parent, child, prop) {
  parent.appendChild(child);
  parent[prop] = child;
}
function insertAfter(refNode, newNode) {
  if (refNode.nextSibling) {
    refNode.parentElement.insertBefore(newNode, refNode.nextSibling);
  } else {
    refNode.parentElement.appendChild(newNode);
  }
}
function appendInto(parent, child) {
  parent.appendChild(child);
  return child;
}
/*
 * Immediately-invoked function expression
 * @arg fn f 
 * @arg object context 
 * @arg ... (optional; any additional arguments will be passed to f when invoked, e.g. to provide args to onclick)  
 * @example var onclick = iife(FaceDiagnosis.fpEdit, this, diagnosis); 
 */
function iife(f, context) {
  var args = (arguments.length > 2) ? Array.prototype.slice.call(arguments, 2) : null;
  return function(){f.apply(context, args || arguments)};
}
/*
 * onClick: optional, either string "method('arg',..)" or Function
 */
function createAnchor(id, href, className, innerText, innerHtml, onClick, context) {
  var a = document.createElement("a");
  if (id != null) 
    a.id = id;
  a.href = href || 'javascript:';
  if (className != null) 
    a.className = className;
  if (innerText != null) 
    a.innerText = innerText;
  if (innerHtml != null)
    a.innerHTML = innerHtml;
  if (onClick) {
    var fn;
    if (isString(onClick)) {
      // onClick += ';return false';
      fn = new Function(onClick);
    } else {
      if (context) 
        fn = function(){onClick.call(context)};
        //fn = function(){onClick.call(context);return false};
      else
        fn = onClick;
      //fn = function(){onClick();return false};
    }
    a.onclick = fn;
  }
  return a;
}
function createA(className, innerText, onClick) {
  return createAnchor(null, null, className, innerText, null, onClick);
}
function createBr() {
  return document.createElement("BR");
}
function createDiv(id, className, innerText, innerHtml) {
  var d = createElement("div", id, className);
  if (innerText != null) d.innerText = innerText;
  if (innerHtml) d.innerHTML = innerHtml; 
  return d;
}
function createDivIn(parent, className, id) {
  var div = createElement('div', id, className);
  parent.appendChild(div);
  return div;
}
function createDivAppend(className, id, e) {
  var div = createElement("div", id, className);
  if (e) {
    div.appendChild(e);
  }
  return div;
}
function post(url, args) {
  var form = document.createElement('form');
  form.setAttribute('method', 'post');
  form.setAttribute('action', url);
  for (var fid in args) 
    form.appendChild(createHidden(null, args[fid], fid));
  document.body.appendChild(form);
  form.submit();
}
function createSpan(className, innerText, id, innerHtml) {
  var span = createElement("span", id, className);
  if (innerText != null) span.innerText = innerText;
  if (innerHtml != null) span.innerHTML = innerHtml;
  return span;
}
function createUnsel(className, innerText) {
  var span = createSpan(className, innerText);
  span.unselectable = 'on';
  return span;
}
function createSpanAppend(className, id, e) {
  var span = createElement("span", id, className);
  if (e) {
    span.appendChild(e);
  }
  return span;
}
function createH2(text, id, className) {
  var h = createElement("h2", id, className);
  h.innerText = text;
  return h;
}
function createH3(text, id, className) {
  var h = createElement("h3", id, className);
  h.innerText = text;
  return h;
}
function createHidden(id, value, name) {
  var i = createInput(id, 'hidden', value);
  if (name)
    i.name = name;
  return i;
}
function createInput(id, type, value, className) {
  var i = document.createElement("input");
  i.type = type;
  i.value = denull(value);
  if (id) i.id = id;
  if (className) i.className = className;
  return i;
}
function createTextbox(id, value, size, className) {
  var i = createInput(id, "text", value);
  if (size != null) i.size = size;
  if (className) i.className = className;
  return i;
}
function createTextArea(id, value) {
  var ta = document.createElement("textarea");
  if (id != null) ta.id = id;
  if (value != null) ta.value = value;
  return ta;
}
function createCheckbox(id, value, className, onClick) {
  var i = createInput(id, "checkbox", value, className);
  if (onClick) i.onclick = new Function(onClick);
  return i;
}
/*
 * Create span of label checks
 * - id: common ID of rendered checkboxes (in order to work with getCheckedValues)
 * - recs: [value,..]            simple array
 *         {index:value,..}      simple object
 *         [{field:value,..},..] array of records (field-value pairs)
 * - selected: optional [value,..] currently selected (checked) values
 * - checkValueFromField: optional, record field value to use as checkbox value; if not supplied, simple index is used 
 * - checkLabelFromField: optional, record field value to use as checkbox label; if not supplied, simple value is used
 * - spanId: optional, defaulted to "id + '_span'";
 * - spanClass: optional
 * - horizontal: optional, assumed false (delimited with <br>) 
 * Returns 
 *   <span id=spanId class=spanClass>
 *     <input id=id type=checkbox .. /><delim>
 *     ..
 *   </span>
 */
function createLabelChecks(id, recs, selected, checkValueFromField, checkLabelFromField, spanId, spanClass, horizontal, onclick) {
  var span = createSpan(spanClass, null, spanId || id + '_span');
  if (Array.is(recs))
    recs = Map.from(recs);
  for (var index in recs) {
    var rec = recs[index];
    var value = (checkValueFromField) ? rec[checkValueFromField] : index;
    var text = (checkLabelFromField) ? rec[checkLabelFromField] : rec;
    var c = createCheckbox(id, value, 'lcheck');
    c.onpropertychange = new Function('lcheckc(this)');
    c.ondblclick = new Function('this.checked=!this.checked');
    span.appendChild(c);
    var l = createLabel('lcheck', text);
    var fn = (function(l, onclick){return function(){lcheck(l, onclick)}})(l, onclick);
    l.onclick = fn;
    l.ondblclick = fn;
    l.unselectable = 'on';
    span.appendChild(l);
    if (inArray(selected, value)) {
      lcheck(l);
    }
    if (! horizontal) {
      span.appendChild(createBr());
    }
  }
  span.unselectable = 'on';
  return span;
}
function lcheck(lbl, onclick) {  // label onClick for checkbox 
  var c = lbl.previousSibling;
  c.checked = ! c.checked;  // this triggers lcheckc
  if (onclick)
    onclick(c);
}
function lcheckc(c) {  // checkbox onPropertyChange
  c.nextSibling.className = (c.checked) ? "lcheck-on" : "lcheck";
}
function createLabel(className, innerText, id) {
  var lbl = createElement("LABEL", id, className);
  if (innerText != null) lbl.innerText = innerText;
  return lbl;
}
function createTable(id, className, tbodyId, withThead) {
  var t = createElement("TABLE", id, className);
  if (withThead) {
    var head = document.createElement("THEAD");
    t.appendChild(head);
    t.head = head;
  }
  var body = document.createElement("TBODY");
  if (tbodyId)
    body.id = tbodyId;
  t.appendChild(body);
  t.body = body;
  return t;
}
function appendTr(table, trClass) {
  var tr = createTr(trClass);
  table.lastChild.appendChild(tr);
  return tr;
}
function createTr(className, id) {
  var tr = createElement("tr", null, className ? className : null);
  if (id)
    tr.id = id;
  return tr;
}
/*
 * @arg <e> table 
 * @arg 'tr-id' id (optional) 
 * @arg 'cls' className (optional)
 * @arg [c,..] cells [{'text':$,'className':$,'style':$},..] 
 * @return <tr>
 */
function appendHeaderRow(table, id, className, cells) {
  var tr = createTr(className, id);
  for (var i = 0; i < cells.length; i++) 
    tr.appendChild(createTh(cells[i].text, cells[i].className, cells[i].style));
  table.head.appendChild(tr);
  return tr;
}
function createThCells(tr, cells) { 
  clearChildren(tr);
  for (var i = 0; i < cells.length; i++) {
    var th = document.createElement("th");
    th.innerHTML = cells[i];
    tr.appendChild(th);
  }
}
function createTd(innerText, className) {
  var td = document.createElement("td");
  td.innerText = denull(innerText);
  if (className) td.className = className;
  return td;
}
function createTdAppend(className, e) {
  var td = createTd(null, className);
  td.appendChild(e);
  return td;
}
function createTh(innerText, className, style) {
  var td = document.createElement("th");
  td.innerText = denull(innerText);
  if (className) 
    td.className = className;
  if (style)
    td.style.cssText = style;
  return td;
}
function createThAppend(className, e) {
  var th = createTh(null, className);
  th.appendChild(e);
  return th;
}
function createTdHtml(html, className) {
  var td = createElement("td", null, className);
  td.innerHTML = html;
  return td;
}
function createTdAnchor(href, className, innerText, id, title, tdClassName) {
  var td = document.createElement("td");
  var a = document.createElement("a");
  a.href = href;
  a.className = className;
  a.innerText = innerText;
  applyProps(a, id, className, title);
  td.appendChild(a);
  td.className = tdClassName;
  return td;
}
function createList(parent, id, className) {
  var ul = document.createElement("ul");
  applyProps(ul, id, className);
  parent.appendChild(ul);
  return ul;
}
function addChildList(parentUl, insertBefore) {
  var ul = document.createElement("ul");
  if (insertBefore) {
    parentUl.insertBefore(ul, insertBefore);
  } else {
    parentUl.appendChild(ul);
  }
  return ul;
}
function createListItem(id, className) {
  var li = document.createElement("li");
  applyProps(li, id, className);
  return li;
}
function addListItem(ul, insertBefore, html, id, className) {  // leave insertBefore null to add to bottom of list  
  var li = createListItem(id, className);
  if (html) li.innerHTML = html;
  if (insertBefore) {
    ul.insertBefore(li, insertBefore);
  } else {
    ul.appendChild(li);
  }
  return li;
}
/*
 * - arr: {'value1':'text1','value2':'text2',...}
 * - selValue: optional selected value, 'value2'
 * - blankText: optional, if supplied becomes first "blank" (value="") option
 */
function createSelect(id, className, arr, selValue, blankText, onChange) {
  var sel = createSelectByKvs(id, className);
  loadSelect(sel, arr, selValue, blankText);
  if (onChange)
    sel.onchange = onChange;
  return sel;
}
function loadSelect(sel, arr, selValue, blankText) {
  if (blankText != null) {
    addOpt(sel, "", blankText, (nullify(selValue) == null))
  }
  for (var value in arr) {
    var text = arr[value];
    addOpt(sel, value, text, value == selValue);
  }
}
/*
 * jKeyValues: [{"v":"text","k":"key","sel":true},{"v":"text","k":"key"},...]
 */
function createSelectByKvs(id, className, jKeyValues) {    
  var sel = document.createElement("select");
  if (id != null) sel.id = id;
  if (className != null) sel.className = className;
  if (jKeyValues) addKvsOpts(sel, jKeyValues);
  return sel;
}
function createElement(tagName, id, className) {
  var e = document.createElement(tagName);
  if (id) e.id = id;
  if (className) e.className = className;
  return e;
}
/*
 * To correct IE bug with fixed TRs that are positioned improperly when table moves on page
 */
function flicker(id) {  // to fix fixed TRs
	return flicker_($(id));
}
function flicker_(e) {
  e.style.display = 'none';
  e.style.display = '';
  return e;
}
/*
 * Element show and hide functions
 * These assume element's display style has not been set thru CSS  
 */
function hide(id) {
  return hide_($(id));
}
function hide_(e) {
  e.style.display = 'none';
  return e;  
}
function show(id) {
  return show_($(id));
}
function show_(e) {
  e.style.display = '';
  return e;
}
function showb(id) {
  return showb_($(id));
}
function showb_(e) {
  e.style.display = 'block';
  return e;
}
function showHide(showId, hideId) {
  hide(hideId).scrollTop = 0;
  show(showId).scrollTop = 0;
}
function showHideIf(cond, showId, hideId) {
  if (cond) {
    hide(hideId).scrollTop = 0;
    show(showId).scrollTop = 0;
  } else {
    show(hideId).scrollTop = 0;
    hide(showId).scrollTop = 0;    
  }
}
function hideIf(cond, id) {
  if (cond) {
    hide(id);
  } else {
    show(id);
  }  
}
function showIf(cond, id) {
  if (cond) {
    show(id);
  } else {
    hide(id);
  }
}
/*
 * Returns true if e and all parents are visible
 */
function isRendered(e) {
  if (e.parentElement) {
    do {
      if (e.tagName == 'BODY') {
        return true;
      }
      e = _$(e);
      if (e.getStyle('display') == 'none' || e.getStyle('visibility') == "hidden") {
        return false;
      }
    } while (e = e.parentElement);
  }
}
/*
 * Clicks $(id) on CR keypress
 * Example usage:
 *   <input type="text" size="30" id="pop-prompt-input" onkeypress="return ifCrClick('pop-prompt-ok')" />
 *   <a id="pop-prompt-ok" href="javascript:" onclick="closePrompt(true); return false" class="cmd none">&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;</a>
 */
function ifCrClick(id) {
  if (event.keyCode == 13) {
    $(id).onclick();
    event.cancelBubble = true;
    return false;
  }
}
/*
 * Clicks $(id) on ESC keypress
 */
function ifEscClick(id) {
  if (event.keyCode == 27) {
    $(id).onclick();
  }
}
function setText(id, text) {
  var e = $(id);
  e.innerText = denull(text);
  return e;
}
function text(id) {
  return $(id).innerText;
}
function value(id, def) {  // def optional, default value if value is null
  return value_($(id), def);
}
function value_(e, def) {  // def optional, default value if value is null
  var val = trim(e.value); 
  return (def && val == null) ? def : val; 
}
function setValue(id, value) {
  return setValue_($(id), value);
}
function setValue_(e, value) {
  var v = denull(value);
  if (e.tagName == "SELECT") {
    for (var i = 0; i < e.options.length; i++) {
      if (e.options[i].value == v) {
        e.options[i].selected = true;
        return e;
      }
    }
    e.options[0].selected = true;
    return e;
  }
  e.value = v;
  return e;  
}
function setCheck(id, value) {
  var e = $(id);
  e.checked = (value == true);
  return e;
}
function setChecks(id, parentId, value) {  // set all checkboxes with same ID
  var c = $$$(id, $(parentId), "INPUT");
  for (var i = 0; i < c.length; i++) {
    c[i].checked = (value == true);
  }
}
function getCheckboxes(id, parentId) {  // return all checkboxes with ID
  return $$$(id, $(parentId), "INPUT");
}
/*
 * Get all checked values of checkbox group (same ID) 
 * Returns [value,..]
 */
function getCheckedValues(id, parentId) {  
  parentId = parentId || id + "_span";  
  var c = $$$(id, $(parentId), "INPUT");
  var a = [];
  for (var i = 0; i < c.length; i++) {
    if (c[i].checked) {
      a.push(c[i].value);
    }
  }
  return a;
}
/*
 * Get all checked value/labels of checkbox group (same ID) 
 * Returns {'v':[value,..],'l':[label,..]}
 */
function getCheckedValuesAndLabels(id, parentId) {  
  parentId = parentId || id + "_span";  
  var c = $$$(id, $(parentId), "INPUT");
  var v = [];
  var l = [];
  for (var i = 0; i < c.length; i++) {
    if (c[i].checked) {
      v.push(c[i].value);
      l.push(c[i].nextSibling.innerText);
    }
  }
  return {'v':v,'l':l};
}
function setCheckedValues(id, parentId, values) {  // check on all group members whose value is in supplied values (and check off the others)
  parentId = parentId || id + "_span";  
  var c = $$$(id, $(parentId), "INPUT");
  for (var i = 0; i < c.length; i++) {
    c[i].checked = inArray(values, c[i].value);
  }  
}
function toggleCheck(id) {
  var e = $(id);
  e.checked = ! e.checked;
  return e;
}
function lrcheck(lbl) {  // label onClick for radio
  var c = lbl.previousSibling;
  if (! c.checked) c.checked = ! c.checked;  
}
function setDisabled(id, test) {  // set CSS disabled style
  var e = $(id);
  if (test) {
    e.disabled = true;
    addClass(e, "disabled");
  } else {
    e.disabled = false;
    removeClass(e, "disabled");
  }
  return e;
}
function setDisabledOnly(id, test) {  // set disabled attribute, no styling
  var e = $(id);
  if (test) {
    e.disabled = true;
  } else {
    e.disabled = false;
  }
  return e;
}
function setDisabledInput(id, test) {  // textbox
  return setDisabledElement($(id));
}
function setDisabledElement(e, test) {
  if (test) {
    e.disabled = true;
    e.style.backgroundColor = "#efefef";
    e.style.borderColor = "#dedede";
  } else {
    e.disabled = false;
    e.style.backgroundColor = "";
    e.style.borderColor = "";
  }
  return e;  
}
function isChecked(id) {
  return $(id).checked;
}
function checkedIf(test) {
  return (test) ? " checked='checked'" : "";
}
function setHtml(id, html) {
  var e = $(id);
  e.innerHTML = denull(html);
  return e;
}
function yesIf(test) {
  return (test) ? "yes" : "";
}
function yesNo(test) {
  if (test == null) return '';
  return (test) ? 'yes' :'no';
}
function nbsp(value) {
  return (nullify(value) == null) ? "&nbsp;" : value;
}
function testSelect() {
  var e = event.srcElement;
  return (e && (e.tagName == "INPUT" || e.tagName == "TEXTAREA" || e.selectable == "1"));
}
function showError(id, html) {
  show(id).innerHTML = html;
}
function showErrors(divId, errors, uiMap) {
  if (uiMap) {
    for (var id in uiMap) {
      $(uiMap[id]).style.borderColor = "";
    }
  }
  if (errors) {
    var html = "<b>Please correct the following error(s):</b><ul>";
    for (var i = 0; i < errors.length; i++) {
      html += "<li>" + errors[i].msg + "</li>";
      if (uiMap) {
        var uid = uiMap[errors[i].id];
        if (uid) {
          $(uid).style.borderColor = "red";
          // if (i == 0) focus(uid);  TODO, check if input
        }
      }
    }
    html += "</ul>";
    show(divId).innerHTML = html;
  } else {
    hide(divId);
  }
}
function errMsg(id, msg) {
  return {
    "id":id,
    "msg":msg
    };
}
function validateRequired(errs, id, label) {  // ex. validateRequired(errs, "pop-ep-myName", "Name");
  if (isBlank(value(id))) {
    errs.push(errMsg(id, msgReq(label)));
  }
}
function msgReq(name) {
  return name + " is a required field.";
}
function selectedText(id) {
  var o = $(id);
  return o.options[o.selectedIndex].text;
}
function selectedValue(id) {
  var o = $(id);
  return o.options[o.selectedIndex].value;
}
function clearChildren(p) {
  while (p.hasChildNodes()) {
    p.removeChild(p.lastChild);
  }
  return p;
}
function addOpt(sel, value, text, selected) {
  var opt = document.createElement("option");
  sel.options.add(opt);
  opt.value = denull(value);
  opt.text = denull(text);
  opt.selected = (selected == true);
  return opt;
}
function addKvsOpts(sel, jKeyValues) {
  for (var i = 0; i < jKeyValues.length; i++) {
    addOpt(sel, jKeyValues[i].k, jKeyValues[i].v, jKeyValues[i].sel);
  }  
}
function createOpts(selectId, jKeyValues) {  // [{"v":"text","k":"key","sel":true},{"v":"text","k":"key"},...]
  var sel = $(selectId);
  clearOpts(sel);
  addKvsOpts(sel, jKeyValues);
  return sel;
}
// createOptsFromObjectArray(sel, lu_types, "name", sel.value, {"t":"(No Type)","k":""}, "active", 1);
// optional defaultOption: {"t":"text","k":"key"}
function createOptsFromObjectArray(selectId, array, textProperty, selKey, defaultOption, testProperty, testValue) {
  var select = $(selectId);
  clearOpts(select);
  if (defaultOption) {
    addOpt(select, defaultOption.k, defaultOption.t);
  }
  for (var key in array) {
    var o = array[key];
    if (! testProperty || o[testProperty] == testValue) {
      addOpt(select, key, o[textProperty], key == selKey);
    }
  }
  return select;
}
function clearOpts(sel) {
  _$(sel).clean();
  return;
  var s = sel.options.length;
  for (var i = 0; i < s; i++) {
    sel.remove(0);
  }  
}
function clearRows(tbody) {
  while (tbody.children.length > 1) {
    tbody.deleteRow(1);
  }
}
function clearAllRows(tbody, keepHeader) {
  var i = (keepHeader) ? 1 : 0;
  while (tbody.children.length > i) {
    tbody.deleteRow(i);
  }
}
function checkAllCol1(c) {  // sync all column 1 checkboxes with this header checkbox
  var t = c.parentElement.parentElement.parentElement.parentElement;
  var tbody = t.children.length && t.children[1];
  if (tbody) {
    for (var i = 0; i < tbody.children.length; i++) {
      var c1 = tbody.children[i].firstChild.firstChild;
      if (c1.checked != c.checked) {
        c1.click();
      }
    }
  }
}
function unselectText() {
  try {
    document.selection.empty();
  } catch (e) {}
}
function applyProps(e, id, className, title) {
  if (id) e.id = id;
  if (className) e.className = className;
  if (title) e.title = title;
}
function buildFn(name, args) {  // buildFn("finishSession", [tid, cid]) returns "finishSession('1','2')"
  return name + argJoin(args);
}
function buildHrefFn(name, args) {
  return buildFn("javascript:" + name, args); 
}
function argJoin(a) {  // return comma-delimited string of arguments, ex. href = "saveClient" + argJoin([c.id, c.name]);
  for (var i = 0; i < a.length; i++) {
    if (a[i] != null) {
      if (isString(a[i])) {
        a[i] = "'" + esc(a[i]) + "'";
      }
    } else {
      a[i] = "null";
    }
  }
  return "(" + a.join(",") + ")";
}
function logJoin(a) {  // return comma-delimited string of arguments for logging
  if (a == null) return "()";
  var b = [];
  for (var i = 0; i < a.length; i++) {
    if (a[i] != null) {
      if (isString(a[i])) {
        b.push('"' + a[i] + '"');
      } else {
        b.push(a[i]);
      }
    } else {
      b.push("null");
    }
  }
  return "(" + b.join(", ") + ")";
}
function ellips(s, len) {
  if (s == null || s.length < len) {
    return s;
  }
  for (var i = len; i > 0; i--) {
    if (s.substr(i, 1) == " ") {
      return trim(s.substr(0, i)) + "...";
    }
  }
  return s.substr(0, len) + "...";
}
function join(a, delim, skipNulls) {
  if (! isArray(a))
    return a;
  if (skipNulls)
    a = removeNullsFromArray(a);
  return (a) ? a.join(delim) : '';
}
function bulletJoin(a, skipNulls) {  // skipNulls optional
  if (! isArray(a))
    return a;
  if (skipNulls)
    a = removeNullsFromArray(a);
  return (a) ? a.join(" <u class='bullet'>&#x2022;</u> ") : "";
}
function nbspJoin(a, skipNulls) {
  return nbsp(bulletJoin(a, skipNulls));
}
function simpleBulletJoin(a, skipNulls) {
  return join(a, ' &#x2022; ', skipNulls);
}
function dashJoin(a, skipNulls) {
  return join(a, ' - ', skipNulls);
}
function click(id) {
  $(id).click();
}
/*
 * Autosize textarea to contents
 * Attach to onkeydown, onkeyup and onkeypress
 */
function taAutosize(text) {  // autosize textarea from contents
  //text.style.height = text.scrollHeight - 4 + 'px';
}
function getHeight(id) {  
  return $(id).clientHeight;
//  var e = $(id);
//  return e.clientHeight ? e.clientHeight : e.offsetHeight;
}
function setHeight(id, value) {  // if using getHeight() as value make sure $(id) has zero padding
  $(id).style.height = value;
}
function jsonUrl(o) {  // encode object for passing as URL query string
  return encodeURIComponent(toJSONString(o))
}
function sgn(x) {
  return (x > 0) | -(x < 0);
}
function showColumnIf(tblId, colNo, cond) {
  var rows = $(tblId).getElementsByTagName("TR");
  for (var i = 0; i < rows.length; i++) {
    rows[i].cells[colNo].style.display = (cond) ? "block" : "none";
  }
}
var sce;
function scrollBottom(divId) {
  var e = $(divId);
  e.scrollTop = e.scrollHeight;
  return e; 
}
function scrollTo(divId, id, padding) {  // scroll to element within scrollable DIV, padding (optional) to adjust scroll calculation (e.g. to accommodate a fixed header row)
  var e = (id) ? $(id) : null;
  scrollToElement(divId, e, padding);
}
function scrollToElement(divId, e, padding) {
  var div = _$(divId);
  var to = (e) ? e.offsetTop : 0;
  if (padding) {
    to = (to < padding) ? 0 : to - padding;
  }
  sce = {
      div:div,
      to:to,
      inc:sgn(to - div.scrollTop),
      speed:1.4};
  sce.intId = setInterval("scrollToLoop()", 1);
}
function scrollToLoop() {
  if (sce) {
    var top = sce.div.scrollTop + sce.inc;
    if ((sce.inc < 0 && top < sce.to) || (sce.inc > 0 && top > sce.to)) {
      clearInterval(sce.intId);
      top = sce.to;
      sce.div.scrollTop = top;
      sce = null;
    } else {
      sce.div.scrollTop = top;
      sce.inc = sce.inc * sce.speed;
    }
  } 
}
function scrollToTr(id, offset) {  // scroll to a TBODY <TR> for a table inside a fixed-height scrollable DIV
  var tr = $(id);
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
function rgbString(rgb) {
  return "rgb(" + rgb.join(",") + ")";
}
function hexToNumbers(s) {
  if (s == "transparent") return [255, 255, 255];
  var a = [];
  s = s.replace(/#/, "");
  s.replace(/(..)/g, function($1) {
      a.push(parseInt($1, 16));
      });
 return a;
}
function surgefade(p) {
  if (p == null || p.rgb) return;
  var rgb1 = hexToNumbers(p.currentStyle["backgroundColor"]);
  var rgb0 = [255,255,128];
  p.rgb = rgb1;
  p.rgbOff = [rgb0[0] - rgb1[0], rgb0[1] - rgb1[1], rgb0[2] - rgb1[2]]; 
  p.style.backgroundColor = rgbString(rgb1);
  p.fdix = 0;
  p.fdmax = 20;
  if (p.id == "") {
    p.id = Math.random();
  }
  setTimeout("surgefadestart('" + p.id + "')", 250);
}
function surgefadestart(pid) {
  var p = $(pid);
  if (p != null) { 
    p.timer = setInterval("surgefadeloop('" + pid + "')", 1);
  }
}
function surgefadeloop(pid) {
  var p = $(pid);
  if (p != null) {
    p.fdix++;
    var m = p.fdix / p.fdmax;
    var rgb = [p.rgb[0] + p.rgbOff[0] * m, p.rgb[1] + p.rgbOff[1] * m, p.rgb[2] + p.rgbOff[2] * m];
    p.style.backgroundColor = rgbString(rgb);
    if (p.fdix == p.fdmax) {
      clearInterval(p.timer);
      p.style.backgroundColor = "";
      p.rgb = null;
      fade(p, 20, 500);
    }
  }
}
function highlight(p) {
  p.rgb0 = hexToNumbers(p.currentStyle["backgroundColor"]);
  p.rgb1 = [255,255,128];
  p.style.backgroundColor = rgbString(p.rgb1);
}
function highlightOff(p) {
  p.style.backgroundColor = rgbString(p.rgb0);
}
function fade(p, max, pause, callback) {  
  if (p == null || p.rgb) return;
  if (p.rgb0 == null)
    highlight(p);
  var rgb0 = p.rgb0;
  var rgb1 = p.rgb1;
  p.rgb = rgb1;
  p.rgbOff = [rgb0[0] - rgb1[0], rgb0[1] - rgb1[1], rgb0[2] - rgb1[2]]; 
  p.style.backgroundColor = rgbString(rgb1);
  p.fdix = 0;
  p.fdmax = (max) ? max : 40;
  p.fdcallback = callback;
  if (p.id == "") {
    p.id = Math.random();
  }
  setTimeout(function(){fadestart(p.id)}, pause || 2000);
}
function fadestart(pid) {
  var p = $(pid);
  if (p != null) { 
    p.timer = setInterval("fadeloop('" + pid + "')", 1);
  }
}
function fadeloop(pid) {
  var p = $(pid);
  if (p != null) {
    p.fdix++;
    var m = p.fdix / p.fdmax;
    var rgb = [p.rgb[0] + p.rgbOff[0] * m, p.rgb[1] + p.rgbOff[1] * m, p.rgb[2] + p.rgbOff[2] * m];
    p.style.backgroundColor = rgbString(rgb);
    if (p.fdix == p.fdmax) {
      clearInterval(p.timer);
      p.style.backgroundColor = "";
      p.rgb = null;
      p.rgb0 = null;
      var cb = p.fdcallback;
      if (cb) {
        cb();
        p.fdcallback = null;
      }
    }
  }
}
function deflate(e) {
  if (e == null || e.deflate) return;
  if (e.id == "") e.id = Math.random();
  var div = 
  e.deflate = {
      inc:0.05,
      zoom:1
      };
  e.deflate.pos = getDimensions(e);
  e.deflate.w2 = e.deflate.pos.width / 2
  e.deflate.h2 = e.deflate.pos.height / 2
  e.style.width = e.deflate.pos.width * .9;
  e.style.height = e.deflate.pos.height * .9;
  //setDimensions(e, e.deflate.pos);
  //e.style.position = 'absolute';
  //e.placeholder = createPlaceholder(e);
  //insertAfter(e, e.placeholder);
  //setDimensions(e.placeholder, e.deflate.pos);
  e.deflate.interval = setInterval("deflateLoop('" + e.id + "')", 1);
}
function createPlaceholder(e) {
  var p = document.createElement(e.tagName);
  p.className = e.className;
  p.style.margin = e.currentStyle.margin;
  p.style.padding = e.currentStyle.padding;
  p.style.visibility = 'hidden';
  return p; 
}
function deflateLoop(id) {
  var e = $(id);
  if (e == null || e.deflate == null) return;
  var done = false;
  var s = e.deflate;
  var limit = 0.01
  var zoom = s.zoom - s.inc;
  if (zoom < limit) {
    zoom = limit;
    done = true;
  }
  e.style.zoom = zoom;
  e.style.marginLeft = -(zoom - 1) * s.w2; // s.pos.left - (zoom - 1) * s.w2; 
  //e.style.marginTop = -(zoom - 1) * s.h2;  // s.pos.top - (zoom - 1) * s.h2; 
  if (done) {
    clearInterval(s.interval);
    e.deflate = null;
    //e.style.display = 'none';
    e.parentElement.removeChild(e);
  } else {
    e.deflate.zoom = zoom;
  }
}
function swell(e, isHidden, to, inc) {
  if (e == null || e.swell) return;
  if (e.id == "") e.id = Math.random();
  if (isHidden) {
    e.style.display = "block";
  }
  e.swell = {
      to:to || 4,   
      inc:inc || 0.2,
      dir:1,
      zoom:1,
      hide:isHidden
      };
  e.swell.dir = 1;
  e.swell.zoom = 1;
  e.swell.pos = absPos(e);
  e.swell.interval = setInterval("swellLoop('" + e.id + "')", 1);
}
function swellLoop(id) {
  var e = $(id);
  if (e == null || e.swell == null) return;
  var done = false;
  var s = e.swell;
  var limit = (s.dir == 1) ? s.to : 1;
  var zoom = s.zoom + s.inc * s.dir;
  if ((s.dir * (zoom - limit)) > 0) {
    zoom = limit;
    if (s.dir == -1) {
      done = true;
    } else {
      e.swell.dir = -1;
    }
  }
  e.style.zoom = zoom;
  e.style.left = s.pos.left - (zoom - 1) * (s.to * 2);
  e.style.top = s.pos.top - (zoom - 1) * (s.to * 2);
  if (done) {
    clearInterval(s.interval);
    if (s.hide) {
      e.style.display = "none";
    }
    e.swell = null;
  } else {
    e.swell.zoom = zoom;
  }
}
function removeHtmlFormatting(d) {
  d = d.replace(/<b>/g, "");
  d = d.replace(/<\/b>/g, "");
  d = d.replace(/<u>/g, "");
  d = d.replace(/<\/u>/g, "");
  return d;
}
function crlfToBr(s) {
  return s.replace(/\r\n/g, '<br/>');
}
function brToCrlf(s) {
  return s.replace(/<br\/>/g, '\r\n');
}
function absPos(e) {
  var cl = 0;
  var ct = 0;
  if (e.offsetParent) {
    do {
      cl += e.offsetLeft;
      ct += e.offsetTop;
    } while (e = e.offsetParent);
  }
  return {
    left:cl,
    top:ct
  }
}
function getDimensions(e) {
  var ap = absPos(e);
  return {
    left:ap.left,
    top:ap.top,
    height:e.offsetHeight,  // e.clientHeight ? e.clientHeight : e.offsetHeight,
    width:e.offsetWidth  // clientWidth ? e.clientWidth : e.offsetWidth
  }
}
function setDimensions(e, dim) {
  e.style.left = dim.left;
  e.style.top = dim.top;
  e.style.height = dim.height;
  e.style.width = dim.width;
}
function syncHeights(ids, min) {
  var h = (min) ? min : 0;
  for (var i = 0; i < ids.length; i++) {
    var hi = _$(ids[i]).getDim().height;
    if (hi > h) h = hi;
  }
  for (var i = 0; i < ids.length; i++) {
    _$(ids[i]).setHeight(h);
  }  
}
function center(e) {
  e.clientHeight;
  var top = document.documentElement.offsetHeight / 2 - (e.clientHeight) / 2;
  if (top < 0) top = 0;
  var left = document.documentElement.offsetWidth / 2 - (e.clientWidth) / 2;
  if (left < 0) left = 0;
  e.style.top = top + document.documentElement.scrollTop;
  e.style.left = left + document.documentElement.scrollLeft;
  return e;
}
function centerWithin(e1, e2) {  // center e1 within e2
  var d1 = getDimensions(e1);
  var d2 = getDimensions(e2);
  e1.style.left = (d2.left + d2.width / 2) - d1.width / 2;
  e1.style.top = (d2.top + d2.height / 2) - d1.height / 2;
}
function insertTag(e, parent) {
  parent = parent || $('bodyContainer');
  parent.appendChild(e);
  return e;
}
function overlayWorking(on, e) {  // e optional, for centering within element rather than page
  var w = $("working-float");
  if (w == null) {
    if (! on) return;
    w = insertTag(createDiv('working-float'));
  }
  w = _$(w);
  w.style.display = (on) ? "block" : "none";
  if (on) {
    if (e) {
      w.centerWithin(e);
    } else {
      w.center();
    }
  }
}
/*
 * Async call 
 */
function call(fn) {
  setTimeout(fn, 1);
}
function overlayWorkingCall(fn) {
  overlayWorking(true);
  setTimeout(fn, 1);
}
function overlayWorkingTable(divId, on) {
  var w = $("working-float");
  var div = $(divId);
  var d = getDimensions(div);
  if (d.height = 0 || d.width == 0) return; 
  w.style.display = (on) ? "block" : "none";
  centerWithin(w, div);
}
function setWorkingTable(divId, on, text) {
  var div = $(divId);
  var table = div.firstChild;
  if (on) {
    addClass(div, "working-table");
    table.style.display = "none";
    var wtext = createDiv(null, "working-text", (text) ? text : "Working")
    //wtext.style.lineHeight = div.style.height;
    div.appendChild(wtext);
    div.wtext = wtext;
  } else {
    removeClass(div, "working-table");
    table.style.display = "";
    if (div.wtext) {
      div.removeChild(div.wtext);
    }
  }
}
/*
 * Attach event handler to element
 * - event: string event name without 'on', e.g. 'mousedown'
 * - fn: function to call on event
 */
function attachEventHandler(e, event, fn) {
  e.attachEvent('on' + event, fn);
}
/*
 * Recursively seek a direct ancestor containing given property value
 * Returns element if found, else null   
 */
function findAncestorWith(e, propName, propValue) {
  if (e[propName] == propValue) {
    return e;
  }
  if (e.parentElement == null) {
    return null;
  }
  if (e.parentElement.tagName == 'BODY') {
    return null;
  }
  return findAncestorWith(e.parentElement, propName, propValue);
} 
function findEventAncestorWith(propName, propValue) {
  if (event && event.srcElement) {
    return findAncestorWith(event.srcElement, propName, propValue);
  }
}
function hideCombos() {
  if (Page.browser.isMsie6()) {   
    var s = document.getElementsByTagName("select");
    for (var i = 0; i < s.length; i++) { 
      var cb = s[i]; 
      if (isRendered(cb)) {
        var t = document.createElement("input");
        if (cb.selectedIndex > -1) {
          t.value = cb.options[cb.selectedIndex].text;
        }
        t.style.position = 'absolute';
        t.style.width = cb.clientWidth; 
        t.style.height = cb.clientHeight; 
        t.style.padding = "1px 15px 0 3px"; 
        t.style.margin = "0";
        t.style.fontSize = cb.currentStyle.fontSize;
        t.style.fontFamily = cb.currentStyle.fontFamily;
        cb.parentElement.insertBefore(t, cb); 
  //      cb.style.display = "none"; 
        cb.style.visibility = 'hidden';
        cb.tag = 1; 
      }
    }
  } 
} 
function restoreCombos(container) {
  if (Page.browser.isMsie6()) {   
    container = container || document; 
    var s = container.getElementsByTagName("select"); 
    for (var i = 0; i < s.length; i++) { 
      var cb = s[i]; 
      if (cb.tag) { 
        var t = cb.previousSibling; 
        cb.parentElement.removeChild(t); 
  //      cb.style.display = "";
        cb.style.visibility = 'visible';
        cb.tag = null;
      }
    }
  }
} 
function focus(id) {
  setTimeout("finishFocus('" + id + "')", 10);
}
function finishFocus(id) {
  var e = $(id);
  try {
    e.focus();
    e.select();
  } catch (e) {
  }
}
function focus_(e) {
//  setTimeout(function(){
    try {
      e.focus();
      e.select();
    } catch (ex) {}
//  }, 1);
}
function blur(id) {  // to fix silly IE input blur bug
  var i = $(id);
  var t = i.value;
  i.value = "";
  i.value = t;
}
var psh = false;
function hidePageScroll() {
  if (! psh) {
    if (document.body.id != "console-body") {
      document.documentElement.style.overflow = "hidden";
      document.documentElement.style.borderRight = '16px solid #D2E3E0';
      psh = true;
    }
  }
}
function showPageScroll() {
  if (document.body.id != "console-body") {
    document.documentElement.style.overflow = "";
    document.documentElement.style.borderRight = "";
  }
  psh = false;
}
function doWork(f, msg, noCurtain, stay) {
  Pop.Working.show(msg, stay); 
  setTimeout(f, 10);
}
var hga;  // hourglass anchor
function doHourglass(f, anchor) {
  hga = (anchor) ? anchor : (event) ? event.srcElement : null;
  if (hga) {
    hga.style.cursor = "wait";
  }
  document.body.style.cursor = "wait";
  setTimeout(f, 1);
}
function closeHourglass() {
  if (hga) {
    hga.style.cursor = "";
  }
  document.body.style.cursor = "";
}
var UNDEFINED = 'undefined';  // if (typeof(x) == UNDEFINED) ..
function px(i) {
  return (i === null) ? '' : (isNaN(i)) ? i : parseInt(i, 10) + 'px';
}

function calculateBmiValues(vW, vWu, vH, vHu) {
  var w = floatValOrZero(vW);
  var h = floatValOrZero(vH);
  if (w == 0 || h == 0) return null;
  var wm = (vWu == 'Kilograms');
  var hm = (vHu == 'cm');
  var n, d;
  if (! wm && ! hm) {  // lbs-in
    n = w * 703;
    d = h;
  } else if (wm && hm) {  // kg-cm
    n = w;
    d = h / 100;
  } else if (wm && ! hm) {  // kg-in
    n = w;
    d = h * 0.0254;
  } else {  // lbs-cm
    n = w * 703;
    d = h * 0.3937;
  }
  return Math.round((n / (d * d)) * 10) / 10;
}
