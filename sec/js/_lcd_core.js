/**
 * LCD FRAMEWORK: CORE
 * @version 1.1
 * @author Warren Hornsby
 */
function async(fn) {
  return setTimeout(fn, 5);
}
/*
 * pause(0.5, function() {
 *   doStuff();
 * })
 */
function pause(seconds, fn) {
  return setTimeout(fn, seconds * 1000);
}
/*
 * var i = 0;
 * loop(function(exit) {  
 *   dostuff(i++);
 *   if (i > 10)  
 *     exit();
 * })
 */
function loop(fn, ms) {
  fn.timer = setInterval(fn.curry(function(onfinish) {
    fn.timer = clearInterval(fn.timer);
    if (onfinish)
      onfinish();
  }), ms || 1);
}
/*
 * Same as loop() using window.requestAnimationFrame
 */
function aloop(fn) {
  Html.Window.setReqAnimFrame();
  (function loop() {
    if (! loop.cancel) {
      fn(function(onfinish) {
        loop.cancel = 1;
      })
      Html.Window.reqAnimFrame(loop);
    }
  })();
}
/*
 * work(this, function() {
 *   this.loadFacesheet();
 * })
 */
function work(e, fn) {
  if (fn == null) {
    fn = e;
    e = Html.Window;
  } else {
    fn = fn.bind(e);
  }
  e.working(function() {
    fn();
    e.working(false);
  })
}
/**
 * Class 
 */
var Class = {
  /*
   * Define constructor function and prototype/static methods
   */
  define:function(constructor, prototype, statics) {
    constructor = constructor || new Function();
    constructor.prototype = prototype;
    Class.augment(constructor, null, statics);
    return constructor;
  },
  /*
   * Augment existing class with additional prototype/static methods
   */
  augment:function(object, prototype, statics) {
    for (var name in prototype)
      object.prototype[name] = prototype[name];
    for (var name in statics)
      object[name] = statics[name];
    return object;
  }
}
/**
 * Object
 */
Class.augment(Object, null, 
  {  // statics
    is:function(e) {
      return e && typeof e === 'object';  // returns true for arrays as well
    },
    isUndefined:function(e) {
      var u;
      return e === u;
    },
    instance:function(o) {
      function Obj() {}
      Obj.prototype = o;
      return new Obj();
    },
    /*
     * Create an object instance from prototype
     * @arg object prototype (optional)
     * @arg object augs (optional; omit to use standard Object._Proto) 
     * @return object instance aug'd with Object._Proto
     */
    create:function(prototype, augs) {
      var e = Object.instance(prototype);
      if (e.init)
        e.init();
      return Object.augment(e, augs);
    },
    /*
     * Create an object instance (child) by augmenting parent prototype 
     * @arg object parent prototype
     * @arg object augs (optional)
     * @return object instance 
     */
    extend:function(parent, augs) {
      // augs._parent = parent;  TODO: flawed, cannot be used beyond first level because this._parent always points to immediate parent
      // can this be resolved by wrapping extend creation in a closure giving access to a 'parent' argument, allowing each instance in hiearchy to refer to 'parent' rather than 'this.parent'?
      return this.create(parent, augs);
    },
    /*
     * Augment existing object  
     * @arg object e
     * @arg object augs (optional; omit to use standard Object._Proto) 
     * @return e augmented
     */
    augment:function(e, augs) {
      augs = augs || Object._Proto;
      for (var name in augs)
        e[name] = augs[name];
      if (augs.init)   
        e.init();
      return e;
    },
    /*
     * Deep clone object excluding HTML elements
     * @arg version omit; used during recursion
     */
    deepclone:function(e, version) {
      try {
        e._cloned = version || Math.random();
      } catch (ex) {
        return e;
      }
      var n;
      /* if (Array.is(e)) {
        n = [];
        for (var i = 0; i < e.length; i++) 
          n.push(Object.deepclone(e[i], e._cloned));
      } else */ 
      if (Array.is(e) || Object.is(e)) {
        n = {};
        for (var i in e) 
          if (Object.is(e[i]))
            if (e[i]._cloned == e._cloned || Html.is(e[i])) 
              n[i] = e[i];
            else 
              n[i] = Object.deepclone(e[i], e._cloned);
          else 
            n[i] = e[i];
      } else {
        n = e;
      }
      return n;
    },
    /*
     * Shallow-clone object
     */
    clone:function(e) {
      var n;
      if (Array.is(e)) {
        n = [];
        for (var i = 0; i < e.length; i++) 
          n.push(Object.clone(e[i]));
      } else if (Object.is(e)) {
        n = {};
        for (var i in e) 
          n[i] = e[i];
      } else {
        n = e;
      }
      return n;
    },
    /*
     * Base object prototype
     */
    _Proto:{
      /*
       * Create an instance from this
       * @return object instance
       */
      create:function() {
        function Obj() {}
        Obj.prototype = this;
        return new Obj();
      },
      /*
       * Extend a prototype from this
       * @arg object proto
       * @return object prototype
       */
      extend:function(proto) {
        return Object.extend(this, proto);
      },
      /*
       * Augment this
       * @arg object augs
       * @return this
       */
      aug:function(augs) {
        Object.augment(this, augs);
        return this;
      },
      /*
       * Apply this to that
       * @arg object that
       * @return that;
       */
      apply:function(that) {
        Object.augment(that, this);
        if (this.onapply)
          that.onapply();
        return that;
      },
      /*
       * @arg string event  
       * @arg object|fn to context|function
       * @arg string toEvent (optional, default same as event if context supplied)
       * e.g. bubble('onsave', this)
       *      bubble('onempty', this.join_onempty.bind(this))
       *      bubble('onclick', this, 'onabstract')
       */
      bubble:function(event, to, toEvent) {
        if (Function.is(to)) 
          this[event] = to;
        else
          this[event] = function(){return to[toEvent || event].apply(this, arguments)};
        return this;
      },
      /*
       * @arg string fid
       * @arg mixed value
       */
      set:function(fid, value) {
        if (fid)
          this[fid] = value;
        return this;
      }
    }
  });
