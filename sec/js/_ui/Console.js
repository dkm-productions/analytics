/* 
 * Console components
 */
//
Pidi = Class.define(null, {
  pidi:null,  // e.g. 'pid', 'pid@injector', 'pid+cloneix' 
  pid:null,
  cloneix:null,
  injector:null,
  //
  isSingle:function() {
    return this.cloneix == null;
  },
  isClone:function() {
    return this.cloneix > 0;
  },
  isInject:function() {
    return this.injector != null;
  }
},{
  from:function(e) {
    if (Array.is(e))
      return this.from_strings(e);
    else
      return this.from_string(e);
  },
  asClone:function(pid, cloneix) {
    return Pidi.from(pid + '+' + cloneix); 
  },
  asSingle:function(pid) {
    return Pidi.from(String.from(pid));
  },
  //
  from_string:function(pidi) {
    pidi = String.from(pidi);
    var me = new Pidi();
    if (pidi) {
      me.pidi = pidi;
      if (pidi.indexOf("+") > -1) {
        var pa = pidi.split("+");
        me.pid = pa[0];
        me.cloneix = String.toInt(pa[1]);
      } else {
        var pa = pidi.split("@");
        me.pid = pa[0];
        me.injector = pa[1];
      }
    }
    return me;
  },
  from_strings:function(pidis) {
    var mes = [];
    for (var i = 0; i < pidis.length; i++) 
      mes.push(this.from_string(pidis[i]));
    return mes;
  }
})
/**
 * Ul UsedParList
 */
UsedParList = {
  create:function(container) {
    var My = this;
    return Html.Ul.create('parlistul').into(container).extend(function(self) {
      return {
        ondraw:function() {},  
        onclickremove:function(pidi, desc) {},
        onremove:function(pid) {},  // when last instance of pid removed
        //
        init:function() {
          self.counts = My.Counts.create();
          self.reset();
        },
        load:function(pidis) {
          self.counts.load(pidis);
          pidis.forEach(function(pidi) {
            self.addAnchor(pidi);
          })
          if (self.map)
            self.draw();
        },
        loadMap:function(map) {
          self.map = map;
          if (self.map)
            self.draw();
        },
        add:function(pid) {
          var par = self.map.getParByPid(pid);
          if (! par.cloneable && self.counts.isUsed(pid))
            return;
          var pidi = self.counts.add(pid, par.cloneable);
          self.addAnchor(pidi);
          return pidi;
        },
        remove:function(pidi) {
          var stillUsed = self.counts.remove(pidi);
          self.removeAnchor(pidi);
          self.draw();
          if (! stillUsed)
            self.onremove(pidi.pid);
        },
        getUsedPids:function() {
          return self.counts.getPids(); 
        },
        //
        reset:function() {  // intentionally does not clear map
          self.clean();
          self.counts.reset();
          self.anchors = {};  // {'pidi':<a>,..}
          return self;
        },
        draw:function() {
          Map.forEach(self.anchors, function(anchor) {
            anchor.setText(self.getParDesc(anchor.pidi));
          })
          self.ondraw();
        },
        addAnchor:function(pidi) {
          var text = self.getParDesc(pidi);
          self.anchors[pidi.pidi] = Html.Anchor.create('used', text).set('pidi', pidi).into(self.li()).bubble('onclick', function() {
            self.onclickremove(pidi, self.getParDesc(pidi));
          })
        },
        removeAnchor:function(pidi) {
          var anchor = self.anchors[pidi.pidi];
          if (anchor)
            anchor.parentElement.remove();
        },
        getParDesc:function(pidi) {
          var par = self.map && self.map.getParByPid(pidi.pid);
          if (par) {
            var desc = par.desc;
            if (pidi.isClone()) 
              desc += ' #' + (self.counts.getPidiIndex(pidi) + 1);
            return desc;
          }
        }
      }
    })
  },
  Counts:{
    create:function() {
      return {
        reset:function() {
          this.pids = {};  // {'pid':[pidi,pidi..],..}
        },
        load:function(pidis) {
          var self = this;
          pidis.forEach(function(pidi) {
            self.push(pidi);
          })
        },
        add:function(pid, asClone) {
          var pidi;
          if (asClone) {
            var last = this.getLastPidi(pid);
            var cloneix = (last) ? last.cloneix + 1 : 1;
            pidi = Pidi.asClone(pid, cloneix);
          } else {
            pidi = Pidi.asSingle(pid);
          }
          this.push(pidi);   
          return pidi;
        },
        remove:function(pidi) {
          var i = this.getPidiIndex(pidi);
          if (i > -1) {
            var pid = pidi.pid;
            this.pids[pid].unset(i);
            if (this.pids[pid].length == 0)
              delete this.pids[pid];
          }
          return this.isUsed(pidi.pid);
        },
        getPids:function() {
          return Map.keys(this.pids);
        },
        getPidiIndex:function(pidi) {
          var arr = this.pids[pidi.pid];
          if (arr) { 
            for (var i = 0; i < arr.length; i++) 
              if (arr[i].pidi == pidi.pidi)
                return i;
          }
        },
        isUsed:function(pid) {
          return this.pids[pid] != null;
        },
        //
        getLastPidi:function(pid) {
          if (this.isUsed(pid)) 
            return this.pids[pid].end();   
        },
        push:function(pidi) {
          var pid = pidi.pid;
          if (! this.isUsed(pid))
            this.pids[pid] = [];
          this.pids[pid].push(pidi);
        }
      }
    }
  }
}
/**
 * Div ParPreviewTrigger
 */
