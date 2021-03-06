/**
 * Page PortalPage
 */
PortalPage = Html.Page.extend(function(self) {
  return {
    onbodyload:function() {},
    //
    init:function() {
      self.content = _$('page');
      self.htitle = _$('htitle');
      self.hnav = _$('hnav');
    },
    onpageload:function() {
      self.onpageresize();
      self.onbodyload();
    },
    onpageresize:function() {
      var cw = self.content.setWidth().getWidth() - 50;
      var w = Math.min(800, cw);
      self.content.setWidth(w);
      if (self._w != w) {
        self._w = w;
        if (self.htitle)
          self.htitle.setWidth(w);
        if (self.hnav)
          self.hnav.setWidth(w);
      }
    },
    goHome:function() {
      self.go('welcome.php');
    },
    goMessaging:function() {
      self.go('messaging.php');
    },
    goFacesheet:function() {
      self.go('facesheet.php');
    },
    goLogout:function() {
      self.go('index.php?logout=1');
    }
  }  
})
/**
 * Anchor ReturnAnchor
 */
ReturnAnchor = {
  create:function(container, text) {
    var a = Html.Anchor.create('return', text || 'Back to Home').into(container);
    if (text) 
      a.bubble('onclick', container, 'onreturn');
    else
      a.onclick = window.goHome;
    return a;
  }
}
/**
 * Div Curtain
 */
Curtain = {
  create:function(container) {
    return Curtain = Html.Window.append(Html.Div.create('Curtain')).extend(function(self) {
      return {
        //
        init:function() {
          self.show();
        },
        show:function() {
          if (! self.showing) {
            self.resize();
            Html._proto.show.call(self);
          }
          return self;
        },
        resize:function() {
          var dd = Html.Window.getViewportDim();
          self.setHeight(dd.height - 4).setWidth(dd.width - 4);
        },
        create:function() {
          self.show();
          return self;
        }
      }
    })
  }
}
/**
 * Div PortalPop
 */
PortalPop = {
  create:function(caption, width) {
    var My = this;
    var wrapper = Html.Window.append(Html.Div.create('PortalPop'));
    return Html.Tile.create(wrapper, 'Content').extend(function(self) {
      return {
        onshow:function() {},
        onclose:function() {},
        //
        init:function() {
          self.cap = Html.Div.create('Cap').extend(My.Cap).before(self)
            .bubble('onclose', self.close);
          self.wrapper = wrapper.setWidth(width).hide();
          self.setCaption(caption);
          wrapper.setUnselectable();
        },
        setCaption:function(text) {
          self.cap.setText(text);
        },
        load:function() {
          // @abstract
        },
        show:function() {
          self.load.apply(self, arguments);
          Curtain.create()
            .bubble('onclick', self.close)
            .bubble('ontouchend', self.close);
          Html.attach(document, 'keydown', self.onkeydown);
          self.wrapper.show().center().setTop(100);
          self.wrapper.focus();
          self.onshow();
        },
        onkeydown:function() {
          if (event && event.keyCode == 27) {
            self.close();
            Html.detach(document, 'keydown', self.onkeydown);
          }
        },
        hide:function() {
          self.wrapper.hide();
        },
        close:function() {
          Curtain.hide();
          self.hide();
          self.onclose();
        },
        create:function() {
          return self;
        }
      }
    })
  },
  Cap:function(self) {
    return {
      onclose:function() {},
      //
      init:function() {
        var table = Html.Table2Col.create(self, 
          self.cap = Html.Div.create(),
          Html.Div.create().setText('X').cursor('pointer'));
        self.close = table.right.set('onclick', function() {
          self.onclose();
        })
      },
      setText:function(text) {
        self.cap.setText(text);
      }
    }
  }
}
PopMsg = {
  create:function() {
    return PopMsg = PortalPop.create('Message', 300).extend(function(self) {
      return {
        //
        init:function() {
          self.msg = Html.Tile.create(self, 'Msg').setUnselectable();
          self.cb = Html.CmdBar.create(self).button('OK', self.close, null, 'ok');
        },
        load:function(msg) {
          self.msg.setText(msg);
        },
        onshow:function() {
          self.focused = document.activeElement;
          self.cb.get('ok').focus();
        },
        onclose:function() {
          self.focused.focus();
        }
      }
    })
  }
}