/** 
 * Object Array
 * @example
 *   fromRecs:function(recs) {
 *     return Object.Array.create(recs, this.fromRec.bind(this));
 *   }, ..
 */
Object.Array = {
  create:function(recs, creator) {
    var array = [];
    recs.forEach(function(rec) {
      array.push(creator(rec));
    })
    return array;
  }
}
/** 
 * Rec
 * Can "revive" simple data recs (e.g. deserialized JSON)  
 */
Object.Rec = Object.create({
  /*  
   * @events
   */
  onload:function(json) {},
  /*
   * @abstract
   * @return Rec prototype for reviving; can be overriden to use different prototype 
   */
  getProto:function(json) {
    return this;
  },
  /*
   * @arg object json {fid:value,..}
   * @return Rec aug'd with json data
   */
  revive:function(json) {
    if (Array.is(json))
      return this.reviveAll(json);
    return this._revive(json || {}, this.getProto(json));
  },
  _revive:function(json, proto) {
    var rec = Object.create(proto, json);
    rec.onload(json);
    return rec;
  },
  /*
   * @arg string fid
   * @arg Rec proto
   * @example
   *   MsgThread = Object.Rec.extend({
   *     onload:function() {
   *       this.setr('facesheet', MsgFacesheet);
   *     },..
   */
  setr:function(fid, proto) {
    var json = this[fid];
    if (json) 
      this[fid] = proto.revive(json);
  },
  /*
   * @arg object[]|map json
   * @return [Rec,..]|{id:Rec,..} aug'd with json data 
   */
  reviveAll:function(jsons) {
    if (jsons)
      if (Array.is(jsons))
        return this._fromArray(jsons);
      else 
        return this._fromMap(jsons);
  },
  /*
   * @return Rec
   */
  clone:function() {
    return this.revive(Json.decode(Json.encode(this, true)));
  },
  /*
   * @arg object proto e.g. from Rec::getStaticJson()
   */
  constants:function(proto) {
    this.aug(proto);
    return this;
  },
  //
  _fromArray:function(array) {
    for (var i = 0, l = array.length; i < l; i++) 
      array[i] = this.revive(array[i], i);
    return Object.augment(array);
  },
  _fromMap:function(map) {
    for (var i in map) {
      if (! Function.is(map[i])) 
        map[i] = this.revive(map[i], i);
    }
    return Object.augment(map);
  }
})
/**
 * Array RecArray
 */
