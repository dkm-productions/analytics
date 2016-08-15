/**
 * Hl7Downloader
 */
Hl7Downloader = {
  create:function(printable) {
    var My = this;
    return Html.Pop.create('Clinical Care Document', 470).extend(function(self) {
      return {
        getServer:function() {},
        download:function() {},
        init:function() {
          self.frame = My.Frame.create(self.content);
          self.cb = Html.SplitCmdBar.create(self.content)
            .print(function() {self.print_onclick()})
          .split()
            .button('Download', self.download_onclick, 'download2')
            .button('Encrypt...', self.encrypt_onclick.curry(true), 'lock')
            .cancel(self.close)
        },
        onshow:function(cid) {
          self.working(true);
          self.cid = cid;
          self.cb.table.invisible();
          self.cb.right.showButtonIf('Encrypt...', true);
          self.cb.left.showButtonIf('Print', printable);
          self.getFile.apply(self, arguments);
        },
        getFile:function(cid) {
          self.getServer().get(cid, function(file) {
            self.working(false);
            self.load(file);
          })
        },
        load:function(file) {
          self.file = file;
          self.frame.load(file);
          self.cb.table.visible();
        },
        download_onclick:function() {
          self.workingClose();
          self.download();
        },
        encrypt_onclick:function() {
          PasswordPop.pop(function(password) {
            if (! String.isBlank(password)) {
              self.cb.table.invisible();
              self.working(true);
              self.getServer().encrypt(self.cid, password, function(file) {
                self.working(false);
                self.cb.right.showButtonIf('Encrypt...', false);
                self.cb.left.showButtonIf('Print', false);
                self.load(file);
              })
            }
          })
        },
        print_onclick:function() {
          Page.popCcdPrint(self.file, self.cid);
        },
        //
        working:function(value) {
          self.frame.visibleIf(! value);
          self.cb.wrapper.visibleIf(! value);
          self.content.working(value);
        },
        workingClose:function() {
          self.working(true);
          pause(1, function() {
            self.working();
            self.close();
          })
        }
      }
    })
  },
  Frame:{
    create:function(container) {
      return Html.Pop.Frame.create(container).extend(function(self) {
        return {
          init:function() {
            self.form = Html.UlEntry.create(self, function(ef) {
              ef.line().lbl('Filename').ro('filename', 'filename');
              ef.line().lbl('SHA-1 Hash').ro('hash', 'hash');
            })
          },
          load:function(file) {
            self.form.load(file);
          }
        }
      })
    }
  }
}
/**
 * CcdDownloader
 */
CcdDownloader = {
  pop:function(cid, asVisit) {
    return Html.Pop.singleton_pop.apply(this, arguments);
  },
  create:function() {
    return Hl7Downloader.create(true).extend(function(self) {
      return {
        getFile:function(cid, custmap) {
          self.visit = custmap ? 1 : 0;
          self.getServer().get(cid, custmap, function(file) {
            self.working(false);
            self.load(file);
          })
        },
        getServer:function() {
          return Ajax.Ccd;
        },
        download:function() {
          Page.Nav.goDownloadCcd(self.file, self.cid, self.visit);  
        },
        print_onclick:function() {
          Page.popCcdPrint(self.file, self.cid, self.demoOnly, self.visit);
          self.workingClose();
        },
        workingClose:function() {
          self.working(true);
          pause(1, function() {
            self.working();
            self.close();
            if (self.visit) {
              Pop.close();
            }
          })
        }
      }
    })
  }
}
DemoDownloader = {
    pop:function(cid) {
      return Html.Pop.singleton_pop.apply(this, arguments);
    },
    create:function() {
      return Hl7Downloader.create(true).extend(function(self) {
        return {
          getFile:function(cid) {
            self.getServer().get(cid, null, function(file) {
              self.working(false);
              self.load(file);
            })
          },
          getServer:function() {
            return Ajax.Ccd;
          },
          download:function() {
            Page.Nav.goDownloadCcd(self.file, self.cid, 0);
          },
          print_onclick:function() {
            Page.popCcdPrint(self.file, self.cid, true, 0);
          }
        }
      })
    }
  }
/**
 * VxuDownloader (Vaccine)
 */
VxuDownloader = {
  pop:function(cid) {
    return Html.Pop.singleton_pop.apply(this, arguments);
  },
  create:function() {
    return Hl7Downloader.create().extend(function(self) {
      return {
        getServer:function() {
          return Ajax.Vxu;
        },
        download:function() {
          Page.Nav.goDownloadVxu(self.file);            
        }
      }
    })
  }
}
/**
 * AdtDownloader (Pub Health Surveillance)
 */
AdtDownloader = {
  pop:function(cid, sessdata) {
    return Html.Pop.singleton_pop.apply(this, arguments);
  },
  create:function() {
    return Hl7Downloader.create().extend(function(self) {
      return {
        getServer:function() {
          return Ajax.Adt;
        },
        getFile:function(cid, sessdata) {
          self.getServer().get(sessdata, function(file) {
            self.working(false);
            self.load(file);
          })
        },
        download:function() {
          Page.Nav.goDownloadAdt(self.file);
        }
      }
    })
  }
}
//
PasswordPop = {
  pop:function(callback) {
    return Html.Pop.singleton_pop.apply(this, arguments);
  },
  create:function(container) {
    return Html.Pop.create('Encrypt File').extend(function(self) {
      return {
        //
        init:function() {
          self.form = Html.UlEntry.create(self.content, function(ef) {
            ef.li('Password').password('password');
            Html.CmdBar.create(self.content).ok(self.ok_onclick).cancel(self.close)
          })
          self.setOnkeypresscr(self.ok_onclick);
        },
        onshow:function(callback) {
          self.callback = callback;
          self.form.focus();
        },
        ok_onclick:function() {
          self.callback(self.form.getValue('password'));
          self.close();
        }
      }
    })
  }
}
