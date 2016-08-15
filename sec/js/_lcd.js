/**
 * LCD FRAMEWORK
 * @version 1.1
 * @author Warren Hornsby
 */
/**
 * Globals
 */
function _$(e) {
  if (String.is(e))
    e = document.getElementById(e);
  return Html.decorate(e);
}
function async(fn) {
  setTimeout(fn, 1);
}
/*
 * pause(0.5, function() {
 *   doStuff();
 * })
 */
function pause(seconds, fn) {
  setTimeout(fn, seconds * 1000);
}
/*
 * var i = 0;
 * loop(function(exit) {  
 *   dostuff(i++);
 *   if (i > 10)  
 *     exit();
 * })
 */
function loop(fn) {
  fn.timer = setInterval(fn.curry(function(onfinish) {
    fn.timer = clearInterval(fn.timer);
    if (onfinish)
      onfinish();
  }), 1);
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
Class = {
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
Object = Class.augment(Object, null, 
  {  // statics
    is:function(e) {
      return e && typeof e === 'object';  // returns true for arrays as well
    },
    isUndefined:function(e) {
      var u;
      return e === u;
    },
    /*
     * Create an object instance from prototype
     * @arg object prototype (optional)
     * @arg object augs (optional; omit to use standard Object._Proto) 
     * @return object instance aug'd with Object._Proto
     */
    create:function(prototype, augs) {
      function F() {}
      F.prototype = prototype;
      var e = new F();
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
      if (Array.is(e)) {
        n = [];
        for (var i = 0; i < e.length; i++) 
          n.push(Object.deepclone(e[i], e._cloned));
      } else if (Object.is(e)) {
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
       * @arg object parent (optional)
       * @return object instance
       */
      create:function(parent) {
        function F() {}
        F.prototype = this;
        var e = new F();
        if (parent)
          e._parent = parent;
        return e;
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
  getProto:function(json, i) {
    return this;
  },
  /*
   * @arg object json {fid:value,..}
   * @return Rec aug'd with json data
   */
  revive:function(json, i) {
    var rec = Object.create(this.getProto(json, i), json || {});
    rec.onload(json);
    return rec;
  },
  /*
   * @arg object[]|map json
   * @arg fn(json, i) decorator (optional, for modifying json element prior to reviving) 
   * @return [Rec,..]|{id:Rec,..} aug'd with json data 
   */
  reviveAll:function(jsons, decorator) {
    if (jsons)
      if (Array.is(jsons))
        return this._fromArray(jsons, decorator);
      else 
        return this._fromMap(jsons, decorator);
  },
  /*
   * @return Rec
   */
  clone:function() {
    return this.revive(Json.decode(Json.encode(this, true)));
  },
  /*
   * @arg fn(json, i) f
   */
  setPrototyper:function(f) {
    this.getProto = f;
    return this;
  },
  /*
   * @arg object proto e.g. from Rec::getStaticJson()
   */
  constants:function(proto) {
    this.aug(proto);
    return this;
  },
  //
  _fromArray:function(array, decorator) {
    for (var i = 0, l = array.length; i < l; i++) {
      if (decorator)
        decorator(array[i], i);
      array[i] = this.revive(array[i], i);
    }
    return Object.augment(array);
  },
  _fromMap:function(map, decorator) {
    for (var i in map) { 
      if (decorator)
        decorator(map[i], i);
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
   * @arg array|map jsons ref'd in create
   * @return Rec prototype for array items
   */
  getItemProto:function(jsons) {},
  /*
   * @events
   */
  onload:function(itemProto, jsons) {},
  onreviveitem:function(json, i) {},
  //
  /*
   * @arg array|map jsons
   * @arg object proto (optional, will use getItemProto if not supplied)
   */
  revive:function(jsons, proto) {
    jsons = jsons || [];
    proto = proto || this.getItemProto(jsons);
    var array = proto.reviveAll(jsons, this.onreviveitem.bind(this));
    array.aug(this).onload(proto, jsons);
    array.itemProto = proto;
    return array;
  },
  /*
   * Shorthand form; requires record is defined prior to array
   * @example Diagnoses = Object.RecArray.of(Diagnosis, {..  // Diagnosis must be defined prior
   */
  of:function(proto, augs) {
    var me = Object.RecArray.extend({
      getItemProto:function(){return proto}
    })
    if (augs)
      me.aug(augs);
    return me;
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
Map = {
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
  /*
   * @arg map/array e
   * @arg string keyFid (optional, to use e[i].keyFid as key; by default, key=i)
   * @arg string valueFid (optional, to use e[i].valueFid as value; by default, value=e[i])
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
        key = (keyFid) ? e[i][keyFid] : i;
        value = (valueFid) ? e[i][valueFid] : e[i];
        m[key] = value;
      }
    } else if (Map.is(e)) {
      for (var i in e) {
        key = (keyFid) ? e[i][keyFid] : i;
        value = (valueFid) ? e[i][valueFid] : e[i];
        m[key] = value;
      }
    }
    return m;
  },
  appendInto:function(map, index, array) {
    if (map[index] == null) 
      map[index] = array;
    else 
      map[index] = map[index].append(array);
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
  keys:function(e, fids) {
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
  }
}
/**
 * String
 */
String = Class.augment(String, 
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
    }
  },{  // statics
    is:function(e) {
    return e && typeof e == 'string';
    },
    isBlank:function(e) {
      return e == null || String.trim(e).length == 0;
    },
    from:function(e) {
      return e + '';
    },
    trim:function(e) {
      return (e != null) ? (e + '').replace('\xa0',' ').replace(/^\s+|\s+$/g, "") : null;
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
    }
  });
/**
 * Array
 */
Array = Class.augment(Array, 
  {  // prototype
    isEmpty:function() {
      return Array.isEmpty(this);
    },
    append:function(e) {
      if (Array.is(e))
        Array.forEach(this,
          function(ei) {
            this.push(ei);
          }); 
      else
        this.push(e);
    },
    pushIfNotNull:function(e) {
      if (e != null)
        this.push(e);
    },
    find:function(item) {
      for (var i = 0, l = this.length; i < l; i++) 
        if (this[i] == item) 
          return i;
      return -1;
    },
    has:function(item) {
      return this.find(item) > -1;
    },
    forEach:function(fn) {
      return Array.forEach(this, fn);
    },
    filter:function(fn) {
      return Array.filter(this, fn);
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
    }
  },{  // statics
    is:function(e) {
      return e != null && e.constructor == Array;
    },
    isEmpty:function(array) {
      return array == null || array.length == 0;
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
        var a = [];
        for (var i = 0; i < e.length; i++) 
          a.push(e[i][fid]);
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
     * @arg [e,..] array
     * @arg fn(e, i) fn for each element
     */
    forEach:function(array, fn) {
      if (array && array.length) 
        for (var i = 0, l = array.length; i < l; i++)
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
          fn = fn || function(e){return e};  
        }
        var filtered = [];
        for (var i = 0; i < array.length; i++) 
          if (fn.call(array, array[i], i) !== null)
            filtered.push(array[i]);
        return filtered;
      }
    },
    /*
     * @arg [rec,..] array
     * @arg string fid 
     * @return array of recs whose fid is set
     */
    filterOn:function(array, fid) {
      return Array.filter(array, function(e) {
        return e[fid];
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
Math = Class.augment(Math, null, 
  {  // statics
    sgn:function(x) {
      return (x > 0) | -(x < 0);      
    },
    isEven:function(i) {
      return (i % 2 == 0);
    },
    larger:function(x1, x2) {
      return (x1 > x2) ? x1 : x2;  
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
    smaller:function(x1, x2) {
      return -Math.larger(-x1, -x2);      
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
Function = Class.augment(Function, 
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
     *   },
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
     *   },    
     */
    prepend:function(fn) {
      var fnOrig = this;
      return function() {
        fn.apply(fn, arguments);
        return fnOrig.apply(fnOrig, arguments);
      }
    },
    /*
     * @arg any 
     */
    async:function() {
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
    }
  });
/**
 * Html
 */
Html = {
  is:function(e) {
    return e && e.nodeName != null;
  },
  create:function(tag, cls) {
    var e = document.createElement(tag);
    if (cls)
      e.className = cls;
    Html.decorate(e);
    return e;
  },
  getTag:function(tag, container, createIfNull) {
    var tags = container.getElementsByTagName(tag);
    if (tags && tags.length > 0)
      return Html.decorate(tags[0]);
    else 
      return (createIfNull) ? container.append(tag) : null;
  },
  decorate:function(e) {
    if (e && ! e._decorated) {
      Class.augment(e, null, Html._proto);
      e._decorated = Html;
      switch (e.tagName) {
        case 'TABLE':
          Class.augment(e, null, Html.Table._proto);
          break;
        case 'TBODY':
        case 'THEAD':
          Class.augment(e, null, Html.Table._protoBody);
          break;
      }
    }
    return e;
  },
  /*
   * @arg <e> e
   * @arg fn(self) protof
   */
  extend:function(e, protof) {
    return e.aug(protof(e));
  },
  _proto:{
    /*
     * @arg object statics
     */
    aug:function(statics) {
      if (statics) {
        Class.augment(this, null, statics);
        if (statics.init) 
          statics.init.call(this);
      }
      return this;
    },
    /*
     * @arg fn(self) protof
     */
    extend:function(proto, protof) {
      if (protof == null)
        return Html.extend(this, proto);
      proto._protof = protof;
      return this.aug(protof(this, (function(parent) {
        return parent._protof(this);
      }).bind(this))); 
    },
    /*
     * @arg string fid
     * @arg mixed value
     */
    set:function(fid, value) {
      if (fid)
        this[fid] = value;
      return this;
    },
    /*
     * @arg string event  
     * @arg object|fn to context|function
     * @arg string toEvent (optional, default same as event if context supplied)
     * e.g. bubble('onselectopt', self)    
     *      bubble('onclick', self.toggle)
     *      bubble('onclick', self, 'onabstract')
     * Recognize that the second form is no good for events intended to be inherited, as the function reference is evaluated only once at init and will always point to the empty @abstract
     */
    bubble:function(event, to, toEvent) {
      if (Function.is(to)) 
        this[event] = to;
      else
        this[event] = function(){to[toEvent || event].apply(this, arguments)};
      return this;
    },
    append:function(e, fid) {
      if (String.is(e)) 
        e = Html.create(e);
      this.appendChild(_$(e));
      if (fid)
        this.set(fid, e);
      return e;
    },
    into:function(e) {
      if (e)
        e.appendChild(this);
      else
        Html.Window.append(this);
      return this;
    },
    before:function(e) {
      if (e && e.parentElement)
        e.parentElement.insertBefore(this, e);
      return this;
    },
    after:function(e) {
      if (e && e.parentElement)
        e.parentElement.insertBefore(this, e.nextSibling);
      return this;
    },
    add:function(e, fid) {
      if (e)
        this.append(e, fid);
      return this;
    },
    remove:function() {
      this.parentElement.removeChild(this);
    },
    clean:function() {
      while (this.hasChildNodes()) 
        this.removeChild(this.lastChild);
      return this;
    },
    setId:function(id) {
      if (id)
        this.id = id;
      return this;
    },
    setText:function(s) {
      this.innerText = String.denull(s);
      return this;
    },
    hide:function() {
      this.style.display = 'none';
      this.showing = null;
      return this;
    },
    show:function() {
      this.style.display = '';
      this.showing = this;
      return this;
    },
    showIf:function(test) {
      if (test) 
        this.show();
      else
        this.hide();
      return this;
    },
    hideIf:function(test) {
      return this.showIf(! test);
    },
    visible:function() {
      this.style.visibility = '';
      return this;
    },
    invisible:function() {
      this.style.visibility = 'hidden';
      return this;
    },
    visibleIf:function(test) {
      return (test) ? this.visible() : this.invisible();
    },
    getStyle:function(style) {
      if (this.currentStyle)
        return this.currentStyle[style];
      else
        return document.defaultView.getComputedStyle(this, null)[style];
    },
    float:function(value) {
      this.style.cssFloat = this.style.styleFloat = value;
      return this;
    },
    getPosDim:function() {
      var pos = this.getPos();
      var dim = this.getDim();
      return Map.combine([pos, dim]);
    },
    getPos:function(x) {
      var l = 0, t = 0;
      var e = this;
      if (e.offsetParent) {
        do {
          l += e.offsetLeft;
          t += e.offsetTop;
          if (e.className == 'pop')
            break;
        } while (e = e.offsetParent);
      }
      return {'left':l,'top':t};
    },
    setLeft:function(i) {
      this.style.left = String.px(i);
      return this;
    },
    setTop:function(i) {
      this.style.top = String.px(i);
      return this;
    },
    getDim:function() {
      var dis = this.style.display;
      if (this.getStyle('display') == 'none')
        this.style.display = 'block';
      var h = this.offsetHeight;
      var w = this.offsetWidth;
      this.style.display = dis;
      return {'height':h,'width':w};
    },
    getHeight:function() {
      return this.getDim().height;
    },
    setHeight:function(i, min) {
      min = min || 0;
      if (i != null && i < min)
        i = min;
      this.style.height = (i == null) ? 'auto' : String.px(i);
      return this;
    },
    setWidth:function(i, min) {
      min = min || 0;
      if (i != null && i < min)
        i = min;
      this.style.width = (i == null) ? 'auto' : String.px(i);
      return this;
    },
    setHeightToMax:function(pad) {
      pad = pad || 0;
      this.setHeight(Html.Window.getViewportDim().height - pad);
      return this;
    },
    setPosition:function(s) {
      this.style.position = s;
      return this;
    },
    getScrollHeight:function() {
      return this.scrollHeight;
    },
    scrollToBottom:function() {
      this.scrollTop = this.scrollHeight;
    },
    center:function() {
      this.clientHeight;
      var left = document.documentElement.clientWidth/2 - this.clientWidth/2;
      if (left < 0) 
        left = 0;
      var top = document.documentElement.clientHeight/2 - this.clientHeight/2;
      if (top < 0) 
        top = 0;
      left += Html.Window.getScrollLeft();
      top += Html.Window.getScrollTop();
      this.setLeft(left).setTop(top);
    },
    centerWithin:function(e) {
      var pp = _$(e).getPos();
      var pd = e.getDim();
      var d = this.getDim();
      return this.setTop(pp.top + pd.height/2 - d.height/2).setLeft(pp.left + pd.width/2 - d.width/2);
    },
    setFocus:function() {
      try {
        this.focus();
      } catch (ex) {
      }
      return this;
    },
    html:function(h) {
      this.innerHTML = h;
      return this;
    },
    nbsp:function() {
      this.innerHTML = '&nbsp;';
      return this;
    },
    working:function(e) {
      if (e) {
        Html.Window.registerWorking(this);
        this._isworking = true;
        if (this._working == null)
          this._working = Html.Window.append(Html.Div.create('working-float'));
        this._working.style.display = 'block';
        this._working.centerWithin(this);
        this._working.style.visibility = 'visible';
        if (Function.is(e)) 
          async(e);
      } else if (this._isworking) {
        this._isworking = false;
        if (this._working)
          this._working.style.display = 'none';
      }
      return this;
    },
    work:function(fn) {
      work(this, fn);
    },
    hasClass:function(cls, startsWith) {
      var extra = (startsWith) ? '*' : '(?:$|\\s)';  
      var hasClassName = new RegExp('(?:^|\\s)' + cls + extra);
      var ec = this.className;
      if (ec && ec.indexOf(cls) != -1 && hasClassName.test(ec)) 
        return true; 
    },
    setClass:function(cls) {
      this.className = cls;
      return this;
    },
    addClass:function(cls) {
      if (! this.hasClass(cls))
        this.className = String.trim(this.className + ' ' + cls);
      return this;
    },
    removeClass:function(cls) {
      this.className = String.trim(this.className.replace(cls, ''));
      return this;
    },
    addClassIf:function(cls, test) {
      if (test)
        this.addClass(cls);
      else
        this.removeClass(cls);
      return this;
    },
    setUnselectable:function() {
      this.unselectable = 'on';
      return this;
    }
  }
};
Html.Window = {
  getEvent:function(e) {
    if (e) 
      e.srcElement = e.target;
    else 
      e = window.event;
    if (e.stopPropagation == null) 
      e.stopPropagation = function(){this.cancelBubble = true};
    return e;
  },
  cancelBubble:function(e) {
    this.getEvent(e).stopPropagation();
  },
  getScrollTop:function() {
    if (typeof pageYOffset != 'undefined')
      return pageYOffset;
    else
      return document.documentElement.scrollTop;
  },
  getScrollLeft:function() {
    if (typeof pageXOffset != 'undefined')
      return pageXOffset;
    else
      return document.documentElement.scrollLeft;
  },
  getDocumentDim:function() {
    var h = document.body.offsetHeight;
    var w = document.body.offsetWidth;
    if (document.documentElement) { 
      if (document.documentElement.clientHeight > h) 
        h = document.documentElement.clientHeight;
      if (document.documentElement.clientWidth > w) 
        w = document.documentElement.clientWidth;
    }
    return {'height':h,'width':w};
  },
  getViewportDim:function() {
    var h, w;
    if (typeof window.innerWidth != 'undefined') { 
      w = window.innerWidth;
      h = window.innerHeight;
    } else if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0) {
      w = document.documentElement.clientWidth;
      h = document.documentElement.clientHeight
    } else {
      w = document.getElementsByTagName('body')[0].clientWidth;
      h = document.getElementsByTagName('body')[0].clientHeight;
    }
    return {'height':h,'width':w};
  },
  attachEvent:function(event, fn, o) {
    o = o || window;
    if (window.addEventListener)  
      o.addEventListener(event, fn, false);
    else 
      o.attachEvent('on' + event, fn);
  },
  detachEvent:function(event, fn, o) {
    o = o || window;
    if (window.removeEventListener)
      o.removeEventListener(event, fn, false);
    else 
      o.detachEvent('on' + event, fn);
  },
  setOnFocus:function(fn) {
    if (typeof(document.onfocusin) == 'object') 
      document.onfocusin = fn;
    else
      window.onfocus = fn;
  },
  setOnBlur:function(fn) {
    if (typeof(document.onfocusout) == 'object') 
      document.onfocusout = fn;
    else
      window.onblur = fn;
  },
  execScript:function(str) {
    if (window.execScript)
      window.execScript(str);
    else
      with (window) 
        window.eval(str);
  },
  append:function(e) {
    document.body.appendChild(_$(e));
    return e;
  },
  registerFixedRow:function(tr) {
    if (this._trs == null)
      this._trs = [];
    this._trs.push(tr);
  },
  flickerFixedRows:function() {
    Array.forEach(this._trs, function(tr) {
      tr.style.display = 'none';
      tr.style.display = '';
    });
  },
  registerWorking:function(e) {
    if (this._wks == null)
      this._wks = [];
    this._wks.push(e);
  },
  clearWorking:function() {
    if (this._wks) {
      this._wks.forEach(function(e) {
        if (e) {}
          e.working(false);
      })
    }
    this._wks = null;
  },
  working:function(e) {
    if (e) {
      Html.Window.registerWorking(this);
      this._isworking = true;
      if (this._working == null)
        this._working = Html.Window.append(Html.Div.create('working-float'));
      this._working.style.display = 'block';
      this._working.center();
      this._working.style.visibility = 'visible';
      if (Function.is(e)) 
        async(e);
    } else if (this._isworking) {
      this._isworking = false;
      if (this._working)
        this._working.style.display = 'none';
    }
    return this;
  }
}
EventKeypressCr = {
  onkeypresscr:function() {},
  //
  onkeypress:function() {
    if (event.keyCode == 13)
      this.onkeypresscr();
  }
}
Html.Animator = {
  /*
   * Set background yellow
   * @arg <e> e
   */
  highlight:function(e) {
    e.rgb0 = hexToNumbers(_$(e).getStyle('backgroundColor'));
    e.rgb1 = [255,255,128];
    e.style.backgroundColor = rgbString(e.rgb1);
  },
  /*
   * Set background yellow, fade to transparent
   * @arg <e> e
   * @arg fn() onfinish (optional)
   */
  fade:function(e, onfinish) {
    if (e == null || e.rgb) 
      return;
    if (e.rgb0 == null)
      Html.Animator.highlight(e);
    var rgb0 = e.rgb0;
    var rgb1 = e.rgb1;
    e.rgb = rgb1;
    e.rgbOff = [rgb0[0] - rgb1[0], rgb0[1] - rgb1[1], rgb0[2] - rgb1[2]]; 
    e.style.backgroundColor = Html.Animator.rgbstring(rgb1);
    var fdix = 0;
    var fdmax = 40;
    pause(0.5, function() {
      loop(function(exit) {
        fdix++;
        var m = fdix / fdmax;
        var rgb = [e.rgb[0] + e.rgbOff[0] * m, e.rgb[1] + e.rgbOff[1] * m, e.rgb[2] + e.rgbOff[2] * m];
        e.style.backgroundColor = Html.Animator.rgbstring(rgb);
        if (fdix == fdmax) {
          e.style.backgroundColor = '';
          e.rgb = null;
          e.rgb0 = null;
          exit(onfinish);
        }
      })
    })
  },
  /*
   * Scroll to element within a scrollable div
   * @arg <e> div
   * @arg <e> to
   * @arg int padding (optional; e.g. to accommodate height of a fixed header row)  
   */
  scrollTo:function(div, to, padding) {
    scrollToElement(div, to, padding);
  },
  /*
   * Pulse (swell) element
   * @arg int to (default 4 times size)
   * @arg int inc (default 0.2)
   * @arg fn() onfinish (optional)
   */
  pulse:function(e, to, inc, onfinish) {
    if (e == null || e.swell) 
      return;
    var s = {
      to:to || 4,   
      inc:inc || 0.2,
      dir:1,
      zoom:1,
      sp:e.style.position,
      pos:e.getPos()};
    pause(0.1, function() {
      loop(function(exit) {
        var limit = (s.dir == 1) ? s.to : 1;
        var zoom = s.zoom + s.inc * s.dir;
        if ((s.dir * (zoom - limit)) > 0) {
          zoom = limit;
          if (s.dir == -1) 
            s.done = true;
          else 
            s.dir = -1;
        }
        e.style.zoom = zoom;
        e.style.left = s.pos.left - (zoom - 1) * (s.to * 2);
        e.style.top = s.pos.top - (zoom - 1) * (s.to * 2);
        if (s.done) 
          exit(onfinish);
        else 
          s.zoom = zoom;
      })
    })
  },
  //
  rgbstring:function(rgb) {
    return 'rgb(' + rgb.join(',') + ')';
  }
}
/**
 * STANDARD TAGS 
 */
Html.Anchor = {
  is:function(e) {
    return Html.is(e) && e.tagName == 'A';
  },
  create:function(cls, text, onclick, augs) {
    var e = Html.create('a', cls);
    e.href = 'javascript:';
    e.setText(text);
    if (onclick) 
      e.onclick = onclick;
    return e.aug(Html.Anchor._proto).aug(augs);
  },
  _proto:{
    tooltip:function(text) {
      this.title = String.denull(text);
      return this;
    },
    noFocus:function() {
      this.hideFocus = 'hideFocus';
      return this;
    },
    working:function(value) {
      if (value) {
        Html.Window.registerWorking(this);
        this._isworking = true;
        this._text = this.innerText;
        this.nbsp();
        this.addClass('working');
        if (Function.is(value)) 
          async(value);
      } else if (this._isworking) {
        this._isworking = false;
        this.innerText = String.denull(this._text);
        this.removeClass('working');
        this._working = null;
      }
      return this;
    },
    setText:function(text) {
      this.innerText = text;
      return this;
    }
  }
}
Html.Br = {
  create:function() {
    return Html.create('br');
  }
}
Html.Div = {
  is:function(e) {
    return Html.is(e) && e.tagName == 'DIV';
  },
  create:function(cls, augs) {
    var self = Html.create('div', cls);
    return self.aug({
      spin:function(e) {
        if (e) {
          self.clean();
          self.addClass('working-circle');
          if (Function.is(e)) 
            async(e);
        } else {
          self.removeClass('working-circle');
        }
      }
    }).aug(augs);
  }
}
Html.HoverableDiv = {
  create:function(cls) {
    return Html.Div.create(cls).aug({
      init:function() {
        this.addClass('hoverable');
      },
      onmouseover:function() {
        this.addClass('hovering');
      },
      onmouseout:function() {
        this.removeClass('hovering');
      }
    })
  }
}
Html.Form = {
  create:function(url, fields) {
    var e = Html.create('form').into();
    return e.aug({
      init:function() {
        this.load(fields);
      },
      method:'POST',
      action:url,
      load:function(fields) {
        for (var name in fields)
          this.append(Html.InputHidden.create(fields[name], name));
        return this;
      }
    })
  }
}
Html.IFrame = {
  create:function(cls, src, height, width) {
    var e = Html.create('iframe', cls);
    e.src = String.denull(src);
    e.setHeight(height);
    e.setWidth(width);
    return e;
  }
}
Html.Image = {
  create:function(cls, src, height, width, alt) {
    var e = Html.create('img', cls);
    e.src = String.denull(src);
    e.setHeight(height);
    e.setWidth(width);
    e.alt = String.denull(alt);
    return e;
  }
}
Html.H1 = {
  create:function(text) {
    var e = Html.create('h1');
    e.setText(text);
    return e;
  }
}
Html.H2 = {
  create:function(text) {
    var e = Html.create('h2');
    e.setText(text);
    return e;
  }
}
Html.H3 = {
  create:function(text) {
    var e = Html.create('h3');
    e.setText(text);
    return e;
  }
}
Html.Input = {
  create:function(type, cls, value, name, augs) { 
    var e = Html.create('input', cls);
    e.type = type;
    e.value = String.denull(value);
    e.name = String.denull(name);
    return e.aug(Html.Input._proto).aug(augs);
  },
  _proto:{
    clean:function() {
      this.value = '';
    },
    getValue:function() {
      return String.trim(this.value);
    },
    setValue:function(value) {
      this.value = String.denull(value);
      return this;
    }
  },
  _dirtyProto:{
    ondirty:function() {},
    //
    clean:function() {
      this._dirty = false;
      this.value = '';
    },
    onchange:function() {
      this._dirty = true;
      this.ondirty();
    },
    isDirty:function() {
      return this._dirty;
    }
  }
}
Html.InputText = {
  create:function(cls, value, name, type) {
    var e = Html.Input.create(type || 'text', cls, value, name);
    return e.aug(EventKeypressCr).aug({
      onkeypresscr:function() {},
      onfocus:function() {
        e.select();
      },
      onblur:function() {  // fixes IE bug
        var temp = this.value;
        this.value = '';
        this.value = temp;
      },
      setFocus:function() {
        async(function() {
          try {e.focus()} catch (ex) {}
        })
        return e;
      },
      noSelectFocus:function() {
        e.onfocus = null;
        return e;
      },
      setSize:function(i) {
        if (i)
          e.size = i;
        return e;
      },
      setMaxLength:function(i) {
        if (i) 
          e.maxLength = i;
        return e;
      }
    })
  }
}
Html.InputPassword = {
  create:function(cls, value, name) {
    return Html.InputText.create(cls, value, name, 'password');
  }
}
Html.InputCheck = {
  create:function(cls, value, name, augs) {
    var e = Html.Input.create('checkbox', cls, value, name);
    return e.aug({
      ondblclick:function() {
        e.onclick();
      },
      setCheck:function(value) {
        e.checked = (value == true);
        return e;
      }
    }).aug(augs);
  }
}
Html.InputRadio = {
  create:function(cls, value, name, augs) {
    var e = Html.Input.create('radio', cls, value, name);
    return e.aug({
      ondblclick:function() {
        e.onclick();
      },
      setCheck:function(value) {
        e.checked = (value == true);
        return e;
      }
    }).aug(augs);
  }
}
Html.InputButton = {
  create:function(cls, value, augs) {
    return Html.Input.create('button', cls, value, augs);
  }
}
Html.InputHidden = {
  create:function(value, name, augs) {
    return Html.Input.create('hidden', null, value, name, augs);
  }
}
Html.Select = {
  /*
   * @arg {value:text,..} map (optional, to load with options on create)
   * @arg string blank (optional, text for creating first blank value)
   * @arg string cls (optional)
   */
  create:function(map, blank, cls) {
    var self = Html.create('select', cls);
    return self.aug({
      /*
       * @event when setValue invoked; use onchange for user interaction
       */
      onset:function() {},
      //
      init:function() {
        if (map)
          self.load(map, blank);
      },
      load:function(map, blank) {
        self.clean();
        if (blank != null) 
          self.addOption('', blank);
        var text;
        for (var value in map) 
          self.addOption(value, map[value]);
      },
      /*
       * @arg string value
       * @arg string text (optional, default to value)
       */
      addOption:function(value, text) {
        var o = Html.create('option');
        self.options.add(o);
        o.value = String.denull(value);
        o.text = String.denull(text || o.value);
      },
      setValue:function(value) {
        var cur = self.value;
        for (var i = 0, l = self.options.length; i < l; i++) 
          if (self.options[i].value == value)
            break;
        var opt = self.options[(i < l) ? i : 0];
        if (opt.value != self.value) { 
          opt.selected = true;
          self.onset();
        }
        return self;
      },
      getValue:function() {
        return self.value;
      },
      getText:function() {
        return self.options[self.selectedIndex].text;
      }
    })
  }
}
Html.TextArea = {
  create:function(cls, value, name, augs) {
    var e = Html.create('textarea', cls);
    e.value = String.denull(value);
    e.name = String.denull(name);
    return e.aug(Html.Input._proto).aug(EventKeypressCr).aug({
      onkeypresscr:function() {},
      //
      setRows:function(i) {
        e.rows = i;
        return e;
      }
    }).aug(augs);
  }
}
Html.AutoSizeTextArea = {
  create:function(cls, value, name, augs) {
    var e = Html.TextArea.create(cls, value, name);
    return e.aug({
      onkeydown:autosize,
      onkeyup:autosize,
      onkeypress:autosize,
      autosize:function() {
        self.setHeight(self.getScrollHeight() + 15);
      }
    }).aug(augs);
  }
}
Html.DirtyTextArea = {
  create:function(cls, value, name) {
    return Html.TextArea.create(cls, value, name).aug(Html.Input._dirtyProto);
  }
}
Html.Label = {
  create:function(cls, text, augs) {
    var e = Html.create('label', cls);
    e.setText(text);
    return e.aug(augs);
  }
}
Html.Span = {
  create:function(cls, text, augs) {
    var e = Html.create('span', cls);
    e.setText(text);
    return e.aug({
      html:function(h) {
        e.innerHTML = h;
        return e;
      }}).aug(augs);
  }
}
Html.Table = {
  is:function(e) {
    return Html.is(e) && e.tagName == 'TABLE';
  },
  create:function(container, cls) {
    return Html.create('table', cls).into(container);
  },
  _proto:{
    tbody:function() {
      if (this._tbody == null) 
        this._tbody = Html.getTag('tbody', this, true);
      return this._tbody; 
    },
    thead:function() {
      if (this._thead == null)
        this._thead = Html.getTag('thead', this, true);
      return this._thead; 
    }
  },
  _protoBody:{
    tr:function(cls) {
      this._tr = Html.create('tr', cls).into(this);
      return Html.Table._protoBody._trAppender(this._tr);
    },
    trFixed:function() {
      var appender = this.tr('fixed head');
      Html.Window.registerFixedRow(this._tr);
      return appender;
    },
    trToggle:function(keep) {
      this._trToggle = (keep) ? this._trToggle : ! this._trToggle;
      return this.tr(this._trToggle ? 'row1' : 'row2');
    },
    _trAppender:function(tr) {
      return {
        td:function(e, cls) {
          this._cell = Html.create('td', cls).into(tr);
          this._appendOrText(e);
          return this;
        },
        th:function(e, cls) {
          this._cell = Html.create('th', cls).into(tr);
          this._appendOrText(e);
          return this;
        },
        html:function(s) {
          this._cell.html(s);
          return this;
        },
        w:function(i) {
          this._cell.setWidth(i);
          return this;
        },
        rowspan:function(i) {
          this._cell.rowSpan = i;
          return this;
        },
        colspan:function(i) {
          this._cell.colSpan = i;
          return this;
        },
        _tr:function() {
          return tr;
        },
        _appendOrText:function(e) {
          if (e)
            if (Html.is(e))
              this._cell.append(e);
            else 
              this._cell.setText(e);
        }
      }
    }
  }
}
Html.Ul = {
  create:function(cls) {
    var self = Html.create('ul', cls);
    return self.aug({
      li:function(cls) {
        return Html.create('li', cls).into(self);
      }
    });
  }
}
Html.Page = {
  extend:function() {
    var self = Html.decorate(window);
    return self.aug({
      /**
       * @events
       */
      onbodyload:function() {},
      //
      init:function() {
        self.body = Html.decorate(document.body).bubble('onload', self, 'onbodyload');
      },
      /*
       * @arg string page 'page-name.php'
       * @arg map|string args e.g. {name:value,..} or single string value to supply to 'id'
       */
      go:function(page, args) {
        setTimeout(function(){window.location.href = String.url(page, args)},1);
      },
      getScrollTop:function() {
        if (typeof pageYOffset != 'undefined')
          return pageYOffset;
        else
          return document.documentElement.scrollTop;
      },
      getScrollLeft:function() {
        if (typeof pageXOffset != 'undefined')
          return pageXOffset;
        else
          return document.documentElement.scrollLeft;
      },
      vscroll:function(i) {
        self.scroll(0, i || 0);
      }
    }).extend.apply(self, arguments);
  }
}
/**
 * CLICKTATE TAGS 
 */
Html.InputDate = {
  create:function(text) {
    return Html.Span.create().extend(function(self) {
      var di;
      return {
        onset:function(value) {},
        //
        init:function() {
          di = new DateInput(text, self, function(value) {
            self.onset(value)
          })
        },
        setValue:function(text) {
          di.setText(text);
        },
        getValue:function() {
          return di.getText();
        },
        setFocus:function() {
          di.focus();
        }
      }
    })
  }
}
/**
 * Anchor AnchorAction
 */
Html.AnchorAction = {
  create:function(cls, text, augs) {
    return Html.Anchor.create('action ' + cls, text).aug(augs); 
  },
  asEdit:function(text, augs) {
    return this.create('edit2', text, augs);
  },
  asSelect:function(text, augs) {
    return this.create('choice', text, augs);
  },
  asSelect2:function(text, augs) {
    return this.create('choice2', text, augs);
  },
  asSelectGreen:function(text, augs) {
    return this.create('editgreen', text, augs);
  },
  asOpen:function(text, augs) {
    return this.create('open', text, augs);
  },
  asNew:function(text, augs) {
    return this.create('new-folder', text, augs);
  },
  asPrint:function(text, augs) {
    return this.create('print', text, augs);
  },
  asView:function(text, augs) {
    return this.create('view2', text, augs);
  },
  asWarning:function(text, augs) {
    return this.create('warning', text, augs);
  },
  asUpdate:function(text, augs) {
    return this.create('update', text, augs);
  },
  asDelete:function(text, augs) {
    return this.create('dele', text, augs);
  },
  asGrid:function(text, augs) {
    return this.create('grid', text, augs);
  },
  asCustom:function(text, augs) {
    return this.create('configure', text, augs);
  },
  asNote:function(text, augs) {
    return this.create('page', text, augs);
  },
  asAppt:function(text, augs) {
    return this.create('appt', text, augs);
  },
  asOrder:function(text, augs) {
    return this.create('track', text, augs);
  },
  asMsg:function(text, augs) {
    return this.create('msg', text, augs);
  },
  asAttach:function(text, augs) {
    return this.create('attachment3', text, augs);
  },
  asGraph:function(text, augs) {
    return this.create('graph', text, augs);
  },
  asXml:function(text, augs) {
    return this.create('xmlsm', text, augs);
  },
  asKey:function(text, augs) {
    return this.create('key2', text, augs);
  }
}
/*
 * AnchorAction AnchorRec 
 */
Html.AnchorRec = {
  /*
   * @arg string cls
   * @arg string text
   * @arg Rec rec
   * @arg fn(Rec) onclick
   */
  create:function(cls, text, rec, onclick) {
    return this.from(Html.AnchorAction.create(cls, text), rec, onclick);
  },
  /*
   * @arg AnchorACtion a
   * @arg Rec rec
   * @arg fn(Rec) onclick
   */
  from:function(a, rec, onclick) {
    if (onclick) 
      a.bubble('onclick', onclick.curry(rec));
    return a;
  },
  //
  asSelect:function(text, rec, onclick) {
    return this.create('choice', text, rec, onclick);
  },
  asEdit:function(text, rec, onclick) {
    return this.create('edit2', text, rec, onclick);
  }
}
AnchorClient = {
  create:function(rec, onclick) {
    var cls = (rec.sex == 'M') ? 'umale' : 'ufemale';
    return Html.AnchorRec.create(cls, rec.name, rec, onclick);
  }
}
AnchorClient_Facesheet = {
  create:function(rec) {
    //return AnchorClient.create(rec, function(rec) {Page.Nav.goFacesheet(rec.clientId)});
    return AnchorClient.create(rec).set('href', Page.url(Page.PAGE_FACESHEET, rec.clientId));
  }
}
AnchorDocStub = {
  create:function(rec, onclick) {
    var m = 'as' + [null, 'Note','Msg','Appt','Order','Attach','Graph','Xml'][rec.type];
    return Html.AnchorRec.from(Html.AnchorAction[m](rec.name), rec, onclick);
  }
}
AnchorTrackItem = {
  create:function(rec, onclick) {
    var self = Html.AnchorRec.from(Html.AnchorAction.asOrder(rec.trackDesc), rec, onclick);
    if (rec.priority == C_TrackItem.PRIORITY_STAT)
      self.addClass('red');
    return self;
  }
}
AnchorProc = {
  create:function(rec, onclick) {
    return Html.AnchorRec.from(Html.AnchorAction.asGraph(rec.Ipc.name), rec, onclick);
  }
}
/**
 * Span LabelCheck
 *   Input check
 *   Label label
 */
Html.LabelCheck = {
  /*
   * @arg string text
   * @arg mixed value (optional, default '1')
   */
  create:function(text, value) {
    var self = Html.Span.create('LabelCheck').setUnselectable();
    return self.aug({
      /*
       * @events
       */
      onclick_check:function(lcheck) {},
      //
      init:function() {
        self.check = Html.LabelCheck.Check.create(self, value || '1').aug({
          onpropertychange:function() {
            if (self.label)
              self.label.setClass((self.check.checked) ? 'lcheck-on' : 'lcheck');
          },
          onclick:function(e) {
            self.lc_onclick();
            Html.Window.cancelBubble(e);
          }
        }); 
        self.label = Html.LabelCheck.Label.create(self, text).aug({
          onclick:function(e) {
            self.setChecked(! self.isChecked());
            if (self.check)
              self.lc_onclick();
            Html.Window.cancelBubble(e);
          }
        });
      },
      isChecked:function() {
        return self.check.checked;
      },
      setChecked:function(b) {
        self.check.checked = b;
      },
      getValue:function() {
        return value;
      },
      getText:function() {
        return text;
      },
      lc_onclick:function() {
        self.onclick_check(self);
        if (self.onchange)
          self.onchange();
      }
    });
  },
  Check:{
    create:function(container, value) {
      var self = Html.InputCheck.create(null, value).into(container);
      return self;
    }
  },
  Label:{
    create:function(container, text) {
      var self = Html.Label.create('lcheck', text).setUnselectable().into(container);
      return self.aug({
        ondblclick:function() {
          self.onclick();
        }
      });
    }
  }
}
Html.DivCheck = {
  create:function(text, value) {
    var My = this;
    return Html.Div.create('DivCheck').extend(function(self) {
      return {
        oncheck:function(checked) {},
        //
        init:function() {
          self.lcheck = My.LabelCheck.create(text, value).into(self).bubble('onclick_check', self.lcheck_onclick);
          self.content = Html.Tile.create(self, 'DivCheckContent').hide();
        },
        add:function(e) {
          return self.lcheck.add(e);
        },
        setText:function(s) {
          self.lcheck.setText(s);
          return self;
        },
        setContent:function(html) {
          self.content.show().html(html);
        },
        isChecked:function() {
          return self.lcheck.isChecked();
        },
        setChecked:function(b) {
          self.lcheck.setChecked(b);
          self.setCheckedClass();
        },
        getValue:function() {
          return self.lcheck.getValue();
        },
        getText:function() {
          return self.lcheck.getText();
        },
        //
        lcheck_onclick:function() {
          self.setCheckedClass();
          self.oncheck(self.lcheck.isChecked());
        },
        onclick:function() {
          self.setChecked(! self.isChecked());
          self.lcheck_onclick();
        },
        setCheckedClass:function() {
          self.addClassIf('DivChecked', self.lcheck.isChecked());
        }
      }
    })
  },
  LabelCheck:{
    create:function(text, value) {
      return Html.LabelCheck.create(text, value).extend(function(self) {
        return {
          init:function() {
            var t = Html.Table.create(self).tbody();
            t.tr().th(self.check).w(17).td(self.label).w('100%');
            t.tr().th().w(17).td(self.content = Html.Div.create('LabelCheckContent')).w('100%');
          },
          add:function(e) {
            return self.content.add(e);
          },
          setText:function(s) {
            self.content.setText(s);
            return self;
          }
        }
      })
    }
  }
}
/**
 * Span LabelChecks
 *   LabelCheck[] lchecks
 */
Html.LabelChecks = {
  /*
   * @arg {value:text,..} map
   * @arg int cols (optional, to spread into columns; default 1)
   */
  create:function(map, cols) {
    var self = Html.Span.create().setUnselectable();
    return self.aug({
      /*
       * @events
       */
      onclick_check:function(lcheck) {},
      //
      init:function() {
        self.lchecks = [];
        for (var value in map) 
          self.lchecks.push(Html.LabelCheck.create(map[value], value).bubble('onclick_check', self));
        Html.TableCol.create(self, cols, self.lchecks); 
      },
      /*
       * @return [LabelCheck,..]
       */
      getChecked:function() {
        var checked = [];
        Array.forEach(self.lchecks, function(lcheck) {
          if (lcheck.isChecked())
            checked.push(lcheck);
        });
        return checked;
      },
      /*
       * @arg [value,..] values
       */
      setChecked:function(values) {
        self._origValues = values;
        values = Array.from(values);
        Array.forEach(self.lchecks, function(lcheck) {
          lcheck.setChecked(values.has(lcheck.getValue()));
        });
      },
      isDirty:function() {
        if (self._origValues) {
          for (var i = 0, j = self.lchecks.length; i < j; i++) {
            var lcheck = self.lchecks[i];
            if (self._origValues.has(lcheck.getValue())) { 
              if (! lcheck.isChecked())
                return true;
            } else {
              if (lcheck.isChecked()) 
                return true;
            }
          }
          return false;
        }
      },
      /*
       * @return [value,..]
       */
      getCheckedValues:function() {
        var checked = [];
        Array.forEach(self.getChecked(), function(lcheck){checked.push(lcheck.getValue())});
        return checked;
      },
      /*
       * @return [text,..]
       */
      getCheckedTexts:function() {
        var checked = [];
        Array.forEach(self.getChecked(), function(lcheck){checked.push(lcheck.getText())});
        return checked;
      }
    });
  }
}
/**
 * Span LabelRadios
 *   {value:LabelRadio,..} lradios
 */
Html.LabelRadios = {
  /*
   * @arg {value:text,..} map
   * @arg int cols (optional, to spread into columns; default map length)
   */
  create:function(map, cols) {
    var self = Html.Span.create().setUnselectable();
    var name = 'LR' + String.rnd();
    return self.aug({
      onselect:function(value) {},
      //
      init:function() {
        self.lradios = {};
        self.value = null;
        var value, len = 0;
        for (value in map) {
          self.lradios[value] = Html.LabelRadios.LabelRadio.create(name, map[value], value).bubble('onclick_radio', self.radio_onclick);
          if (self.value == null)
            self.value = value;
          len++;
        }
        Html.TableCol.create(self, cols || len, Array.from(self.lradios));
        self.setValue(self.value);
      },
      setValue:function(value) {
        self.value = value;
        self.lradios[value].setChecked(true);
      },
      getValue:function(value) {
        return self.value;
      },
      //
      radio_onclick:function(lradio) {
        self.value = lradio.getValue();
        self.onselect(self.value);
      }
    });
  },
  LabelRadio:{
    create:function(name, text, value) {
      var _proto = this;
      var self = Html.Span.create().setUnselectable();
      return self.aug({
        onclick_radio:function(lradio) {},
        //
        init:function() {
          self.radio = _proto.Radio.create(self, value, name).aug({
            onpropertychange:function() {
              if (self.label)
                self.label.setClass((self.radio.checked) ? 'lcheck-on' : 'lcheck');
            },
            onclick:function() {
              self.onclick_radio(self);        
            }
          }); 
          self.label = _proto.Label.create(self, text).aug({
            onclick:function() {
              self.setChecked(true);
              if (self.radio) 
                self.onclick_radio(self);
            }
          });
        },
        isChecked:function() {
          return self.radio.checked;
        },
        setChecked:function(b) {
          self.radio.checked = b;
        },
        getValue:function() {
          return value;
        },
        getText:function() {
          return text;
        }
      });
    },
    Radio:{
      create:function(container, value, name) {
        var self = Html.InputRadio.create(null, value, name).into(container);
        return self;
      }
    },
    Label:{
      create:function(container, text) {
        var self = Html.Label.create('lcheck', text).setUnselectable().into(container);
        return self.aug({
          ondblclick:function() {
            self.onclick();
          }
        });
      }
    }
  }
}
/**
 * EntryForm Wrapper
 */
Html.EntryForm = {
  create:function(container, firstLabelCls, augs) {
    var ul = Html.Ul.create().into(container);
    ul.ef = new EntryForm(ul, firstLabelCls);
    return Object.augment(ul.ef, augs); 
  }
}
/**
 * Ul Entry
 **/
Html.UlEntry = {
  /*
   * self.form = Html.UlEntry.create(self, function(ef) {
   *   ef.line().lbl('Label1').textbox('field1');
   *   ef.line('mt5').lbl('Label2').ro('field2');
   * }) 
   */
  create:function(container, build) {
    return Html.Ul.create().into(container).extend(function(self) {
      return {
        onload:function(rec) {},  // may modify rec or return new one 
        onchange:function() {},
        //
        init:function() {
          self.ef = Object.augment(new EntryForm(self));
          self.ef.setOnChangeAny(self.ef_onchange);
          if (build)
            build(self.ef);
        },
        load:function(rec) {
          self.ef.clearRecordChanged();
          var changed = self.onload(rec);
          if (changed)
            rec = changed;
          self.rec = rec;
          self.ef.setRecord(rec);
          self.draw();
          return self;
        },
        draw:function() {},
        isDirty:function() {
          return self.ef.isRecordChanged();
        },
        line:function(cls) {
          return self.ef.line(cls);
        },
        applyTo:function() {
          return self.ef.applyTo(self.rec);
        },
        getRecord:function() {
          return self.ef.getRecord();
        },
        getValue:function(fid) {
          return self.ef.getValue(fid);
        },
        focus:function(fid) {
          self.ef.focus(fid);
        },
        ef_onchange:function() {
          if (self.ef.isRecordChanged())
            self.onchange();
        }
      }
    })
  }
}
/**
 * Ul Filter
 */
Html.UlFilter = {
  create:function() {
    var My = this;
    return Html.Ul.create('filter').extend(My, function(self) {
      return {
        onselect:function(a) {},
        //
        /*
         * @arg {'key':'text',..} items
         * @arg string selectedkey (optional)
         */
        load:function(items, selectedkey) {
          self.reset();
          for (var key in items) { 
            if (selectedkey == null)
              selectedkey = key;
            self.add(key, items[key]);
          }
          self.select(key);
        },
        reset:function() {
          self.clean();
          self.selected = null;
          self.items = {};
        },
        add:function(key, text) {
          var a = Html.Anchor.create();
          a.setText(text).set('key', key).bubble('onclick', self.item_onclick.curry(a));
          self.li().add(self.items[key] = a);
          return a;
        },
        select:function(key) {
          if (self.selected) { 
            if (self.selected.key == key)
              return;
            self.selected.removeClass('fsel');
          }
          self.selected = self.items[key].addClass('fsel');
          self.onselect(self.selected);
          return self.selected;
        },
        //
        item_onclick:function(a) {
          self.select(a.key);
        }
      }
    })
  }
}
/**
 * Tile NavBar
 *   LinkBox prevbox
 *   Tile onbox
 *   LinkBox nextbox
 */
Html.NavBar = {
  create:function(container) {
    var My = this;
    return Html.Tile.create(container, 'NavBar').extend(function(self) {
      return {
        onselect:function(rec) {},
        ondraw_load:function(rec, header, content) {
          // @abstract
        },  
        //
        init:function() {
          self.prevbox = My.LinkBox.asPrev().bubble('onnav', self.draw);
          self.onbox = My.OnBox.create();
          self.nextbox = My.LinkBox.asNext().bubble('onnav', self.draw);
          Html.Table.create(self, 'w100').tbody().tr().td(self.prevbox).w('15%').td(self.onbox).w('70%').td(self.nextbox).w('15%');
        },
        /*
         * @arg Rec[] recs
         * @arg Rec rec
         * @arg proto anchor e.g. AnchorDocStub 
         * @arg bool descending (optional)
         */
        load:function(recs, rec, anchor, descending) {
          self.navs = Array.navify(recs, descending);
          self.anchor = anchor;
          self.draw(rec);
        },
        //
        draw:function(rec) {
          self.prevbox.load(rec._prev, self.anchor);
          self.nextbox.load(rec._next, self.anchor);
          self.draw_onbox(rec);
          self.onselect(rec);
        },
        draw_onbox:function(rec) {
          self.onbox.header.clean();
          self.onbox.content.clean();
          self.ondraw_load(rec, self.onbox.header, self.onbox.content);
        }
      }
    })
  },
  OnBox:{
    create:function() {
      return Html.Div.create('onbox').extend(function(self) {
        return {
          init:function() {
            self.header = Html.H2.create().into(self);
            self.content = Html.Tile.create(self, 'content');
          }
        }
      })
    }
  },
  LinkBox:{
    create:function(cls) {
      return Html.Div.create(cls).extend(function(self) {
        return {
          onnav:function(rec) {},
          //
          load:function(rec, anchor) {
            self.clean();
            self.rec = rec;
            self.anchor = (rec) ? anchor.create(self.rec, self.onnav).addClass('linkbox').noFocus().into(self) : null;
            self.addClassIf('empty', rec == null);
          },
          //
          onclick:function() {
            if (self.rec)
              self.onnav(self.rec);
          },
          ondblclick:function() {
            self.onclick();
          },
          onmouseover:function() {
            if (self.anchor) 
              self.addClass('hover');
          },
          onmouseout:function() {
            self.removeClass('hover');
          }
        }
      })
    },
    asPrev:function() {
      return this.create('linkbox prevbox');
    },
    asNext:function() {
      return this.create('linkbox nextbox');
    }
  }
}
/**
 * Div TemplateUi
 *   TemplateUi tui
 */
Html.TemplateUi = {
  /*
   * @arg <e> container
   * @arg Facesheet fs
   */
  create:function(container, fs) {
    var self = Html.Div.create().into(container);
    return self.aug({
      /*
       * @events
       */
      onload:function() {},
      onchange:function(q) {},
      /*
       * @abstract (must override if using argless load)
       * @arg fn(pid) callback_pid
       */
      getPid:function(callback_pid) {
        callback_pid(self.pid);
      },
      //
      init:function() {
        self.tui = new TemplateUi(self, fs, null, null, TemplateUi.FORMAT_ENTRY_FORM_WIDE, null, function(q){self.onchange(q)});
      },
      /*
       * @arg int pid (optional; if omitted, must implement getPid)
       * @arg fn() onload (optional)
       */
      load:function(pid, onload) {
        self.onload = onload;
        self.pid = pid;
        self.working(function() {
          self.getPid(function(pid) {
            self.pid = pid;
            self.tui.reset();
            self.tui.getParInfo(self.pid, function() {
              self.working(false);
              self.onload();
            });
          });
        });
      },
      isLoaded:function() {
        return self.pid;
      },
      /*
       * Reset form with same paragraph 
       */
      reset:function() {
        self.clean();
        self.tui.reset();
        if (self.pid)
          self.working(function() {
            self.tui.getParInfo(self.pid, function() {
              self.working(false);
            });
          });
      }
    });
  }
}
/**
 * Div Tile
 */
Html.Tile = {
  create:function(container, cls) {
    return Html.Div.create(cls).into(container);
  }
}
/**
 * Table2Col SplitTile
 *   TH left
 *   TD right
 */
Html.SplitTile = {
  create:function(container) {
    return Html.Table2Col.create(container).setClass('split').extend(function(self) {
      return {
        init:function() {
          self.left.addClass('split');
          self.right.addClass('split');
        },
        showBoth:function() {
          self.left.show();
          self.right.show();
        },
        showLeft:function() {
          self.left.show();
          self.right.hide();
        },
        showRight:function() {
          self.right.show();
          self.left.hide();
        }
      }
    })
  }
}
/**
 * Div ScrollDiv
 */
Html.ScrollDiv = {
  create:function(container, cls) {
    var self = Html.Div.create(cls).into(container);
    return self.aug({
      clean:function() {
        Html._proto.clean.call(self);
        self.scrollTop = 0;
      }
    })
  }
}
/**
 * Table Table2Col
 *   Th left
 *   Tr right
 */
Html.Table2Col = {
  /*
   * @arg col1, col2 (optional contents of table)
   */
  create:function(container, col1, col2) {
    var self = Html.Table.create(container, 't2c');
    return self.aug({
      init:function() {
        self.tr = self.tbody().tr(); 
        self.left = self.tr.th(col1)._cell;
        self.right = self.tr.td(col2)._cell;
      }
    });
  }
}
/**
 * Table Table2Col
 *   Th left
 *   Tr right
 */
Html.Table2ColHead = {
  /*
   * @arg col1, col2 (optional contents of table)
   */
  create:function(container, col1, col2) {
    var self = Html.Table.create(container, 'h');
    return self.aug({
      init:function() {
        self.tr = self.tbody().tr(); 
        self.left = self.tr.th(col1)._cell;
        self.right = self.tr.td(col2)._cell;
      }
    });
  }
}
/**
 * Table TableCol
 */
Html.TableCol = {
  /*
   * @arg <e> container
   * @arg int|obj[] cols number of columns|items to populate table at create (optional)
   * @ex create(self, [tile1, tile2])
   * @ex create(self, 3, checks)
   */
  create:function(container, cols, items) {
    if (cols == null) {
      cols = 1;
    } else if (Array.is(cols)) { 
      items = cols;
      cols = items.length;
    }
    var table = Html.Table.create(container);
    var tbody = table.tbody();
    tbody.aug({
      init:function() {
        tbody.reset(cols);
      },
      reset:function(cols) {
        tbody.clean();
        tbody.ct = 0;
        tbody.cols = cols;
      },
      add:function(e) {
        if (tbody.ct % tbody.cols == 0)
          tbody.trapp = tbody.tr();
        tbody.ct++;
        tbody.trapp.td(e);
      }
    });
    if (items)
      Array.forEach(items, function(item) {
        tbody.add(item);
      });
    return tbody;
  }
}
/**
 * TableCol Table1Row
 */
Html.Table1Row = {
  /*
   * @arg <e> container
   * @arg obj[] items
   */
  create:function(container, items) {
    cols = items.length;
    return Html.TableCol.create(container, cols, items);
  }
}
/**
 * Table ScrollTable
 *   Div wrapper
 */
Html.ScrollTable = {
  create:function(container, tableCls, wrapperCls) {
    var My = this;
    var div = Html.Div.create(wrapperCls || 'fstab').into(container);
    return Html.Table.create(div, tableCls || 'fsy').extend(My, function(self) {
      return {
        init:function() {
          self.wrapper = div;
        },
        working:function(e) {
          self.wrapper.working(e);
        },
        setHeight:function(i) {
          if (div.hasClass('noscroll'))
            self.style.height = (i == null) ? 'auto' : String.px(i);
          else
            div.setHeight(i);
        },
        scrollTo:function(e, padding) {
          Html.Animator.scrollTo(self.wrapper, e, padding);
        },
        hide:function() {
          self.wrapper.hide();
          return self;
        },
        show:function() {
          self.wrapper.show();
          return self;
        }
      }
    })
  }
}
/**
 * CmdBarAppender CmdBar 
 **/
Html.CmdBar = {
  create:function(container, context) {
    var cb = new CmdBar(container, null, context);
    var wrapper = _$(cb.div);
    return Object.augment(cb.appender()).aug({
      wrapper:wrapper,
      into:function(e) {
        e.appendChild(wrapper);
        return this;
      },
      hide:function() {
        wrapper.hide();
        return this;
      },
      show:function() {
        wrapper.show();
        return this;
      },
      showIf:function(e) {
        wrapper.showIf(e);
        return this;
      }
    });
  }
}
/**
 * Table2Col SplitCmdBar
 * @example
 *   self.cb = Html.SplitCmdBar.create(self).ok(self.ok_onclick).split().ok(self.ok2_onclick).end();
 */
Html.SplitCmdBar = {
  /*
   * @arg bool noAlign true to keep buttons centered 
   */
  create:function(container, noAlign) {
    var table = Html.Table2Col.create(container);
    var cbLeft = Html.CmdBar.create(table.left.addClass('w50'));
    var cbRight = Html.CmdBar.create(table.right.addClass('w50'));
    if (! noAlign) {
      cbLeft.wrapper.addClass('cmd-left');
      cbRight.wrapper.addClass('cmd-right');
    }
    cbRight.aug({
      end:function() {
        return table.aug({
          left:cbLeft,
          right:cbRight
        })
      },
      left:cbLeft,
      right:cbRight,
      table:table
    })
    return cbLeft.aug({
      split:function() {
        return cbRight;
      },
      left:cbLeft,
      right:cbRight,
      table:table
    });
  }   
}
/**
 * ScrollTable TableLoader
 *   TableLoader loader()
 */
Html.TableLoader = {
  /*
   * var self = Html.TableLoader.create(container);
   * return self.aug({
   *  init:function() {
   *    self.setHeight(500);
   *    self.thead().trFixed().th('Date').w('10%').th('Type').w('10%').th('Name').w('30%').th('').w('50%');
   *    self.setTopFilter();
   *  },
   *  filter:function(rec) {
   *    return {'Type':rec._type};
   *  },
   *  rowBreaks:function(rec) {
   *    return [rec.date];
   *  },
   *  rowOffset:function(rec) {
   *    return rec.date;
   *  },
   *  add:function(rec, tr) {
   *    tr.td(rec.date, 'bold nw').td(rec._type).select(AnchorDocStub).td(rec.desc);
   *  }
   * });
   */
  create:function(container, tableCls, wrapperCls) {
    var My = this;
    var table = Html.ScrollTable.create(container, tableCls, wrapperCls);
    table.tl = new TableLoader(table.tbody(), 'off', table.wrapper);
    return table.extend(My, function(self) {
      return {
        onload:function(recs) {},
        ondraw:function() {},
        onselect:function(rec) {},
        //
        init:function() {
          self.thead();
        },
        /*
         * @abstract (optional)
         * @return string e.g. return rec.ipc
         */
        rowKey:function(rec) {},
        /*
         * @abstract (optional)
         * @return string[] e.g. return [rec.date, rec.sessionId]
         */
        rowBreaks:function(rec) {},
        /*
         * @abstract (optional)
         * @return string e.g. return rec.cat
         */
        rowOffset:function(rec) {
          var s = self.rowBreaks(rec);
          if (s)
            return s.join();
        },
        /*
         * @abstract (optional)
         * @arg Rec rec filter, will be empty {} on reset
         * @return object e.g. return {'Category':C_Ipc.CATS[rec.cat]}
         */
        filter:function(rec) {},
        /*
         * @abstract (must override if using argless load)
         * @arg fn(Rec[]) callback_recs
         */
        fetch:function(callback_recs) {
          callback_recs(self.recs);
        },
        /*
         * @abstract (required if using load)
         * @arg Rec rec
         * @arg TrAppender tr to build record row e.g. tr.select(rec, rec.name).td(rec.desc)
         */
        add:function(rec, tr) {},
        //
        /*
         * @arg Rec[] recs (optional; if null, must implement fetch) 
         */
        load:function(recs) {
          if (recs)
            self.working(function() {
              self._load(recs);
            })
          else 
            self.working(function() {
              self.fetch(self._load);
            })
        },
        _load:function(recs) {
          self.recs = recs;
          self.reset();
          self.onload(recs);
          self.recs = recs;
          self.draw();
          self.loaded = true;
        },
        /*
         * @return bool
         */
        isLoaded:function() {
          return self.loaded;
        },
        /*
         * Refresh table after record add/update/delete
         * @arg Rec/int e (Rec if add/update, int ID if delete)
         * @requires fetch() and rowKey()  
         */
        update:function(e) {
          var rec = (Object.is(e)) ? e : null;
          var key = rec ? self.rowKey(rec) : e;
          var tr = self.tl.getRowByKey(key);
          if (tr) {
            Html.Animator.highlight(tr);
            self.working(function() {
              self.recs = null;
              self.fetch(function(recs) {
                self.recs = recs;
                if (self.tl.getRowByKey(key)) {
                  if (rec) {
                    self.working(false);
                    self.drawRow(rec);
                    self.tl.reapply();
                    Html.Animator.fade(tr);
                  } else {
                    self.working(false);
                    Html.Animator.fade(tr, function() {
                      self.tl.removeTrs([key]);
                    });
                  }
                }
              });
            });
          } else {
            self.load();
          }
        },
        /*
         * @arg string key
         * @return Rec
         */
        findKey:function(key) {
          for (var i = 0; i < self.recs.length; i++) 
            if (self.rowKey(self.recs[i]) == key) 
              return self.recs[i];
        },
        thead:function() {
          var thead = Html.Table._proto.thead.call(self);
          return thead.aug({
            /*
             * @return TrAppender of header
             */
            tr:function(cls) {
              var tr = Html.Table._protoBody.tr.call(thead, cls);
              self.tl.setTrHead(tr._tr());
              return tr;
            }
          });
        },
        tbody:function() {
          var tbody = Html.Table._proto.tbody.call(self);
          if (! tbody._auged) {
            tbody._auged = tbody.aug({
              /*
               * @arg Rec rec (optional, if supplied remaining args pulled from abstract row methods at top)
               * @args optional (see TableLoader) and may be specified independently below (e.g. breaks())
               * @return TrAppender of body
               */ 
              tr:function(rec, offset, breaks, filter, key, index) {
                if (rec) {
                  offset = self.rowOffset(rec);
                  breaks = self.rowBreaks(rec);
                  filter = self.filter(rec);
                  key = self.rowKey(rec);
                }
                self.tl.createTr(offset, breaks, filter, key, index);
                self._tr = self.tl.tr;
                if (rec)
                  self._tr.rec = rec;
                return this._trAppender(self._tr); 
              },
              _trAppender:function(tr) {
                return {
                  /*
                   * Create a <td>
                   * @arg <e>|string e contents of cell
                   * @arg string cls (optional)
                   * @return TrAppender
                   */
                  td:function(e, cls) {
                    if (String.is(e))
                      self.tl.createTd(cls, e);
                    else
                      self.tl.createTdAppend(cls, e);
                    this._cell = self.tl.td;
                    return this;
                  },
                  colspan:function(i) {
                    this._cell.colSpan = i;
                    return this;
                  },
                  w:function(i) {
                    Html._proto.setWidth.call(this._cell, i);
                    return this;
                  },
                  html:function(s) {
                    this._cell.innerHTML = s;
                  },
                  /*
                   * Create a checkbox cell
                   * @arg string|Rec value (optional; by default check.value set to row key and check.rec set to row rec) 
                   * @arg bool checked (optional)
                   * @return TrAppender
                   */
                  check:function(value, checked) {
                    var rec;
                    if (Object.is(value)) {
                      rec = value;
                      value = '';
                    } else {
                      rec = self._tr.rec;
                      value = value || self.tl.tr.key;
                    }
                    var e = Html.InputCheck.create().setValue(value);
                    self.tl.createTdAppend('check', e);
                    self.tl.tr.check = e;
                    e.aug({
                      rec:rec,
                      tr:self.tl.tr,
                      onclick:function() {
                        e.tr.style.backgroundColor = (e.checked) ? '#FFFF40' : '';  
                      }
                    });
                    if (checked)
                      e.click();
                    return this;
                  },
                  /*
                   * Create a selector
                   * @arg <a>|proto|string e e.g. AnchorTrackItem 
                   * @arg Rec|string rec (or ID) to supply to onselect event (optional, uses rec assigned to row if supplied)
                   * @arg fn(Rec) onclick (optional, self.onselect by default)
                   * @return TrAppender
                   */
                  select:function(e, rec, onclick, cls) {
                    rec = rec || self._tr.rec;
                    cls = cls || 'nw';
                    onclick = onclick || self.onselect;
                    if (String.is(e))
                      e = Html.AnchorRec.asSelect(e, rec, onclick);
                    else if (Html.Anchor.is(e))
                      e.bubble('onclick', onclick.curry(rec));
                    else
                      e = e.create(rec, onclick);
                    this.td(e, cls);
                    self._tr.selector = e;
                    return this;
                  },
                  /*
                   * Create a selector as edit icon
                   * Still fires onselect
                   */
                  edit:function(text) {
                    this.select(Html.AnchorRec.asEdit(text, self._tr.rec));
                    return this;
                  }
                }
              }
            });
          }
          return tbody;
        },
        reset:function() {
          self.tl.reset();
          self.tl.defineFilter(self.filter({}));
          self.loaded = false;
        },
        /*
         * @return [value,..] of checked rows
         */
        getCheckValues:function() {
          var values = [];
          Array.forEach(self.tl.trs(), function(tr) {
            if (tr.check && tr.check.checked)
              values.push(tr.check.value);
          });
          return values;
        },
        /*
         * @return [value,..] of checked rows
         */
        getCheckRecs:function() {
          var values = [];
          Array.forEach(self.tl.trs(), function(tr) {
            if (tr.check && tr.check.checked)
              values.push(tr.check.rec);
          });
          return values;
        },
        /*
         * @arg int[] values of rows to check
         */
        setChecks:function(values) {
          self.resetChecks();
          Array.forEach(self.tl.trs(), function(tr) {
            if (tr.check && values.has(tr.check.value))
              tr.check.click();
          });
        },
        resetChecks:function() {
          Array.forEach(self.tl.trs(), function(tr) {
            if (tr.check && tr.check.checked)
              tr.check.click();
          });
        },
        /*
         * Add a top filter
         * @example 
         * init:function() {
         *   self.setTopFilter().setAllLabel('Show All');
         * }
         */
        addTopFilter:function() {
          self.topFilter = Html.TableLoader.TopFilterBar.create(self);
          return {
            hideAllLabel:function() {
              self.tl.filterHideAll = true;
              return this;
            },
            /*
             * @arg string text 
             */
            setAllLabel:function(text) {
              self.tl.filterAllLabel = text;
              return this;
            },
            /*
             * @arg fn(filter) onset
             */
            onset:function(onset) {
              self.tl.filterOnset = onset;
              return this;
            }
          }
        },
        /*
         * Assign rec filter values to a top filter bar (created upon first use)
         * @arg Rec rec (optional, leave null to reset)
         */
        setTopFilter:function(rec) {
          if (self.topFilter == null) 
            self.addTopFilter();
          self.tl.loadFilterTopbar(this.topFilter, self.filter(rec || {}));
        },
        noWorking:function() {
          self.working = function(e) {
            if (Function.is(e)) 
              async(e);
          }
          return self;
        },
        /*
         * @return TableLoader
         */
        loader:function() {
          return self.tl;
        },
        //
        draw:function() {
          self.working(function() {
            if (self.recs)
              for (var i = 0; i < self.recs.length; i++) 
                self.drawRow(self.recs[i]);
            self.ondraw();
            if (self.topFilter)  
              self.setTopFilter();
            self.working(false);
          })
        },
        drawRow:function(rec) {
          self.add(rec, self.tbody().tr(rec));
        }
      }
    })
  },
  TopFilterBar:{
    create:function(table) {
      var div = Html.Div.create('topfilter').before(table.wrapper);
      return Html.Ul.create('topfilter').into(div);
    }
  }
}
/**
 * Tile Panels
 *   Panel[] panels
 */
Html.Panels = {
  /*
   * @arg {'name':contentProto,..}
   */
  create:function(container, panels) {
    var My = this;
    return Html.Tile.create(container, 'Panels').extend(this, function(self) {
      return {
        onselect:function(panel) {},
        //
        init:function() {
          var panel, proto;
          self.Panels = {};
          for (var name in panels) {
            proto = panels[name];
            panel = My.Panel.create(self, name, proto).bubble('onselect', self.panel_onselect);
            self.Panels[name] = panel;
          } 
        },
        /*
         * @arg fn(My.Panel) callback 
         */
        forEach:function(callback) {
          for (var name in self.Panels) 
            callback(self.Panels[name]);
        },
        reset:function() {
          if (self.selected)
            self.selected.reset();
        },
        setMaxHeight:function(i) {
          self.forEach(function(p) {
            p.setMaxHeight(i);
          })
        },
        //
        panel_onselect:function(panel) {
          if (panel.name != self.selname) {
            self.selname = panel.name;
            self.selected = panel.content;
            self.forEach(function(p) {
              p.hideIf(p.name != panel.name);
            })
          }
        }
      }
    })
  },
  Panel:{
    create:function(container, name, contentProto) {
      return Html.Tile.create(container, 'Panel').hide().extend(function(self) {
        return {
          onselect:function(panel) {},
          //
          init:function() {
            self.name = name;
          },
          select:function() {
            self.show();
            if (self.content == null)  
              self.createContent();
            self.onselect(self);
            return self.content;
          },
          createContent:function() {
            self.content = contentProto.create(self);
            self.setContentMaxHeight();
          },
          setMaxHeight:function(i) {
            if (self.maxHeight != i) {
              self.maxHeight = i;
              self.setContentMaxHeight();
            }
          },
          setContentMaxHeight:function() {
            if (self.maxHeight)
              if (self.content && self.content.setMaxHeight) 
                self.content.setMaxHeight(self.maxHeight);
          }
        }
      })
    }
  }
} 
/**
 * Tile Tiles
 */
Html.Tiles = {
  /*
   * self.tiles = Html.Tiles.create(self, [
   *   self.recips = My.RecipsForm.create(self, recips);
   *   self.portal = My.PortalTile.create(self)]);
   */
  create:function(container, tiles) {
    var self = Html.Tile.create(container);
    return self.aug({
      init:function() {
        self._count = 0;
        self.tiles = [];
        tiles.forEach(self.add);
      },
      add:function(tile) {
        self.tiles.append(tile.into(self).hide().aug({
          _tileIndex:self._count++,
          select:function() {
            return self.select(tile);
          }
        }))
      },
      select:function(tile) {
        self.tiles.forEach(function(t, i) {
          t.showIf(i == tile._tileIndex);
        })
        if (tile.ontileselect)
          tile.ontileselect();
        return self._selected = tile;
      },
      selected:function() {
        return self._selected;
      },
      setMaxHeight:function(i) {
        self.tiles.forEach(function(t) {
          if (t.setMaxHeight) {
            t.show();
            t.setMaxHeight(i);
            t.hide();
          }
        })
        if (self._selected)
          self.selected().show();
      }
    })
  }
}
/**
 * Div TabPanels
 *   Div bar
 *     TabBar tb
 *   Div panels
 */
Html.TabPanels = {
  /*
   * @arg string[] panelTitles ['Documentation History',..]   
   * @arg string[] tabCaptions ['Documents',..] (optional, will use titles if omitted)
   */
  create:function(container, panelTitles, tabCaptions) {
    var self = Html.Div.create().into(container.clean());
    return self.aug({
      /*
       * @events
       */
      panel_onselect:function(panel) {},
      //
      init:function() {
        self.bar = Html.TabPanels.Bar.create(self, {
          onselect:function(panel) {
            self.panel_onselect(panel);
          }
        });
        self.panels = Html.TabPanels.Panels.create(self, panelTitles.length);
        self.bar.load(panelTitles, tabCaptions);
      },
      /*
       * @arg int index
       */
      select:function(index) {
        self.bar.select(index);
      },
      /*
       * @return Panel
       */
      selected:function() {
        return self.panels.get(self.bar.tb.getSelIndex());
      },
      /*
       * @return int
       */
      getSelIndex:function() {
        return self.bar.tb.getSelIndex();
      }
    });
  },
  Bar:{
    create:function(container, augs) {
      var self = Html.Tile.create(container, 'tabbar');
      return self.aug({
        onselect:function(panel) {},
        //
        load:function(titles, captions) {
          self.tb = new TabBar(container, titles, captions);
          self.tb._onSelectCallback = function(index, panel) {
            Html.Window.flickerFixedRows();
            self.onselect(panel);
          };
        },
        select:function(index) {
          self.tb.select(index);
        }
      }).aug(augs);
    }
  },
  Panels:{
    create:function(container, count) {
      var self = Html.Tile.create(container, 'tabpanels');
      self.panels = [];
      for (var i = 0; i < count; i++)
        self.panels.push(Html.TabPanels.Panel.create(self, i));
      return self.aug({
        get:function(index) {
          return self.panels[index];
        },
        getAll:function() {
          return self.panels;
        },
        /*
         * @arg fn(panel) callback 
         */
        forEach:function(callback) {
          Array.forEach(self.panels, callback);
        }
      });
    }
  },
  Panel:{
    create:function(container, index) {
      var self = Html.Tile.create(container, 'tabpanel');
      return self.aug({
        index:index
      });
    }
  }
}
/**
 * Div Pop
 *   Div cap
 *     Div caption
 *     Anchor ctlbox
 *   Div content
 */
Html.Pop = {
  /*
   * AbcPop = {                                     
   *   pop:function(x, y) {                   
   *     AbcPop = this.create().pop(x, y);      
   *   },                                           
   *   create:function() {                      
   *     var self = Html.Pop.create('Caption');     
   *     return self.aug({
   *       init:function() {
   *         //..
   *       },                          
   *       pop:function(x, y) {                     
   *         //..          
   *         self.show(z);
   *         return self; 
   *       }               
   *     });    
   *   }                   
   * }                     
   * AbcPop.pop('x', 'y').aug({
   *   onshow:function(z) {
   *     //..
   *   }
   * });
   */
  create:function(caption, width) {  
    var id = 'pop' + String.rnd();
    var pop = Html.Div.create('pop', {
      init:function() {
        this.cap = Html.Pop.Cap.create(this, caption);
        this.content = Html.Pop.Content.create(this);
      }
    });
    Html.Window.append(pop.setWidth(width));
    return this._augment(pop);
  },
  /*
   * From existing <html> source
   */
  from:function(id) {
    var pop = _$(id);
    if (pop.cap == null) {
      pop.cap = self.firstChild;
      pop.content = self.lastChild;
      this._augment(pop);
    }
    return pop;
  },
  _augment:function(self) {
    return self.aug({
      //
      POP_POS:Pop.POS_CENTER,
      //
      onpop:function() {},   // prior to show; passes all pop() args
      onshow:function() {},  // after show; passes all pop() args 
      onclose:function() {},
      /*
       * @return self
       */
      pop:function() {
        self.reset();
        self.onpop.apply(this, arguments);
        self.show.apply(this, arguments);
        return self;
      },
      reset:function() {},
      /*
       * @abstract 
       * @arg CmdBar cb
       */
      buildCmd:function(cb) {},
      //
      /*
       * @return int viewport height
       */
      fullscreen:function(maxw, maxh) {
        var w = Html.Window.getViewportDim();
        self.setWidth(Math.smaller(w.width - 40, maxw || 1000));
        return Math.smaller(w.height - 150, maxh || 1000);
      },
      show:function() {
        Pop.show(self, null, self.POP_POS);
        self.onshow.apply(this, arguments);
        return self;
      },
      close:function() {
        Pop.close();
      },
      showPosCursor:function() {
        Pop.showPosCursor(self);
        self.onshow.apply(this, arguments);
        return self;
      },
      setCaption:function(text) {
        self.cap.caption.setText(text);
      },
      bubble:function(event) {  // "one-time" bubbles for global pops
        var was = self[event];
        Html._proto.bubble.apply(self, arguments);
        var to = self[event];
        self[event] = function() {
          to.apply(self, arguments);
          self[event] = was;  
        }
        return self;
      },
      withErrorBox:function() {
        self.aug({
          errorbox:Html.Pop.ErrorBox.create(self),
          onerror:function(e) {
            self.working(false);
            self.errorbox.showException(e);
          },
          pop:self.pop.append(function() {
            self.errorbox.hide();
          })
        })
        return self;
      },
      setOnkeypresscr:function(onkeypresscr) {
        self.aug(EventKeypressCr);
        self.onkeypresscr = onkeypresscr;
      }
    });
  },
  Cap:{
    create:function(container, text, onClose) {
      var cap = Html.Div.create('pop-cap').into(container);
      cap.caption = Html.Div.create().into(cap).setText(text);
      cap.ctlbox = Html.Anchor.create('pop-close', null, function(){container.close()}).into(cap);
      return cap;
    }
  },
  Content:{
    create:function(container) {
      return Html.Div.create('pop-content').into(container);
    }
  },
  Frame:{
    create:function(container, caption, anchor) {
      var div = Html.Div.create('pop-frame').into(container);
      var head;
      var cap = Html.H1.create().into(div).hide();
      if (anchor) {
        head = Html.Div.create('pop-frame-head').into(div);
        head.add(cap).add(anchor);
      }
      var self = Html.Div.create('pop-frame-content').into(div);
      self = self.aug({
        frame:div,
        head:head,
        cap:cap,
        setCaption:function(text) {
          self.cap.show().setText(text);
        },
        setCaptionHtml:function(html) {
          self.cap.show().html(html);
        }
      })
      if (caption)
        self.setCaption(caption);
      return self;
    }
  },
  CmdBar:{
    create:function(pop) {
      var cb = Html.CmdBar.create(pop.content, pop);
      return cb.aug({
        build:function() {
          if (! cb._built) {
            pop.buildCmd(cb);
            cb._built = cb;
          }
          return cb;
        },
        reset:function() {
          cb.container().clean();
          cb._built = null;
        },
        saveCancel:function(cap) {
          cb.save(pop.save_onclick, cap).cancel(pop.close);
        }
      });
    }
  },
  ErrorBox:{
    create:function(pop) {
      return Html.Div.create('pop-error mpe').after(pop.content).aug({
        showException:function(e) {
          this.show(e.message);
        },
        show:function(html) {
          this.html(html);
          this.style.display = '';
          Html.Window.flickerFixedRows();
        }
      });
    }
  }
}
/**
 * Pop IncludedSourcePop
 */
Html.IncludedSourcePop = {
  /*
   * @arg string name 'PatientSelector' 
   * @arg string id 'pop-ps' 
   * @arg fn(Pop) oninclude
   */
  create:function(name, id, oninclude) {
    Pop.cacheMousePos();
    var src = 'js/pops/inc/' + name + '.php';
    Includer.getWorking(src, function() {
      var self = Html.Pop.from(id);
      oninclude(self);
    });
  }
}
/**
 * Pop DirtyPop
 */
Html.DirtyPop = {
  create:function(caption, width) {
    return Html.Pop.create(caption, width).extend(function(self) {
      return {
        ondirty:function() {},
        save:function() {},
        //
        onshow:function() {
          self.setDirty(false);
        },
        setDirty:function(value) {
          self._dirty = value;
          if (! self._dirty && value)
            self.ondirty();
        },
        isDirty:function() {
          return self._dirty;
        },
        close:function(saved) {
          if (saved)
            Pop.close(true);
          else
            Pop.Confirm.closeCheckDirty(self, self.save);
        }
      }
    })
  }
}
/**
 * DirtyPop RecordEntryPop
 *   PopFrame frame
 *   EntryForm form
 *   CmdBar cmd
 */
Html.RecordEntryPop = {
  /*
   * AbcEntryPop = {
   *   pop:function(rec) {
   *     AbcEntryPop = this.create().pop(rec); 
   *   },
   *   create:function() {
   *     var self = Html.RecordEntryPop.create('Caption');
   *     return self.aug({
   *       //..
   *     });
   *   }
   * }
   * AbcEntryPop.pop(rec).aug({
   *   onsave:function(rec) {..}
   *   ondelete:function(id) {..}
   * });
   */
  create:function(caption, width, frameCaption) {
    var My = this;
    return Html.DirtyPop.create(caption, width).extend(My, function(self) {
      return {
        //
        onshow:function(rec, focusId) {  // assumes focusId passed to pop, but this is optional    
          self.form.focus(focusId);
        },
        onload:function(rec) {},  // supplies all pop arguments (rec, focusId, ..)
        onsave:function(rec) {},  
        onerror:function(e) {
          self.working(false);
          self.errorbox.show(e.message);
          Html.Window.flickerFixedRows();
        },
        /*
         * @abstract
         * @arg EntryForm ef
         * @arg Rec rec
         */
        buildForm:function(ef, rec) {},
        /*
         * @abstract 
         * @arg CmdBar cb
         */
        buildCmd:function(cb) {
          cb.save(self.save_onclick).cancel(self.cancel_onclick);
        },
        /*
         * @abstract 
         * @arg Rec rec
         * @arg fn(Rec) onsuccess
         * @arg fn(Rec) onerror 
         */
        save:function(rec, onsuccess, onerror) {
        },
        /*
         * @abstract
         * @return bool
         */
        isDeletable:function(rec) {
          return rec != null;
        },
        //
        init:function() {
          self.frame = Html.Pop.Frame.create(self.content, frameCaption);
          self.form = My.Form.create(self);
          self.cmd = Html.Pop.CmdBar.create(self); 
          self.errorbox = Html.Pop.ErrorBox.create(self);
        },
        /*
         * @arg Rec rec
         */
        pop:function(rec) {
          self.rec = rec;
          self.setDirty(false);
          self.onload.apply(this, arguments);
          self.form.build().setRecord(self.rec);
          self.cmd.build();
          self.errorbox.hide();
          self.show.apply(this, arguments);
          self.cmd.showDelIf(self.isDeletable(self.rec))
          return self;
        },
        isDirty:function() {
          return self.form.isRecordChanged();
        },
        //
        close_asSaved:function(rec) {
          self.close(true);
          self.onsave(rec);
        },
        save_onclick:function() {
          var rec = self.form.applyTo(self.rec);
          self.working(function() {
            self.errorbox.hide();
            self.save(rec, 
              function(rec) {
                self.working(false);
                self.close_asSaved(rec);
              },
              [self.onerror, self]);
          });
        },
        cancel_onclick:function() {
          self.close();
        }
      }
    })
  },
  Form:{
    create:function(pop) {
      var ef = Html.EntryForm.create(pop.frame);
      return ef.aug({
        build:function() {
          if (! ef._built) {
            pop.buildForm(ef);
            ef._built = ef;
          }
          return ef;
        }
      });
    }
  }
}
/**
 * RecordEntryPop RecordEntryDeletePop
 */
Html.RecordEntryDeletePop = {
  create:function(caption, width, frameCaption) {
    var self = Html.RecordEntryPop.create(caption, width);
    return self.aug({
      /*
       * @events
       */
      onsave:function(rec) {},  
      ondelete:function(id) {},  // @arg int id of Rec deleted
      /*
       * @abstract 
       * @arg CmdBar cb
       */
      buildCmd:function(cb) {
        cb.save(self.save_onclick).del(self.del_onclick).cancel(self.cancel_onclick);
      },
      /*
       * @abstract 
       * @arg Rec rec
       * @arg fn(id) onsuccess 
       */
      remove:function(rec, onsuccess) {
      },
      /*
       * @abstract
       * @return string
       */
      getDeleteNoun:function() {
        return 'record';
      },
      //
      del_onclick:function() {
        var rec = self.form.getRecord();
        Pop.Confirm.showYesNo('Are you sure you want to remove this ' + this.getDeleteNoun() + '?', function() {
          self.working(true);
          self.errorbox.hide();
          self.remove(rec, 
            function(id) {
              self.working(false);
              self.close();
              self.ondelete(id);
            },
            [self.onerror, self]);
        });
      }
    });
  }
}
/**
 * RecEntryCallbackPop.pop(rec, callback)
 * callback supplies either Rec of add/update or [int] ID of delete
 */
Html.RecEntryPop = {
  create:function(caption, width, frameCaption) {
    var self = Html.RecordEntryDeletePop.create(caption, width);
    return self.aug({
      onshow:function() {},
      buildForm:function(ef) {},
      save:function(rec, callback_rec) {},
      remove:function(rec, callback_id) {},
      onload:function(rec, callback) {
        self.setCallback(callback);
      },
      //
      setCallback:function(callback) {
        if (callback) {
          self.onsave = callback;
          self.ondelete = function(id) {
            callback([id]);
          }
        }
      }
    })
  }
}
/**
 * Span TextAnchor
 *   InputText input
 *   Anchor anchor
 */
Html.TextAnchor = {
  create:function(anchorCls, inputSize) {
    var self = Html.Span.create();
    return self.aug({
      onclick_anchor:function(text) {},
      //
      init:function() {
        self.input = Html.InputText.create().setSize(inputSize).into(self).aug({
          onkeypresscr:function() {
            self.anchor_onclick();
          }
        });
        self.anchor = Html.Anchor.create(anchorCls, null, self.anchor_onclick).into(self);
      },
      setText:function(text) {
        self.input.value = String.denull(text);
      },
      getText:function() {
        return self.input.value;
      },
      setFocus:function() {
        self.input.setFocus();
      },
      //
      anchor_onclick:function() {
        self.onclick_anchor(self.getText());
      }
    });
  }
}
/**
 * TextAnchor SearchTextAnchor
 *   InputText input
 *   Anchor anchor 
 */
Html.SearchTextAnchor = {
  create:function(inputSize) {
    return Html.TextAnchor.create('mglass', inputSize);
  }
}
/**
 * AnchorTab AnchorTab 
 */
Html.AnchorTab = {
  create:function(text, cls) {
    var at = new AnchorTab(text || '(Select)', cls || 'atedit');
    return Class.augment(at, null, {
      checks:function(recs, cols) {
        at.loadChecks(recs, null, null, null, null, null, cols);
        return at;
      },
      radios:function(recs, cols) {
        at.loadRadios(recs, null, null, null, null, cols);
        return at;
      },
      okCancel:function(onok) {
        at.appendCmd(null, onok);
        return at;
      },
      cancelOnly:function() {
        at.appendCmd(AnchorTab.BUTTONS_CANCEL_ONLY);
        return at;
      }
    });
  }
}
/**
 * Anchor AnchorTabSelector
 */
Html.AnchorTabSelector = {
  create:function(cls, cols) {
    cols = cols || 3;
    var at = new AnchorTab('(Select)', cls || 'atedit');
    var self = at.anchor;
    return self.aug({
      onchange:function(value) {},
      //
      /*
       * @arg Rec[] recs
       * @arg string valueFid (optional)
       * @arg string textFid (optional)
       */
      radios:function(recs, valueFid, textFid) {
        at.loadRadios(recs, valueFid, textFid, null, null, cols);
        at.appendCmd(AnchorTab.BUTTONS_CANCEL_ONLY);
        at.setOnchange(self.at_onchange);
        return self;
      },
      setValue:function(value) {
        at.setValue(value);
      },
      getValue:function() {
        return at.getValue();
      },
      getText:function() {
        return self.innerText;
      },
      //
      at_onchange:function() {
        self.onchange(at.getValue());
      }
    })
  }
}
/**
 * SearchTextAnchor Picker
 */
Html.Picker = {
  create:function(inputSize, pop) {
    return Html.SearchTextAnchor.create(inputSize).extend(this, function(self) {
      return {
        init:function() {
          self.pop = pop;
        },
        getValue:function() {
          return self.value;
        },
        set:function(value, text) {
          self.value = value;
          self.setText(text);
        },
        //
        onclick_anchor:function(text) {
          self.pop.show(self.value, self.getText());
        }
      }
    })
  }
}
/**
 * Picker RecPicker
 */
Html.RecPicker = {
  /*
   * @arg int inputSize
   * @arg PickerPop pop
   */
  create:function(inputSize, pop) {
    return Html.Picker.create(inputSize, pop).extend(function(self) {
      return {
        onset:function(rec) {},
        //
        /*
         * @arg Rec rec
         * @return string
         */
        getValueFrom:function(rec) {},
        /*
         * @arg Rec rec
         * @return string
         */
        getTextFrom:function(rec) {},
        /*
         * @arg Rec rec
         */
        set:function(rec) {
          if (rec) 
            self.setValueText(self.getValueFrom(rec), self.getTextFrom(rec));
          else
            self.setValueText(null, '');
        },
        /*
         * @return string
         */
        getValue:function() {
          return self.value;
        },
        /*
         * @return string
         */
        getText:function() {
          return self.input.value;
        },
        //
        setValueText:function(value, text) {
          self.value = value;
          self.setText(text);
        },
        showPop:function(value, text) {
          self.pop.pop(value, text).bubble('onselect', self.pop_onselect);  
        },
        onclick_anchor:function(text) {
          self.showPop(self.value, self.getText());
        },
        pop_onselect:function(rec) {
          self.set(rec);
          self.onset(rec);
        }
      }
    })
  }
}
/**
 * ScrollTable RecordTable
 */
Html.RecordTable = {
  create:function(container, cls) {
    var self = Html.ScrollTable.create(container, cls);
    return self.aug({
      /*
       * @events
       */
      onselect:function(rec) {},
      onload:function(recs) {},
      /*
       * @abstract (must override if using argless load)
       * @arg fn(Rec[]) callback_recs
       */
      fetch:function(callback_recs) {
        callback_recs(self.recs);
      },
      /*
       * @abstract
       * @arg Rec rec
       * @arg TrAppender tr to build record row e.g. tr.select(rec, rec.name).td(rec.desc)
       */
      add:function(rec, tr) {},
      //
      /*
       * @arg Rec[] recs (optional; if null, must implement fetch) 
       */
      load:function(recs) {
        self.recs = recs;
        self.working(true);
        self.fetch(function(recs) {
          self.working(false);
          self.recs = recs;
          self.draw();
          self.onload(recs);
        });
      },
      //
      tbody:function() {
        var tbody = Html.Table._proto.tbody.call(self);
        if (! tbody._auged) {
          tbody.aug({
            tr:function(cls) {
              if (cls == null)
                cls = Math.isEven(self.ct++) ? '' : 'off';
              var appender = Html.Table._protoBody.tr.call(tbody, cls);
              return Class.augment(appender, null, {
                /*
                 * @arg Rec rec
                 * @arg proto|<a>|string e e.g. AnchorTrackItem
                 * @return TrAppender 
                 */
                select:function(rec, e) {
                  if (String.is(e))
                    e = Html.AnchorRec.asSelect(e, rec, self.onselect);
                  else if (Html.Anchor.is(e))
                    e.bubble('onclick', self.onselect.curry(rec));
                  else
                    e = e.create(rec, self.onselect);
                  return this.td(e);
                }
              });
            }
          });
          tbody._auged = self;
        }
        return tbody;
      },        
      clean:function() {
        self.tbody().clean();
        self.ct = 0;
      },
      draw:function() {
        self.clean();
        self.working(function() {
          Array.forEach(self.recs, function(rec) {
            self.add(rec, self.tbody().tr());
          });
          self.working(false);
        });
      }
    })
  }
}
/**
 * Div SearchRecordTable
 *   Div searcher
 *     SearchTextAnchor input
 *     Span filterbox
 *   RecordTable table 
 */
Html.SearchRecordTable = {
  create:function(container) {
    var self = Html.Div.create().into(container);
    return self.aug({
      /*
       * @events
       */
      onload:function(recs) {},
      onselect:function(rec) {},
      /*
       * @abstract
       * @arg fn(Rec[]) callback_recs 
       */
      fetch:function(callback_recs) {},
      /*
       * @abstract
       * @arg Rec rec
       * @arg RegExp search
       * @return bool true if rec should be displayed based upon search  
       */
      applies:function(rec, search) {},
      /*
       * @abstract
       * @arg Rec rec
       * @arg TrAppender tr to build record row e.g. tr.select(rec, rec.name).td(rec.desc)
       */
      add:function(rec, tr) {},
      //
      init:function() {
        self.searcher = Html.SearchRecordTable.Searcher.create(self).aug({
          onclick_search:function(text) {
            self.load(text);
          }
        })
        self.table = Html.SearchRecordTable.Table.create(self).aug({
          fetch:function(callback_recs) {
            self.fetch(callback_recs);
          },
          add:function(rec, tr) {
            self.add(rec, tr);
          },
          onselect:function(rec) {
            self.onselect(rec);
          },
          onload:function(recs) {
            self.onload(recs);
          }
        })
      },
      load:function(text) {
        self.searcher.setSearchText(text);
        if (self.loaded) {
          self.table.draw();
        } else {
          self.loaded = self;
          self.table.load();
        }
      },
      getSearchText:function() {
        return self.searcher.getSearchText();
      },
      setFocus:function() {
        self.searcher.setFocus();
      },
      thead:function() {
        return self.table.thead();
      },
      //
      tbody:function() {
        return self.table.tbody();
      },
      clean:function() {
        self.table.clean();
        self.loaded = null;
      }
    });
  },
  Table:{
    create:function(container) {
      var self = Html.RecordTable.create(container);
      return self.aug({
        draw:function() {
          self.clean();
          self.working(function() {
            var search = container.searcher.getSearchRegExp();
            var unapplies = [];
            for (var i = 0; i < self.recs.length; i++) {
              var rec = self.recs[i];
              if (container.applies(rec, search)) 
                container.add(rec, self.tbody().tr(''));
              else 
                unapplies.push(rec);
            }
            for (var i = 0; i < unapplies.length; i++) 
              container.add(unapplies[i], self.tbody().tr('off'));
            self.working(false);
          })
        }
      })
    }
  },
  Searcher:{
    create:function(container) {
      var self = Html.Div.create('mb5').into(container);
      return self.aug({
        onclick_search:function(text) {},
        //
        init:function() {
          self.input = Html.SearchTextAnchor.create().into(self).aug({
            onclick_anchor:function(text) {
              self.onclick_search(text);
            }
          });
          self.filterbox = Html.Span.create().into(self);
        },
        getSearchText:function() {
          return self.input.getText();
        },
        getSearchRegExp:function() {
          var text = String.nullify(self.input.getText());
          return (text) ? new RegExp(text, 'i') : null;
        },
        setSearchText:function(text) {
          self.input.setText(text);
        },
        setFocus:function() {
          self.input.setFocus();
        }
      });
    } 
  }
}
/**
 * @deprecated use RecPicker instead
 * Picker RecordPicker
 *   PickerPop pop
 */
Html.RecordPicker = {
  create:function(popCaption, inputSize, popWidth) {
    var My = this;
    var pop = Html.PickerPop.create(popCaption, popWidth);
    return Html.Picker.create(inputSize, pop).extend(My, function(self) {
      return {
        /*
         * @events
         */
        onset:function(rec) {},
        //
        init:function() {
          self.pop = pop.aug({
            onselect:function(rec) {
              self.set(rec);
              self.onset(rec);
            },
            onclose:function() {
              self.setFocus();
            },
            table_fetch:function(callback_recs) {
              self.fetch(callback_recs);
            },
            table_applies:function(rec, search) {
              return self.applies(rec, search);
            },
            table_add:function(rec, tr) {
              self.add(rec, tr);
            },
            cmdbar_buttons:function(cb) {
              self.buttons(cb);
            }
          })
        },
        /*
         * @arg Rec rec
         */
        set:function(rec) {
          if (rec) 
            self.setValueText(self.getValueFrom(rec), self.getTextFrom(rec));
          else
            self.setValueText(null, '');
        },
        /*
         * @arg string value
         * @arg string text
         */
        setValueText:function(value, text) {
          self.value = value;
          self.setText(text);
        },
        /*
         * @return string
         */
        getValue:function() {
          return self.value;
        },
        /*
         * @return string
         */
        getText:function() {
          return self.input.value;
        },
        //
        /*
         * @abstract
         * @arg Rec rec
         * @return string
         */
        getValueFrom:function(rec) {},
        /*
         * @abstract
         * @arg Rec rec
         * @return string
         */
        getTextFrom:function(rec) {},
        /*
         * @abstract
         * @arg fn(Rec[]) callback_recs
         */
        fetch:function(callback_recs) {},
        /*
         * @abstract
         * @arg Rec rec
         * @arg RegExp search
         * @return bool true if rec should be displayed based upon search  
         */
        applies:function(rec, search) {},
        /*
         * @abstract
         * @arg Rec rec
         * @arg TrAppender tr to build record row e.g. tr.select(rec, rec.name).td(rec.desc)
         */
        add:function(rec, tr) {},
        thead:function() {
          return self.pop.table.thead();
        },
        /*
         * Override to create add'l buttons
         */
        buttons:function(cmd) {
          cmd.cancel(self.pop.close);
        }
      }
    })
  }
}
/**
 * AnchorAction AnchorPicker
 *   PickerPop pop
 */
Html.AnchorPicker = {
  /*
   * @arg string cls
   * @arg string defaultText (optional) 
   * @arg string popCaption (optional)
   * @arg int popWidth (optional)
   */
  create:function(cls, defaultText, popCaption, popWidth) {
    return Html.AnchorAction.create(cls, defaultText || '(Select)').extend(function(self) {
      return {
        onset:function(rec) {},
        //
        fetch:function(fn_recs) {},
        applies:function(rec, search) {},
        add:function(rec, tr) {},
        getValueFrom:function(rec) {},
        getTextFrom:function(rec) {},
        //
        init:function() {
          self.pop = Html.PickerPop.create(popCaption, popWidth);
          self.pop.bubble('onselect', self.pop_onselect).bubble('table_fetch', self, 'fetch').bubble('table_applies', self, 'applies').bubble('table_add', self, 'add').bubble('cmdbar_buttons', self, 'buttons');
        },
        set:function(rec) {
          if (rec) 
            self.setValueText(self.getValueFrom(rec), self.getTextFrom(rec));
          else
            self.setValueText(null, defaultText);
        },
        thead:function() {
          return self.pop.table.thead();
        },
        onclick:function() {
          self.pop.show(self.value, self.getText());          
        },
        //
        setValueText:function(value, text) {
          self.value = value;
          self.setText(text);
        },
        getValue:function() {
          return self.value;
        },
        getText:function() {
          return (self.value) ? self.innerText : '';
        },
        pop_onselect:function(rec) {
          self.set(rec);
          self.onset(rec);
        },
        buttons:function(cmd) {}
      }
    })
  }
}
/**
 * Pop PickerPop
 *   SearchRecordTable table
 */
Html.PickerPop = {
  create:function(caption, width) {
    var My = this;
    return Html.Pop.create(caption || 'Selector', width || 600).extend(My, function(self) {
      return {
        POP_POS:Pop.POS_CURSOR, 
        onbeforeshow:function(/* pop() args */) {},  
        onselect:function(rec) {}, 
        table_fetch:function(callback_recs) {},
        table_applies:function(rec, search) {},
        table_add:function(rec, tr) {},
        cmdbar_buttons:function(cb) {
          cb.cancel(self.close)
        },
        //
        init:function() {
          self.table = Html.SearchRecordTable.create(self.content).aug({
            onselect:function(rec) {
              self.select(rec);
            },
            fetch:function(callback) {
              self.table_fetch(callback);
            },
            applies:function(rec, search) {
              return self.table_applies(rec, search);
            },
            add:function(rec, tr) {
              self.table_add(rec, tr);
            }
          })
          self.cmd = My.CmdBar.create(self);
        },
        clean:function() {
          self.table.clean();
        },
        getSearchText:function() {
          return self.table.getSearchText();
        },
        show:function(value, text) {
          self.onbeforeshow.apply(this, arguments);
          self.cmd.load();
          Pop.show(self, null, self.POP_POS);
          self.table.load(text);
          self.table.setFocus();
          self.onshow.apply(this, arguments);
          return self;
        },
        select:function(rec) {
          self.close();
          self.onselect(rec);
        }
      }
    })
  },
  CmdBar:{
    create:function(pop) {
      var self = Html.CmdBar.create(pop.content);
      return Class.augment(self, null, {
        load:function() {
          if (! self.loaded) {
            pop.cmdbar_buttons(self);
            self.loaded = self;
          }
        }
      })
    }
  }
}
/**
 * Json
 */
Json = {
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
Cookies = {
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