Object.RecArray = Object.create({
  /*
   * @abstract
   * @arg json of item
   * @arg i index of item
   * @return Rec prototype for array items
   */
  getItemProto:function(json, i) {},
  /*
   * @events
   */
  ondecorateitem:function(json, i) {},  // prior to revive of item
  onloaditem:function(item, i) {},  // after revive of item
  onload:function(itemProto, jsons) {},  // after revive of all
  //
  match:function(text) {
    var search = text && text.asRegExp();
    var self = this;
    var arr = Array.filter(self, function(rec) {
      return (self.isMatch(rec, search)) ? rec : null;
    })
    arr.sort(function(a, b){return self.lev(a, text) - self.lev(b, text)});
    return arr;
  },
  lev:function(rec, search) {
    return search.lev(rec.name);  // @abstract
  },
  isMatch:function(rec, search) {
    return (rec.name && rec.name.match(search));  // @abstract
  },
  /*
   * @arg array|map jsons
   * @arg object proto (optional, will use getItemProto if not supplied)
   */
  revive:function(jsons, proto) {
    jsons = jsons || [];
    var array = this._reviveAll(jsons, proto, this.ondecorateitem.bind(this), this.onloaditem.bind(this));
    array.aug(this).onload(proto, jsons);
    array.itemProto = proto;
    return array;
  },
  _reviveAll:function(jsons, proto, ondecorateitem, onloaditem) {
    if (jsons)
      if (Array.is(jsons))
        return this._fromArray(jsons, proto, ondecorateitem, onloaditem);
      else
        return this._fromMap(jsons, proto, ondecorateitem, onloaditem);
  },
  _fromArray:function(array, proto, ondecorateitem, onloaditem) {
    for (var i = 0, l = array.length; i < l; i++) {
      if (ondecorateitem)
        ondecorateitem(array[i], i);
      var p = proto || this.getItemProto(array[i], i);
      array[i] = p.revive(array[i], i);
      if (onloaditem) 
        onloaditem(array[i], i);
    }
    return Object.augment(array);
  },
  _fromMap:function(map, proto, ondecorateitem, onloaditem) {
    for (var i in map) { 
      if (ondecorateitem)
        ondecorateitem(map[i], i);
      var p = proto || this.getItemProto(map[i], i);
      map[i] = p.revive(map[i], i);
      if (onloaditem)
        onloaditem(map[i], i);
    }
    return Object.augment(map);
  },
  /*
   * Shorthand form; requires record is defined prior to array
   * @example Diagnoses = Object.RecArray.of(Diagnosis, {..  // Diagnosis must be defined prior
   */
  of:function(proto, augs) {
    var me = this.extend({
      getItemProto:function(){return proto}
    })
    if (augs) 
      me.aug(augs);
    return me;
  },
  /* 
   * Generic AJAX: fetch all/autocomplete
   */
  ajax_fetchAll:function(fetcher, callback) {
    var self = this;
    if (self.cache) {
      if (callback)
        callback(self.cache);
    } else {
      if (self._fetchingall) {
        pause(0.5, function() {  // already fetching, try again until cache is available 
          self.ajax_fetchAll(fetcher, callback);
        })
      } else {
        self._fetchingall = true;
        fetcher(function(recs) {
          self.cache = recs;
          self._fetchingall = false;
          if (callback)
            callback(recs);
        })
      }
    }
  },
  ajax_fetchMatches:function(fetchAll, text, callback) {
    var self = this;
    self._matchingtext = text;
    fetchAll(function(recs) {
      if (self._matchingtext == text)
        callback(recs.match(text));
    })
  },
  resetCache:function() {
    this.cache = null;
  }
})
/**
 * Rec LevelRec
 */
Object.LevelRec = Object.Rec.extend({
  /*
   * @abstract
   */
  getGroupLevelProto:function(json, i) {
    return this;
  },
  resetDirty:function() {
    this._dirty = false;
  },
  set:function(fid, value) {
    value = String.nullify(value);
    if (this[fid] != value) {
      this[fid] = value;
      this._dirty = true;
    }
  },
  isDirty:function() {
    return this._dirty;
  },
  //
  getProto:function(json, i) {
    return (json.userGroupId == 0) ? this : this.getGroupLevelProto(json, i); 
  },
  isAppLevel:function() {
    return this.userGroupId == 0;
  }
})
/**
 * Collection
 */
Object.Collection = {
  items:{},
  count:0,
  add:function(item, key) {
    if (! this.has(key))
      this.count--;
    this.items[key] = item;
    this.count++;
  },
  remove:function(key) {
    if (! this.isEmpty(key)) {
      delete this.items[key];
      this.count--;
    }
  },
  get:function(key) {
    return this.items[key];
  },
  getValues:function() {
    var a = [];
    for (var key in this.items) 
      a.push(this.items[key]);
    return a;
  },
  isEmpty:function() {
    return this.count == 0;
  },
  has:function(key) {
    return key in this.items;
  }
}
/**
 * Map
 */
