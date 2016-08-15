var C_TrackItem;
/**
 * Order Sheet
 * Static pop controller
 */
var OrderSheet = {
  items:null,  // {key:TrackItem,..}
  callback:null,
  /*
   * Show order sheet
   * @arg items [OrderItem,..] 
   * @callback() on save
   */
  pop:function(orderItems, callback) {
    Html.Window.working(true);
    this.callback = callback;
    var self = this;
    Ajax.Tracking.order(orderItems, 
      function(tracksheet) {
        self._load(tracksheet);
        Pop.show('pop-os');
        Html.Window.working(false);
      });
  },
  /*
   * @arg tracksheet {'items':..,'add':..}
   */
  _load:function(tracksheet) {  
    this.items = {};
    var trackItems = tracksheet.items;
    if (trackItems) {
      var t = new TableLoader('os-tbody', null, 'os-div');
      var checked;
      var self = this;
      for (var i = 0; i < trackItems.length; i++) {
        var item = trackItems[i];
        t.createTr(item.trackCat);
        item.ui = {};
        item.ui.sel = createCheckbox();
        item.ui.sel.tr = t.tr;
        item.ui.sel.onclick = function(){self._setRowColor(this)}; 
        item.ui.cat = createSelect(null, null, C_TrackItem.TCATS, item.trackCat);
        item.ui.priority = createSelect(null, null, C_TrackItem.PRIORITIES, item.priority);
        item.ui.notes = createTextbox(null, item.orderNotes, '40', 'text');
        t.createTd('check');
        t.append(item.ui.sel);
        t.createTd();
        t.append(item.ui.cat);
        if (tracksheet.add)
          checked = item.trackCat != C_TrackItem.TCAT_OTHER;
        else
          checked = item.trackItemId != null;
        item.ui.sel.checked = checked;
        this._setRowColor(item.ui.sel);
        t.createTd(null, item.trackDesc);
        t.createTd();
        t.append(item.ui.priority);
        t.createTd();
        t.append(item.ui.notes);
        this.items[item.key] = item;
      }
    }      
  },
  _setRowColor:function(e) {
    if (e.tr) 
      e.tr.className = (e.checked) ? 'off' : 'disable';
  },
  pClose:function() {
    Pop.close();
  },
  pSave:function() {
    Html.Window.working(true);
    var saveItems = [];
    for (var key in this.items) {
      var item = this.items[key];
      var ui = item.ui;
      if (ui.sel.checked) {
        item.trackCat = value_(ui.cat);
        item.priority = value_(ui.priority);
        item.orderNotes = value_(ui.notes);
        delete(item.ui);
        saveItems.push(item);
      }
    }
    var self = this;
    Ajax.Tracking.saveOrder(saveItems, 
      function() {
        Html.Window.working(false);
        Pop.close();
        if (self.callback)
          self.callback();
      });
  }
};
/**
 * Order Item
 * Data class
 */
function OrderItem(cid, sid, key, tcat, tdesc, cpt, icd, diag, icd10) {
  this.cid = cid;
  this.sid = sid;
  this.key = key;
  this.tcat = tcat;
  this.tdesc = tdesc;
  this.cpt = cpt;
  this.icd = icd;
  this.diag = diag;
  this.icd10 = icd10;
}
OrderItem.prototype = {
  cid:null,
  sid:null,
  key:null,
  tcat:null,
  tdesc:null,
  cpt:null
}
/*
 * Static key builder
 * Returns qidi#oix: '21600@2131#19' 
 */
OrderItem.buildKey = function(qidi, oix) {
  return qidi + '#' + oix;
}
OrderItem.fromFs = function(fs, q, opt) {
  return new OrderItem(
    fs.client.clientId, 
    '0', 
    OrderItem.buildKey(q.id, opt.oix),
    (opt.tcat) ? opt.tcat : '99',
    (opt.desc) ? opt.desc : opt.text,
    opt.cpt);
}