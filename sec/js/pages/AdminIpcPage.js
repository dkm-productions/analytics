/**
 * Admin IProc Page
 * @author Warren Hornsby
 */
AdminIpcPage = {
  //
  load:function(query) {
    Page.setEvents();
    IpcAdmin.create(_$('ipc-list'));
  }
};
/** 
 * Tile IpcAdmin
 *   TableLoader table
 *   CmdBar cmd
 */
IpcAdmin = {
  create:function(parent, callback) {
    parent.clean();
    var self = Html.Tile.create(parent);
    return self.aug({
      init:function() {
        self.table = IpcAdmin.Table.create(self).bubble('onselect', self.table_onselect);
        self.cmd = Html.CmdBar.create(self)
          .button('Copy To/From...', self.copy_onclick, 'copy-note')
          .del(self.del_onclick, 'Delete Checked...')
          .add('New IPC Code...', self.add_onclick);
      },
      add_onclick:function() {
        IpcEntryPop.pop(null, self.refresh);
      },
      del_onclick:function() {
        var checks = self.table.getCheckValues();
        if (checks.length) {
          Pop.Confirm.showDeleteChecked("delete", function(confirm) {
            if (confirm) {
              Ajax.post('AdminIpc', 'deleteMany', checks, function() {
                self.refresh(checks);
              });
            }
          });  
        } else {
          Pop.Msg.showCritical('Nothing was selected.');
        }
      },
      copy_onclick:function() {
        var checks = self.table.getCheckValues();
        TemplatePicker.pop(3, function(ids) {
          if (ids.length > 0) {
            var qid = ids[0];
            Ajax.get('AdminIpc', 'getQuestion', qid, function(rec) {
              if (checks.length == 0) {
                self.table.setChecksFromOptions(rec);
              } else {
                var text = 'Are you sure you want to replace the options for this question?<br><br>' + rec.uid + '<br>Par <b>' + rec.Par.uid + '</b> effective: ' + rec.Par.dateEffective;
                Pop.Confirm.showYesNo(text, function() {
                  overlayWorking(true);
                  Ajax.post('AdminIpc', 'copyOptions', {'ids':checks,'qid':qid}, function() {
                    self.table.resetChecks();
                    overlayWorking(false);
                  });
                });
              }
            });
          }
        });
      },
      table_onselect:function(rec) {
        IpcEntryPop.pop(rec, self.refresh);
      },
      refresh:function(update) {
        var next = self.table.load(update);
        if (next && next.selector) 
          next.selector.click();
      }
    });
  },
  Table:{
    create:function(parent, augs) {
      var self = Html.TableLoader.create(parent);
      return self.aug({
        init:function() {
          self.setHeight(540);
          self.thead().tr('fixed head').th(null, 'check').th('Name').w('20%').th('IPC').w('10%').th('Desc').w('40%').th('Alt ID').w('15%').th('Category').w('15%');
          self.setTopFilter();
          self.load();
        },
        filter:function(rec) {
          return {'Category':C_Ipc.CATS[rec.cat]};
        },
        rowKey:function(rec) {
          return rec.ipc; 
        },
        load:function(update) {
          if (Array.is(update)) {
            self.loader().removeTrs(update);
          } else if (update && self.loader().getRowByKey(update.ipc)) {
            self.add(update);
            var tr = self.loader().getRowByKey(update.ipc)
            Html.Animator.fade(tr);
            return tr.nextSibling; 
          } else {
            self.reset();
            var scrollTo = null;
            self.working(true);
            Ajax.get('AdminIpc', 'getAll', null, function(recs) {
              Array.forEach(recs, function(rec) {
                self.add(rec);
                if (update && update.ipc == rec.ipc) 
                  scrollTo = self.loader().tr;
              });
              self.setTopFilter();
              if (scrollTo) 
                Html.Animator.fade(self.loader().scrollToTr(scrollTo));
              self.working(false);
            }); 
          }
        },
        add:function(rec) {
          var cat = C_Ipc.CATS[rec.cat];
          var code = self.uiCodes(rec);
          //var code = (rec.code) ? rec.code + ' (' + rec.codeSystem + ')' : null;
          self.tbody().tr(rec).check().edit(rec.name).td(rec.ipc).td(rec.desc).td(code).td(cat);
        },
        setChecksFromOptions:function(q) {
          var ids = Array.from(q.Options, 'cptCode');
          self.setChecks(ids);
        },
        uiCodes:function(rec) {
          var a = [];
          self.uiCode(a, rec.codeSnomed, 'S');
          self.uiCode(a, rec.codeLoinc, 'L');
          self.uiCode(a, rec.codeCpt, 'C4');
          return a.join(', ');
        },
        uiCode:function(a, value, system) {
          if (value) 
            a.push(value + ' (' + system + ')');
        }
      }).aug(augs);
    }
  }
}
/**
 * RecordEntryDeletePop IpcEntry
 */
IpcEntryPop = {
  pop:function(rec, callback) {
    IpcEntryPop = this.create(callback).pop(rec);
  },
  create:function(callback) {
    var self = Html.RecordEntryDeletePop.create('IPC Entry');
    return self.aug({
      onload:function() {
        if (self.rec && self.rec.cat == C_Ipc.CAT_LAB)
          self.rec.codeSystem = C_Ipc.CS_LOINC;
      },
      onshow:function(rec) {
        if (rec == null)  
          self.form.focus('ipc');
        else
          self.form.focus((rec.name) ? 'codeSnomed' : 'name');
      },
      onsave:function(rec) {
        callback(rec);
      },
      ondelete:function(id) {
        callback([id]);
      },
      buildForm:function(ef) {
        ef.line().lbl('IPC').textbox('ipc', 5).lbl('Category').select('cat', C_Ipc.CATS, '');
        ef.line('mt10').lbl('Name').textbox('name', 40, 40);
        ef.line().lbl('Desc').textarea('desc', 6);
        ef.line('mt10').lbl('SNOMED').textbox('codeSnomed', 20);
        ef.line().lbl('CPT').textbox('codeCpt', 20);
        ef.line().lbl('LOINC').textbox('codeLoinc', 20);
          //.lbl('System').select('codeSystem', C_Ipc.CODE_SYSTEMS, '');
      },
      save:function(rec, callback_rec) {
        Ajax.post('AdminIpc', 'save', rec, callback_rec);
      },
      remove:function(rec, callback_id) {
        Ajax.get('AdminIpc', 'delete', rec.ipc, callback_id);
      }
    })
  }
}  
/**
 * InputHidden TemplatePicker
 */
TemplatePicker = {
  //
  pop:function(level, callback, tid, sid, pid, qid) {
    TemplatePicker = this.create().pop(level, callback, tid, sid, pid, qid);
  },
  create:function() {
    var self = Html.InputHidden.create().setId('pb_value');
    Html.Window.append(self);
    return self.aug({
      /*
       * @arg int level 2=par,3=question,4=option
       * @callback([id,..])
       */
      pop:function(level, callback, tid, sid, pid, qid) {
        self.clean();
        args = {'l':level,'tid':tid||1,'sid':sid||13,'pid':pid,'qid':qid,'fld':self.id,'delim':','};
        Page.pop('popBuilder.php', args, null, 'PopBuilder', function() {
          if (self.value)
            callback(Json.decode(self.value));
        });
        return self;
      }
    });
  }
}
/**
 * Assign global instance
 */
var page = AdminIpcPage;  