var Map = {
  is:function(e) {
    return e && typeof e === 'object' && ! Array.is(e);
  },
  isEmpty:function(map) {
    return this.length(map) == 0;
  },
  length:function(map) {
    var ct = 0;
    for (var i in map) 
      ct++;
    return ct;
  },
  forEach:function(map, fn) {
    Map.each(map, fn);
  },
  each:function(map, fn) {
    for (var i in map)
      fn.call(map, map[i], i);
  },
  eachByKey:function(map, fn) {  // same as each(), but in ascending key order
    var keys = Map.keys(map).sort();
    keys.each(function(key) {
      fn.call(map, map[key], key);
    })
  },
  eachByValue:function(map, fn) {  // same as each(), but in ascending value order
    var a = [];
    Map.each(map, function(value, key) {
      a.push([value, key]);
    })
    a.sort();
    a.each(function(e) {
      fn(e[0], e[1]);
    })
  },
  pushInto:function(map, index, item) {
    if (map[index] == null) 
      map[index] = [];
    map[index].push(item);
    return map;
  },
  unshiftInto:function(map, index, item) {
    if (map[index] == null) 
      map[index] = [];
    map[index].unshift(item);
  },
  appendInto:function(map, index, array) {
    if (map[index] == null) 
      map[index] = array;
    else 
      map[index] = map[index].append(array);
  },
  /*
   * @arg map/array e
   * @arg string|fn keyFid (optional, to use e[i].keyFid as key; by default, key=i)
   * @arg string|fn valueFid (optional, to use e[i].valueFid as value; by default, value=e[i])
   * @return {key:value,..}
   * Examples:
   *   Map.from(['a','b','c']) => {'0':'a','1':'b','2':'c'}
   *   Map.from([{'id':'k1','name':'alpha'},{'id':'k2','name':'beta'}], 'id', 'name') => {'k1':'alpha','k2':'beta'}
   *   Map.from({'k1':{'name':'alpha'},'k2':{'name':'beta'}}, null, 'name') => {'k1':'alpha','k2':'beta'}
   */
  from:function(e, keyFid, valueFid) {
    var m = {}, key, value;
    if (Array.is(e)) {
      for (var i = 0; i <  e.length; i++) {
        key = (keyFid) ? (Function.is(keyFid) ? keyFid(e[i]) : e[i][keyFid]) : i;
        value = (valueFid) ? (Function.is(valueFid) ? valueFid(e[i]) : e[i][valueFid]) : e[i];
        m[key] = value;
      }
    } else if (Map.is(e)) {
      for (var i in e) {
        key = (keyFid) ? (Function.is(keyFid) ? keyFid(e[i]) : e[i][keyFid]) : i;
        value = (valueFid) ? (Function.is(valueFid) ? valueFid(e[i]) : e[i][valueFid]) : e[i];
        m[key] = value;
      }
    }
    return m;
  },
  /*
   * @arg map map {'id1':e1,'id2':e2,..}
   * @arg array ids ['id2','id4',..]
   * @return {'id2':e2,'id4':e4,..}
   */
  extract:function(map, ids) {
    var m = {};
    for (var i = 0; i < ids.length; i++) 
      m[ids[i]] = map[ids[i]];
    return m;
  },
  /*
   * @arg object[] maps
   * @return object
   */
  combine:function(maps) {
    var combined = maps[0];
    for (var i = 1, l = maps.length; i < l; i++) 
      for (var fid in maps[i]) 
        combined[fid] = maps[i][fid];
    return combined;
  },
  /*
   * @arg map e {'k':v,..}
   * @return array ['k',..]
   */
  keys:function(e) {
    var a = [];
    for (var i in e)
      a.push(i);
    return a;
  },
  /*
   * @arg map e {'k':v,..}
   * @arg string[] fids (optional, to return values of matching fids)
   * @return array [v,..]
   */
  values:function(e, fids) {
    var a = [];
    if (fids) 
      fids.forEach(function(fid) {
        if (fid in e)
          a.push(map[fid]);
      })
    else 
      for (var i in e)
        a.push(e[i]);
    return a;
  },
  /*
   * @arg map e
   */
  invert:function(e) {
    var map = {};
    for (var i in e) 
      map[e[i]] = i;
    return map;
  },
  /*
   * @arg map e
   * @return first element
   */
  first:function(map) {
    if (Map.is(map)) 
      for (var fid in map)
        return map[fid];
  },
  asUrlQuery:function(map) {
    a = [];
    for (var name in map) {
      if (map[name])
        a.push(name + '=' + encodeURIComponent(map[name]));
      else
        a.push(name + '=');
    }
    return a.join('&');
  }
}
/**
 * String
 */
