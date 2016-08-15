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
                Ajax.Templates.preview(par.parId, self.map, function(view) {
                  self.browser.spin();
                  par.html = view.html;
                  self.drawPreview();
                })
              } 
            },
            links_oncinfo:function(cinfoId) {
              self.browser.spin(true);
              Ajax.Templates.cinfo(cinfoId, function(cinfo) {
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
              onselect:function(a) {
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