ParPreviewTrigger = {
  create:function() {
    var My = this;
    ParPreviewTrigger = Html.Div.create('ParPreviewTrigger').extend(function(self) {
      return {
        //
        init:function() {
          Html.Window.append(self.hide());
          self.pop = My.Pop.create().bubble('onshow', self.pop_onshow).bubble('onhide', self.pop_onhide).bubble('onmouseover', self.pop_onmouseover).bubble('onreqinsert', self.pop_onreqinsert).bubble('onreqclose', self.reset);
        },
        load:function(map) {
          self.pop.load(map);
        },
        on:function(pos, par) {
          self.pos = pos;
          self.par = par;
          self.setLeft(pos.left).setTop(pos.top).setHeight(pos.height).show();
          self.pop.setLeft(pos.left + 20);
        },
        off:function() {
          self.reset();
          if (self.pop.previewing) {
            pause(1, function() {
              if (! self.triggered) 
                self.pop.off(); 
            })
          }
        },
        //
        reset:function() {
          self.par = null;
          self.triggered = null;
          self.hide();
          return self;
        },
        trigger:function(par) {
          var pid = par.parId;
          if (self.triggered != pid) {
            self.triggered = pid;
            pause(0.3, function() {
              if (self.triggered == pid)
                self.pop.on(self.pos, par);
            })
          }
        },
        onmouseover:function() {
          if (self.par && self.triggered != self.par.parId)
            self.trigger(self.par);
        },
        onmouseout:function() {
          self.off();
        },
        onclick:function() {
          if (self.pop.previewing) {
            self.off();
          } else {
            self.triggered = self.par.id;
            self.pop.on(self.pos, self.par);
          }
        },
        pop_onshow:function() {
          self.addClass('previewing');
        },
        pop_onhide:function() {
          self.removeClass('previewing');
        },
        pop_onmouseover:function() {
          if (! self.triggered && self.pop.previewing) {
            self.triggered = self.pop.previewing;
            self.on(self.pop.triggeredPos, self.pop.par);
          }
        },
        pop_onreqinsert:function(pid) {
          self.reset();
          requestPar(pid);
        }
      }
    })
  },
  Pop:{
    create:function() {
      var My = this;
      return Html.Div.create('ParPreviewPop').extend(function(self) {
        return {
          onshow:function() {},
          onhide:function() {},
          onreqinsert:function(pid) {},
          onreqclose:function() {},
          //
          init:function() {
            Html.Window.append(self.setTop(10).hide());
            self.head = Html.H1.create();
            self.closer = Html.Anchor.create('pop-close').bubble('onclick', self.off);
            var table = Html.Table2Col.create(self, self.head, self.closer);
            table.right.addClass('closer');
            self.browser = My.BrowserTile.create(self).bubble('onreqinsert', self.browser_onreqinsert).bubble('onreqclose', self.browser_onreqclose);
          },
          load:function(map) {
            self.browser.loadMap(map);
          },
          on:function(pos, par) {
            self.triggeredPos = pos;
            self.par = par;
            self.previewing = par.parId;
            self.head.setText(par.desc);
            self.browser.load(par);
            self.show();
            self.onshow();
          },
          off:function() {
            self.previewing = null;
            self.hide();
            self.onhide();
          },
          //
          onmouseout:function() {
            //self.off();
          },
          browser_onreqinsert:function(pid) {
            self.off();
            self.onreqinsert(pid);
          },
          browser_onreqclose:function(pid) {
            self.off();
            self.onreqclose();
          }
        }
      })
    },
    BrowserTile:{
      create:function(container) {
        var My = this;
        return Html.Tile.create(container, 'BrowserTile').extend(function(self) {
          return {
            onreqinsert:function(pid) {},
            onreqclose:function() {},
            //
            init:function() {
              self.links = My.Links.create().bubble('onpreview', self.links_onpreview).bubble('oncinfo', self.links_oncinfo);
              var bc = Html.Div.create();
              self.browser = Html.Div.create('browser par-preview').into(bc);
              self.cmd = Html.CmdBar.create(bc).add('Insert into Document', self.insert_onclick).button('Close', self.close_onclick);
              Html.Table2Col.create(self, self.links, bc);
              self.browser.setHeight(Html.Window.getViewportDim().height - 200);
            },
            loadMap:function(map) {
              self.map = map;
            },
            load:function(par) {
              self.browser.clean();
              self.par = par;
              self.links.load(par);
            },
            drawPreview:function() {
              self.browser.html(self.par.html);
            },
            drawCinfo:function(cinfo) {
              self.browser.html(cinfo.text);
            },
            links_onpreview:function() {
              var par = self.par;
              if (par.html) {
                self.drawPreview();
              } else {
                self.browser.spin(true);
                Ajax.JTemplates.preview(par.parId, self.map, function(view) {
                  self.browser.spin();
                  par.html = view.html;
                  self.drawPreview();
                })
              } 
            },
            links_oncinfo:function(cinfoId) {
              self.browser.spin(true);
              Ajax.JTemplates.cinfo(cinfoId, function(cinfo) {
                self.browser.spin();
                self.drawCinfo(cinfo);
              })
            },
            insert_onclick:function() {
              self.onreqinsert(self.par.parId);
            },
            close_onclick:function() {
              self.onreqclose();
            }
          }
        })
      },
      Links:{
        create:function() {
          var My = this;
          return Html.UlFilter.create().extend(My, function(self) {
            return {
              onpreview:function() {},
              oncinfo:function(cinfoId) {},
              //
              load:function(par) {
                self.reset();
                self.add('', 'Preview');
                Array.forEach(par.Cinfos, function(cinfo) {
                  self.add(cinfo.name, cinfo.name).set('cinfoId', cinfo.cinfoId);
                })
                self.select('');
              },
              onselect:function() {
                var a = self.selected;
                if (a.cinfoId)
                  self.oncinfo(a.cinfoId);
                else
                  self.onpreview();
              }
            }
          })
        }
      }
    }
  }
}