Class.augment(String, 
  {  // prototype
    plural:function(amt, useEs) {
      var s = (useEs) ? 'es' : 's';
      var noun = (amt == 1) ? this : this + s;
      return amt + ' ' + noun;
    },
    pluralEs:function(amt) {
      return this.plural(amt, true);
    },
    beginsWith:function(e) {
      return (this.substr(0, e.length) == e);
    },
    endsWith:function(e) {
      return (this.substr(this.length - e.length) == e);
    },
    contains:function(e) {
      return this.indexOf(e) > -1;
    },
    addSlashes:function() {
      return this.replace(/([\\"'])/g, "\\$1").replace(/\0/g, "\\0");
    },
    ellips:function(len) {
      if (this.length > len) {
        for (var i = len; i > 0; i--) 
          if (this.substr(i, 1) == " ") 
            return String.trim(this.substr(0, i)) + "...";
        return this.substr(0, len) + "...";
      } else {
        return this + '';
      }
    },
    replaceBull:function(to) {
      return this.replace(/\u2022/g, to || '');
    },
    asRegExp:function() {  
      return new RegExp(String.escapeRegExp(this), 'i');  // case-insensitive
    },
    splitCamel:function() {
      return this.replace(/([A-Z])/g, ' $1').replace(/^,/, function(s){return s.toUpperCase()});
    },
    removeHtml:function() {
      return this.replace(/<b>/g, "")
        .replace(/<\/b>/g, "")
        .replace(/<u>/g, "")
        .replace(/<\/u>/g, "");
    },
    lev:function(to) {  /*Levenshtein distance*/
      var d = []; 
      var n = this.length;
      var m = to.length;
      if (n == 0) return m;
      if (m == 0) return n;
      for (var i = n; i >= 0; i--) d[i] = [];
      for (var i = n; i >= 0; i--) d[i][0] = i;
      for (var j = m; j >= 0; j--) d[0][j] = j;
      for (var i = 1; i <= n; i++) {
        var s_i = this.charAt(i - 1);
        for (var j = 1; j <= m; j++) {
          if (i == j && d[i][j] > 4) return n;
          var t_j = to.charAt(j - 1);
          var cost = (s_i == t_j) ? 0 : 1; 
          var mi = d[i - 1][j] + 1;
          var b = d[i][j - 1] + 1;
          var c = d[i - 1][j - 1] + cost;
          if (b < mi) mi = b;
          if (c < mi) mi = c;
          d[i][j] = mi;
          if (i > 1 && j > 1 && s_i == to.charAt(j - 2) && this.charAt(i - 2) == t_j) {
            d[i][j] = Math.min(d[i][j], d[i - 2][j - 2] + cost);
          }
        }
      }
      return d[n][m];
    }
  },{  // statics
    is:function(e) {
    return e !== null && typeof e == 'string';
    },
    isBlank:function(e) {
      return e == null || String.trim(e).length == 0;
    },
    from:function(e) {
      return String.denull(e);
    },
    trim:function(e) {
      return (e != null) ? (e + '').replace(/\xa0/g, '').replace(/^\s+|\s+$/g, '') : null;
    },
    denull:function(e) {
      return (e == null) ? '' : e + '';
    },
    nullify:function(e) {
      return (e == null) ? null : (String.trim(e + '') == '') ? null : e;
    },
    toInt:function(e) {
      var i = parseInt(e, 10);
      return (isNaN(i)) ? 0 : i;
    },
    toFloat:function(e) {
      var f = parseFloat(e);
      return (isNaN(f)) ? 0. : f;
    },
    hasNumber:function(e) {
      return /\d/.test(e);
    },
    zpad:function(i, len) {  // omit len for 2 digits
      if (len == null)
        return (i < 10) ? '0' + i : '' + i;
      var s = '0000000000' + i;
      return s.substr(s.length - len);
    },
    rnd:function(len) {
      return (Math.random() + '').substr(2, len || 8);
    },
    px:function(i) {
      return (i === null) ? '' : (isNaN(i)) ? i : parseInt(i, 10) + 'px';
    },
    percent:function(i) {
      return (i === null) ? '' : (isNaN(i)) ? i : parseInt(i, 10) + '%';
    },
    brToCrlf:function(s) {
      if (s) {
        s = s.replace(/<br\/>/g, '\r\n');
        s = s.replace(/<br>/g, '\r\n');
      }
      return s;
    },
    crlfToBr:function(s) {
      if (s) {
        s = s.replace(/\r\n/g, '<br/>');
        s = s.replace(/\r/g, '<br/>');
        s = s.replace(/\n/g, '<br/>');
      }
      return s;
    },
    nbspToSpace:function(s) {
      return s.replace(/\u00a0/g, ' ');
    },
    yesNo:function(b) {
      if (b == null) return '';
      return (b) ? 'yes' :'no';
    },
    nbsp:function(s) {
      return (String.nullify(s) == null) ? '&nbsp;' : s;
    },
    /*
     * @arg string page 'home.php'
     * @arg map|string args e.g. {name:value,..} or single string value to supply to 'id'
     * @arg string sessid (optional)
     * @arg bool norandomize (optional)  
     */
    url:function(page, args, sessid, norandomize) {
      var u = page;
      if (page.indexOf('?') == -1) 
        u += "?";
      var a = [];
      if (args)
        if (Map.is(args)) 
          for (var name in args) {
            if (args[name])
              a.push(name + '=' + encodeURIComponent(args[name]));
            else
              a.push(name + '=');
          }
        else 
          a.push('id=' + args);
      if (sessid)
        a.push('sess=' + sessid);
      if (! norandomize) 
        a.push(Math.random());
      return u + a.join('&');
    },
    escapeRegExp:function(s) {
      return s.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
    }
  });
/**
 * Boolean
 */
Boolean = Class.augment(Boolean, null,
  {  // statics
    toInt:function(e) {
      return (e) ? 1 : 0;
    },
    fromInt:function(e) {
      return (e == 1);
    },
    toString:function(e) {
      return (e) ? 'true' : 'false'
    },
    fromString:function(e) {
      return (e.toUpperCase() == 'TRUE');
    },
    from:function(e) {
      return (e) ? true : false;
    }
  });
/**
 * Array
 */
Class.augment(Array, 
  {  // prototype
    isEmpty:function() {
      return Array.isEmpty(this);
    },
    append:function(e) {
      if (Array.is(e)) 
        for (var i = 0; i < e.length; i++)
          this.push(e[i]);
      else
        this.push(e);
      return this;
    },
    pushIfNotNull:function(e) {
      if (e != null)
        this.push(e);
      return this;
    },
    pushBefore:function(e, i) {
      this.splice(i, 0, e);
      return this;
    },
    find:function(value, fid) {  // fid optional
      for (var i = 0, l = this.length; i < l; i++) { 
        if (fid) {
          if (this[i][fid] == value)
            return i;
        } else if (this[i] == value) { 
          return i;
        }
      }
      return -1;
    },
    has:function(item) {
      return this.find(item) > -1;
    },
    remove:function(from, to) {  // credit Resig
      var rest = this.slice((to || from) + 1 || this.length);
      this.length = from < 0 ? this.length + from : from;
      return this.push.apply(this, rest);
    },
    forEach:function(fn) {
      return Array.forEach(this, fn);
    },
    each:function(fn) {
      return Array.forEach(this, fn);
    },
    filter:function(fn) {
      return Array.filter(this, fn);
    },
    filterOn:function(fid, value) {
      return Array.filterOn(this, fid, value);
    },
    sortNumeric:function() {
      return this.sort(function(a, b){return a - b});
    },
    /*
     * Define list of methods that will automatically be invoked upon members of array (e.g. walk)
     */
    delegate:function() {
      var methods = Array.prototype.slice.call(arguments);
      var array = this;
      methods.each(function(method) {
        array[method] = function() {
          var args = Array.prototype.slice.call(arguments);
          return array.walk(method, args);
        }
      })
      return this;
    },
    /*
     * Invoke a method upon each member of array
     * @param string method
     * @param array args
     */
    walk:function(method, args) {
      var ret;
      args = Array.from(args);
      this.each(function(e) {
        var r = e[method].apply(e, args);
        ret = ret || r;
      })
      return ret;
    },
    aug:function(augs) {
      Object.augment(this, augs);
      return this;
    },
    /*
     * PHP-style functions
     */
    current:function() {
      this.__i = (this.__i == null) ? 0 : this.__i;
      return (this.__i >= 0 && this.__i < this.length) ? this[this.__i] : false;
    },
    reset:function() {
      this.__i = 0;
      return this;
    },
    first:function() {
      this.__i = 0;
      return this.current();
    },
    end:function() {
      this.__i = this.length - 1;
      return this.current();
    },
    next:function() {
      this.__i = (this.__i == null) ? 1 : this.__i + 1;
      return this.current();
    },
    prev:function() {
      this.__i = (this.__i == null) ? 1 : this.__i - 1;
      return this.current();
    },
    unset:function(i) {
      this.splice(i, 1);
      return this;
    }
  },{  // statics
    is:function(e) {
      return e != null && e.constructor == Array;
    },
    isEmpty:function(array) {
      return array == null || array.length == 0;
    },
    arrayify:function(e) {
      if (Array.is(e))
        return e;
      return e == null ? [] : [e];
    },
    /*
     * @arg mixed e
     * @arg string fid (optional, to get field values of object array)
     * Examples:
     *   Array.from(null) => []
     *   Array.from('one') => ['one']
     *   Array.from(['one']) => ['one']
     *   Array.from([{'name':'a'},{'name':'b'},{'name':'c'}], 'name') => ['a','b','c']
     *   Array.from({'k1':'a','k2':'b','k3':'c'}) => ['a','b','c']
     */
    from:function(e, fid) {
      if (Array.is(e)) {
        if (fid == null)
          return e;
        var a = [], ei;
        for (var i = 0; i < e.length; i++) {
          ei = e[i][fid];
          if (Function.is(ei))  
            ei = ei.call(e[i]);  // to construct array from results of method 'fid'
          a.push(ei);
        }
        return a;
      }
      if (e == null) 
        return [];
      if (Map.is(e)) {
        var a = [];
        for (var i in e)
          a.push(e[i]);
        return a;
      } else {
        return [e];
      }
    },
    /*
     * @arg array e [[1, 2], 3, [4, 5, 6, [7, 8]]]
     * @return array [1, 2, 3, 4, 5, 6, 7]
     */
    flatten:function(e) {
      if (Array.is(e)) {
        var arr = [];
        for (var i = 0; i < e.length; i++) 
          _flat(e[i], arr);
        return arr;
      }
      function _flat(e, arr) {
        if (Array.is(e)) 
          for (var i = 0; i < e.length; i++) 
            _flat(e[i], arr);
        else
          arr.push(e);
      }
    },
    /*
     * @arg [e,..] array
     * @arg fn(e, i) fn for each element
     */
    forEach:function(array, fn) {
      Array.each(array, fn);
    },
    each:function(array, fn) {
      if (array && array.length) 
        for (var i = 0, l = array.length; i < l; i++)
          fn.call(array, array[i], i);
      else if (Map.is(array))
        for (var i in array)
          fn.call(array, array[i], i);
    },
    /*
     * @arg [e,..] array
     * @arg string|fn(e, i) fn for each element; return e to retain, null to omit
     *                         if string, includes those where e.fid is set
     *                         if omitted, will remove nulls from array  
     * @return array 
     */
    filter:function(array, fn) {
      if (Array.is(array)) {
        if (String.is(fn)) {
          var fid = fn;
          fn = function(e){return e[fid] ? e[fid] : null};
        } else {
          fn = fn || function(e){return e ? e : null};  
        }
        var filtered = [], value;
        for (var i = 0; i < array.length; i++) {
          value = fn.call(array, array[i], i);
          if (value !== null)
            filtered.push(value);
        }
        return filtered;
      }
    },
    /*
     * @arg [rec,..] array
     * @arg string fid 
     * @arg any value (optional)
     * @return array of recs whose fid is set (or equal to optional value)
     */
    filterOn:function(array, fid, value) {
      return Array.filter(array, function(e) {
        if (value)
          return (e[fid] == value) ? e : null;
        else
          return (e[fid]) ? e : null;
      })
    },
    /*
     * Add _prev and _next links to records
     * @arg object[] recs
     * @arg bool descending (optional)
     * @return object[] 
     */
    navify:function(recs, descending) {
      if (recs) {
        var last;
        var prev = (descending) ? '_next' : '_prev';
        var next = (descending) ? '_prev' : '_next';
        recs.forEach(function(rec) {
          if (last) {
            last[next] = rec;
            rec[prev] = last;
            rec[next] = null;
          } else {
            rec._prev = null;
            rec._next = null;
          }
          last = rec;
        });
        return recs;
      }
    }
  });
/**
 * Math
 */
Class.augment(Math, null, 
  {  // statics
    sgn:function(x) {
      return (x > 0) | -(x < 0);      
    },
    isEven:function(i) {
      return (i % 2 == 0);
    },
    largest:function(array) {
      var max;
      Array.forEach(array,
        function(x) {
          if (max == null || x > max) 
            max = x;
        });
      return max;
    },
    smallest:function(array) {
      var min;
      Array.forEach(array,
        function(x) {
          if (min == null || x < min) 
            min = x;
        });
      return min;
    },
    roundTo:function(num, places) {
      if (places) { 
        var pow = Math.pow(10, places);
        return Math.round(num * pow) / pow;
      } else {
        return Math.round(num);
      }
    }
  });
/**
 * Function
 */
Class.augment(Function, 
  {  // prototype
    /*
     * @arg any argument(s) to bind 
     * @return new function calling original with bound arguments
     */
    curry:function() {
      var fn = this;
      var args = Array.prototype.slice.call(arguments);
      return function() {
        return fn.apply(fn, args.concat(Array.prototype.slice.call(arguments)));
      }
    },
    /*
     * @arg object context for execution 
     * @arg any argument(s) to bind (optional) 
     * @return new function calling original in bound execution context curried with any add'l args
     * e.g. identical to curry() except first arg is always execution context
     */
    bind:function(context) {
      var args = Array.prototype.slice.call(arguments, 1);
      var fn = this;
      return function() {
        return fn.apply(context, args.concat(Array.prototype.slice.call(arguments)));
      }
    },
    /*
     * @arg function fn
     * @return new function calling original then calling fn with same args plus result of original
     * @example
     *   getRecord:self.getRecord.append(function(id, rec) {
     *     // calls original self.getRecord(id) returns rec
     *     rec._something = id;
     *   }),
     */
    append:function(fn) {
      var fnOrig = this;
      return function() {
        var result = fnOrig.apply(fnOrig, arguments);
        return fn.apply(fn, Array.prototype.slice.call(arguments).concat(result));
      }
    },
    /*
     * @arg function fn
     * @return new function calling fn first then calling original same args
     * @example
     *   getRecord:self.getRecord.prepend(function(id) {
     *     alert(id);
     *     // calls original self.getRecord(id) returns rec
     *   }),    
     */
    prepend:function(fn) {
      var fnOrig = this;
      return function() {
        fn.apply(fn, arguments);
        return fnOrig.apply(fnOrig, arguments);
      }
    },
    /*
     * @arg function fn
     * @return new wrapper fn with ref to original fn supplied as first argument
     * @example
     *   onerror:self.onerror.extend(function(_onerror, e) {
     *     if (e.type == 'DupeRecord')
     *       _onerror(e);
     *   }),
     */
    extend:function(fn) {
      var fnOrig = this;
      return function() {
        return fn.apply(fn, [fnOrig].concat(Array.prototype.slice.call(arguments)));
      }
    },
    /*
     * @arg string text (optional)
     * @example
     *   .bubble('onclick', self.trash_onclick.confirm())
     */
    confirm:function(text) {
      text = text || 'delete this record';
      return Pop.Confirm.showYesNo.bind(Pop.Confirm, 'Are you sure you want to ' + text + '?', this);
    },
    /*
     * @arg any 
     */
    async:function() {  // do not use; loses 'this' reference
      async(this.curry.apply(this, arguments));
    }
  },
  {  // statics
    is:function(e) {
      try {  
        return /^\s*\bfunction\b/.test(e);  
      } catch (e) {
        return false;  
      }    
    },
    from:function(e) {
      return (Function.is(e)) ? e : new Function(e);
    },
    /*
     * Defer binding until execution
     * @arg object to context
     * @arg string method 'button_onclick' 
     * @example cb.del(Function.defer(self, 'del_onclick'))
     */
    defer:function(to, method) {
      return function() {
        to[method].apply(to, arguments);
      }
    }
  });
/**
 * Json
 */
var Json = {
  //
  _re:/[\x00-\x1f\\"]/,
  _stringescape:{'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"' :'\\"','\\':'\\\\'},
  //
  ERR_DECODE:'Json.decode',
  /*
   * Encodes object into JSON string
   * @arg mixed obj
   * @arg bool elimTempProps (optional, to remove underscore-prefixed props, default true)
   * @arg bool retainNulls (optional, default false)
   */
  encode:function(obj, elimTempProps, retainNulls) {
    switch (typeof obj) {
      case 'string':
        return '"' + (this._re.test(obj) ? this._stringencode(obj) : obj) + '"';
      case 'number':
      case 'boolean':
        return String(obj);
      case 'object':
        if (obj) {
          var a, val;
          switch (obj.constructor) {
            case Array:
              a = [];
              for (var i = 0, l = obj.length; i < l; i++) 
                a.push(this.encode(obj[i], elimTempProps));
              return '[' + a.join(',') + ']';
            case Object:
              a = [];
              for (var prop in obj)
                if (obj.hasOwnProperty(prop)) 
                  if (! elimTempProps || prop.substr(0, 1) != '_') { 
                    val = this.encode(obj[prop], elimTempProps);
                    if (retainNulls || val != 'null')
                      a.push('"' + (this._re.test(prop) ? this._stringencode(prop) : prop) + '":' + val);
                  }
              return '{' + a.join(',') + '}';
            case String:
              return '"' + (this._re.test(obj) ? this._stringencode(obj) : obj) + '"';
            case Number:
            case Boolean:
              return String(obj);
            case Function:
            case Date:
            case RegExp:
              return 'null';
          }
        }
        return 'null';
      case 'function':
      case 'undefined':
      case 'unknown':
        return 'null';
      default:
        return 'null';
    }
  },
  /*
   * Encodes object into JSON string suitable for passing as URL query string / HTTP form value
   */
  uriEncode:function(obj) {
    return encodeURIComponent(this._fix(this.encode(obj, true, true)));
  },
  /*
   * Decodes JSON into object/value
   */
  decode:function(string) {
    try {
      return ! (/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(string.replace(/"(\\.|[^"\\])*"/g, ''))) && eval('(' + string + ')');
    } catch (e) {
      throw Page.error(Json.ERR_DECODE, 'Json.decode(' + string + ')', e);
    }
  },
  //
  _fix:function(string) {
    string = string.replace(/\u2022/g, "&bull;")
    return string;
  },
  _stringencode:function(string) {
    var self = this;
    return string.replace(
      /[\x00-\x1f\\"]/g,
      function(a) {
        var b = self._stringescape[a];
        if (b)
          return b;
        b = a.charCodeAt();
        return '\\u00' + Math.floor(b / 16).toString(16) + (b % 16).toString(16);
      }
    )
  }
};
/**
 * Cookies  
 */
var Cookies = {
  /*
   * @arg string name 'cookieName'
   * @arg mixed value 
   * @arg int _expires minutes; omit to last for session  
   */
  set:function(name, value, _expires, _path, _domain, _secure) {
    var args = [name + '=' + escape(Json.encode(value))];
    if (_expires) {
      if (_expires == -1) {
        args.push('expires=Thu, 01-Jan-1970 00:00:01 GMT');
      } else {
        var d = new Date();
        d.setTime(d.getTime() + _expires * 60000);
        args.push('expires=' + d.toUTCString());
      }
    }
    if (_path) 
      args.push('path=' + _path);
    if (_domain)
      args.push('domain=' + _domain);
    if (_secure) 
      args.push('secure');
    document.cookie = args.join(';');
  },
  /*
   * @arg string name 'cookieName'
   * @return mixed value
   */
  get:function(name) {
    var nameValues = document.cookie.split(';');
    for (i = 0, j = nameValues.length; i < j; i++) {
      var nameValue = nameValues[i].split('=');
      if (name == nameValue[0].replace(/^\s+|\s+$/g, ""))
        return Json.decode(unescape(nameValue[1]));
    }
  },
  /*
   * @arg string name 'cookieName' 
   */
  expire:function(name, _path, _domain) {
    if (Cookies.get(name)) 
      Cookies.set(name, null, -1, _path, _domain);
  }
}
/**
 * UrlHash
 */
var UrlHash = Object.create(Object._Proto, {
  /*
   * @return {'fid':value,..} @e.g. {'action':'print','id':'12'} from page.php#action=print&id=12
   */
  get:function() {
    var map = {};
    var hash = window.location.hash;
    if (hash.length) {
      hash = hash.substr(1).split('&');
      for (var i = 0; i < hash.length; i++) {
        var e = hash[i].split('=');
        map[e[0]] = e[1];
      } 
    }
    return map;
  },
  set:function(map) {
    var a = [];
    for (var fid in map) 
      a.push(fid + '=' + encodeURIComponent(map[fid]));
    window.location.hash = '#' + a.join('&');
  }
})