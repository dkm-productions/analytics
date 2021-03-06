/**
 * Tile ReportStubView
 */
ReportStubView = {
  create:function(container) {
    var My = this;
    return Html.Tile.create(container, 'ReportStubView').extend(function(self) {
      return {
        onselect:function(stub) {},
        oncreate:function(report) {},
        //
        init:function() {
          self.table = My.Table.create(self).bubble('onselect', self);
          self.cb = Html.CmdBar.create(self).add('New Report...', self.add_onclick);
          self._pad = self.cb.height();
          self.load();
        },
        load:function() {
          self.table.load();
        },
        setMaxHeight:function(i) {
          self.table.setMaxHeight(i - self._pad);
        },
        //
        add_onclick:function() {
          NewReportTypePop.pop(self.newReport);
        },
        newReport:function(tableId) {
          self.working(function() {
            Ajax.Reporting.newReport(tableId, function(report) {
              self.working(false);
              self.oncreate(report);
            })
          })
        }
      }
    })
  },
  Table:{
    create:function(container) {
      return Html.TableLoader.create(container, 'fsy grid').extend(function(self) {
        return {
          onselect:function(report) {},
          //
          init:function() {
            self.thead().trFixed().th('Type').w('10%').th('Name').w('25%').th('Description').w('65%');
          },
          //
          rowKey:function(rec) {
            return rec.reportId; 
          },
          rowBreaks:function(rec) {
            return [rec._table];
          },
          fetch:function(callback_recs) {
            Ajax.Reporting.getStubs(callback_recs);
          },
          add:function(report, tr) {
            tr.td(report._table, 'fs').select(Html.AnchorAction.create('Report', report.name)).td(report.comment, 'comment');
          },
          setMaxHeight:function(i) {
            self.setHeight(i);
          }
        }
      })
    }
  }
}
NewReportTypePop = {
  /*
   * @callback(tableId)
   */
  pop:function(callback) {
    var types = Map.invert(Map.extract(RepCritRec.TABLES, RepCritRec.CREATABLE));
    Question.asDummy('Report Type', Map.keys(types)).pop(function(type) {
      callback(types[type]);
    })
  }
}
/**
 * Tile ReportView
 *   Tile header
 *   ReportTable table
 */
ReportView = {
  create:function(container) {
    var My = this;
    return Html.Tile.create(container, 'ReportView').extend(function(self) {
      return {
        onedit:function(report) {},
        onexit:function() {},
        //
        init:function() {
          self.header = My.Header.create(self).bubble('onedit', self).bubble('onexit', self);
          self.table = ReportTablePanels.create(self);
          self._pad = self.header.getHeight();
        },
        loadFromStub:function(stub) {
          self.reset();
          self.working(function() {
            Ajax.Reporting.getReport(stub.reportId, self.load);
          })
        },
        setMaxHeight:function(i) {
          self.table.setMaxHeight(i - self._pad);
        },
        reset:function() {
          self.table.reset();
          self.header.reset();
        },
        /*
         * @arg ReportCriteria report
         */
        load:function(report) {
          self.reset();
          self.working(false);
          self.report = report;
          self.header.load(report);
          self.table.load(report);
        }
      }
    })
  },
  Header:{
    create:function(container) {
      return Html.Tile.create(container, 'ReportViewHeader').extend(function(self) {
        return {
          onedit:function(report) {},
          onexit:function() {},
          //
          init:function() {
            var t = Html.Table2Col.create(self);
            self.h2 = Html.H2.create('Report').into(t.left).nbsp();
            self.comment = Html.Div.create().into(t.left).setText('Description');
            self.custom = Html.AnchorAction.asCustom('Configure this report').into(t.right).bubble('onclick', self.custom_onclick);
            self.exit = Html.AnchorAction.create('back', 'Return to list').into(t.right).bubble('onclick', self, 'onexit');
          },
          reset:function() {
            self.invisible();
          },
          load:function(report) {
            self.report = report;
            self.h2.setText(report.name);
            self.comment.setText(self.formatComment(report));
            self.visible();
          },
          //
          formatComment:function(report) {
            return report._tableName + ': ' + report.comment; 
            return s;
          }, 
          custom_onclick:function() {
            self.onedit(self.report);
          }
        }
      })
    }
  }
}
/**
 * Tile ReportCriteriaView 
 *   ReportTree tree
 *   ReportTable table
 */
ReportCriteriaView = {
  create:function(container) {
    return Html.Tile.create(container, 'ReportCriteriaView').extend(function(self) {
      return {
        onexit:function() {},
        //
        init:function() {
          self.tree = ReportTree.create(self).bubble('onresize', self.tree_resize).bubble('ondelete', self.exit_onclick);
          self.cb = Html.CmdBar.create(self).button('Generate Report', self.generate_onclick, 'report').exit(self.exit_onclick).container();
          self.tile = Html.Tile.create(self, 'mt10');
          self.table = ReportTablePanels.create(self.tile).hide();
        },
        /*
         * @arg ReportCriteria report
         */
        load:function(report) {
          if (report == null) 
            self.working(function() {
              Ajax.Reporting.newReport(self._load);
            })
          else
            self._load(report);
        },
        _load:function(report) {
          self.working(false);
          self.report = report;
          self.tree.load(report);
          self.table.hide();
        },
        //
        generate_onclick:function() {
          self.table.show().load(self.report);
        },
        exit_onclick:function() {
          self.onexit();
        },
        resize:function(pad) {
        },
        tree_resize:function(expanded) {
          Html.Window.flickerFixedRows();
        }
      }
    })
  }
}
/**
 * Ul ReportTree
 */
ReportTree = {
  create:function(container) {
    var My = this;
    return Html.Ul.create().into(container).extend(function(self) {
      return {
        onresize:function() {},
        ondelete:function(id) {},
        //
        load:function(report) {
          self.report = report;
          self.clean();
          self.items = [];
          self.add(0, My.ReportAnchor.create(report).bubble('onpop', self.report_onpop).bubble('onsave', self.report_onsave).bubble('ondelete', self));
          self.add(1, My.Rec.create(report.Rec).bubble('onupdate', self.onupdate));
          Array.forEach(report.Rec.Joins, function(join) {
            self.add(1, My.JoinAnchor.create(report, join).bubble('onupdate', self.onupdate));
            Array.forEach(join.Recs, function(rec) {
              self.add(2, My.Rec.create(rec).bubble('onupdate', self.onupdate).bubble('ondelete', function(rec){self.rec_ondelete(rec, join)}));
            });
            self.add(2, My.AnotherJoinRecAnchor.create(join).bubble('onupdate', self.onupdate));
          });
          if (! Array.isEmpty(report.Rec.JOINS_TO))
            self.add(1, My.JoinAnchor.create(report).bubble('onupdate', self.onupdate));
          self.onresize();
        },
        expand:function() {
          self.items.forEach(function(li) {
            li.show();
          })
          self.onresize(true);
        },
        contract:function() {
          self.items.forEach(function(li) {
            li.hide();
          })
          self.onresize(false);
        },
        //
        add:function(level, e) {
          var li = self.li('l' + level).add(e);
          if (level > 0)
            self.items.push(li);
        },
        onupdate:function() {
          self.load(self.report);
        },
        rec_ondelete:function(rec, join) {
          join.drop(rec);
          self.onupdate();
        },
        report_onpop:function() {
          self.expand();
        },
        report_onsave:function(report) {
          self.load(report);
        }
      }
    })
  },
  ReportAnchor:{
    /*
     * @arg ReportCriteria report
     */
    create:function(report) {
      return Html.Span.create().extend(function(self) {
        return {
          onpop:function() {},
          onsave:function(report) {},
          ondelete:function() {},
          //
          init:function() {
            self.reportAnchor = Html.AnchorAction.create('Report', report.name).into(self).bubble('onclick', self.pop);
            self.commentAnchor = Html.Anchor.create('ml5', report.comment).into(self).bubble('onclick', self.pop.bind(self, 'comment'));
            if (report.reportId == null && ! report._firstpop) 
              report._firstpop = self.pop();
          },
          pop:function(focusId) {
            self.onpop();
            return ReportEntryPop.pop(report, focusId).bubble('onsave', self).bubble('ondelete', self);
          }
        }
      })
    }
  },
  AnotherJoinRecAnchor:{
    /*
     * @arg RepCritJoin join
     */
    create:function(join) {
      return Html.AnchorAction.create('red', 'Another...').extend(function(self) {
        return {
          onupdate:function() {},
          //
          onclick:function() {
            Ajax.Reporting.getJoin(join.table, function(j) {
              overlayWorking(false);
              join.add(RepCritRec.revive(j.Recs[0]));
              self.onupdate();
            })
          }
        }
      })
    }
  },
  JoinAnchor:{
    /*
     * @arg ReportCriteria report
     * @arg RepCritJoin join (optional)
     */
    create:function(report, join) {
      var text = (join) ? join.JTS[join.jt] : 'add...';
      var cls = (join) ? 'Join' : 'Join red';
      return Html.AnchorAction.create(cls, text).extend(function(self) {
        return {
          onupdate:function() {},
          //
          onclick:function() {
            if (join)
              JoinTypePop.pop(join, self.onupdate);
            else
              NewJoinPop.pop(report, self.onupdate);
          }
        }
      })
    }
  },
  Rec:{
    /*
     * @arg RepCritRec rec
     */
    create:function(rec) {
      return Html.Span.create().extend(function(self) {
        return {
          onupdate:function() {},
          ondelete:function(rec) {},
          //
          init:function() {
            Html.Span.create('Record', rec._name).into(self);
            Html.Anchor.create('Summary', rec.summary(), self.anchor_onclick).into(self);
          },
          anchor_onclick:function() {
            if (rec.pid_ && rec._prototype.pi == null) {
              Page.work(function() {
                Ajax.Templates.getParInfo(rec.pid_, function(pi) {
                  Page.work(false);
                  x.x
                  rec._prototype.pi = pi;
                  self.pop();
                })
              })
            } else {
              self.pop();
            }
          },
          //
          pop:function() {
            CritRecEntryPop.pop(rec).bubble('onsave', self.pop_onsave).bubble('ondelete', self.pop_ondelete);
          },
          pop_onsave:function(update) {
            rec = update;
            self.onupdate();
          },
          pop_ondelete:function() {
            self.ondelete(rec);
          }
        }
      })
    }
  }
}
/**
 * Panels ReportTablePanels
 */
ReportTablePanels = {
  create:function(container) {
    var panels = {
      Client:ClientReportTable, 
      Audit:AuditReportTable};
    return Html.Panels.create(container, panels).extend(function(self) {
      return {
        load:function(report) {
          if (report.isTable(RepCritRec.T_AUDITS)) 
            self.Panels.Audit.select();
          else 
            self.Panels.Client.select();
          self.selected.load(report);
        }
      }
    })
  }
}
/**
 * TableLoader ReportTable
 */
ReportTable = {
  create:function(container) {
    var My = this;
    return Html.TableLoader.create(container, 'fsb').extend(My, function(self, parent) {
      return {
        init:function() {
          self.setHeight(350);
          self.stats = Html.Tile.create(container, 'Stats').nbsp();
          self._pad = self.stats.getHeight();
        },
        load:function(report) {
          self.report = report;
          parent(Html.TableLoader).load(report);
        },
        hide:function() {
          self.stats.hide();
          parent(Html.ScrollTable).hide();
          return self;
        },
        show:function() {
          self.stats.show();
          parent(Html.ScrollTable).show();
          return self;
        },
        setMaxHeight:function(i) {
          self.setHeight(i - self._pad, 100);
        },
        //
        fetch:function(callback_recs) {
          Ajax.Reporting.generate(self.report, callback_recs);
        },
        reset:function() {
          self.thead().clean();
          parent(Html.TableLoader).reset();
        },
        setStats:function(recs) {
          self.stats.setText('Total record(s) returned: ' + recs.length);
        }
      }
    })
  }
}
/**
 * ReportTable ClientReportTable
 */
ClientReportTable = {
  create:function(container) {
    return ReportTable.create(container, 'fsb').extend(this, function(self, parent) {
      return {
        onload:function(recs) {
          var tr = self.thead().trFixed().th(self.report.Rec._name);
          var w = String.percent(80 / recs.joinCt);
          recs.joinTables.forEach(function(table) {
            tr.th(table).w(w);
          })
          self.setStats(recs);
        },
        add:function(rec, tr) {
          tr.td(AnchorClient_Facesheet.create(rec));
          rec.getJoinDatas().forEach(function(j) {
            tr.td().html(j.labels.join('<br>'));
          })
        }
      }
    })
  }
}
/**
 * ReportTable AuditReportTable
 */
AuditReportTable = {
  create:function(container) {
    var My = this;
    return ReportTable.create(container, 'fsb').extend(this, function(self, parent) {
      return {
        onload:function(recs) {
          var tr = self.thead().trFixed().th('Audit Record').th('Date').th('User').th('Patient');
          var w = String.percent(60 / recs.joinCt);
          recs.joinTables.forEach(function(table) {
            tr.th(table).w(w);
          })
          self.setStats(recs);
        },
        add:function(rec, tr) {
          tr.select(My.AnchorAudit.create(rec)).td(rec.date).td(rec.User.name).td(AnchorClient_Facesheet.create(rec.Client));
          rec.getJoinDatas().forEach(function(j) {
            tr.td().html(j.labels.join('<br>'));
          })
        },
        onselect:function(rec) {
          AuditViewer.pop(self.recs, rec);
        }
      }
    })
  },
  AnchorAudit:{
    create:function(rec, text) {
      text = text || rec._label;
      var a;
      switch (rec.action) {
        case AuditRec.ACTION_REVIEW:
          a = Html.AnchorAction.asView(text);
        case AuditRec.ACTION_DELETE:
          a = Html.AnchorAction.asDelete(text);
        case AuditRec.ACTION_PRINT:
          a = Html.AnchorAction.asPrint(text);
        case AuditRec.ACTION_BREAKGLASS:
          a = Html.AnchorAction.asWarning(text);
        default:
          a = Html.AnchorAction.asUpdate(text);
      }
      if (rec._altered) {
        a.addClass('red');
      }
      return a;
    }
  }
}
/**
 * Pop AuditViewer
 */
AuditViewer = {
  pop:function(recs, rec) {
    return this.create().pop(recs, rec);
  },
  create:function() {
    var My = this;
    return AuditViewer = Html.Pop.create('Audit Viewer', 800).extend(function(self) {
      return {
        init:function() {
          self.navbar = My.NavBar.create(self.content).bubble('onselect', self.navbar_onselect);
          self.viewer = My.Viewer.create(self.content);
        },
        onshow:function(recs, rec) {
          self.navbar.load(recs, rec, My.AnchorAudit, true);
        },
        navbar_onselect:function(rec) {
          self.viewer.load(rec);
        }
      }
    })
  },
  NavBar:{
    create:function(container) {
      var My = this;
      return Html.NavBar.create(container).extend(function(self) {
        return {
          init:function() {
            self.onbox.content.hide();
            self.recbox = My.RecBox.create(self.onbox);
          },
          ondraw_load:function(rec, header, content) {
            header.html(rec.Client.name + '<br>' + rec._label);
            self.recbox.load(rec); 
          },
          getContent:function(rec) {
            var a = [];
            a.push(rec.recName + ' ID #' + rec.recId);
            a.push(rec.ACTIONS[rec.action] + ' ' + rec.date + ' by ' + rec.User.name);
            return a.join('<br>');
          }
        }
      })
    },
    RecBox:{
      create:function(container) {
        return Html.Tile.create(container, 'RecBox').extend(function(self) {
          return {
            init:function() {
              var ef = self.form = Html.EntryForm.create(self);
              ef.li('Record ID', null, 'nopad').ro('recId').lbl('User').ro('_by').lbl('Date').ro('date');
            },
            load:function(rec) {
              self.form.setFields(rec, true);
            }
          }
        })
      }
    }
  },
  AnchorAudit:{
    create:function(rec, onclick) {
      return Html.AnchorRec.from(AuditReportTable.AnchorAudit.create(rec, rec.recName), rec, onclick);
    }
  },
  Viewer:{
    create:function(container) {
      var My = this;
      return Html.Tile.create(container, 'AuditViewer').extend(function(self) {
        return {
          init:function() {
            var tr = Html.Table.create(self).tbody().tr();
            self.before = My.Snapshot.create(Html.Pop.Frame.create(tr.td()._cell, 'Before')),
            self.after = My.Snapshot.create(Html.Pop.Frame.create(tr.td(null, 'pl5')._cell, 'After'))
          },
          load:function(rec) {
            self.before.load(rec.before);
            self.after.load(rec.after);
          }
        }
      })
    },
    Snapshot:{
      create:function(container) {
        return Html.Tile.create(container, 'Snapshot').extend(function(self) {
          return {
            init:function() {
              self.table = Html.Table.create().into(self).tbody();
            },
            load:function(rec) {
              self.table.clean();
              var snap = rec.getSnapshot();
              if (snap) 
                for (var fid in snap) 
                  self.table.tr().th(fid + ':').td(snap[fid]);
            }
          }
        })
      }
    }
  }
}
/**
 * Join pops
 */
JoinTypePop = {
  /*
   * @arg RepCritJoin join
   * @arg fn() onupdate
   */
  pop:function(join, onupdate) {
    var jts = Map.invert(Map.extract(join.JTS, join.allowable()));
    Question.asDummy('Join Type', Map.keys(jts), join.JTS[join.jt]).popToggle(function(key) {
      join.update(jts[key]);
      onupdate();
    })
  }
}
NewJoinPop = {
  /*
   * @arg ReportCriteria report
   * @arg fn() onupdate
   */
  pop:function(report, onupdate) {
    var tables = Map.invert(Map.extract(RepCritRec.TABLES, report.Rec.JOINS_TO));
    Question.asDummy('Join Table', Map.keys(tables)).pop(function(key) {
      overlayWorking(true);
      Ajax.Reporting.getJoin(tables[key], function(join) {
        overlayWorking(false);
        report.addJoin(join);
        onupdate();
      })
    })
  }
}
/**
 * RecordEntryPop ReportEntryPop
 */
ReportEntryPop = {
  /*
   * @arg RepCritRec rec 
   * @arg string focusId (optional)
   */
  pop:function(rec, focusId) {
    return this.create().pop(rec, focusId);
  },
  create:function() {
    return ReportEntryPop = Html.RecordEntryDeletePop.create('Report Entry', 500).extend(function(self) {
      return {
        onsave:function(report) {},
        ondelete:function(id) {},
        //
        isDeletable:function(rec) {
          return rec.reportId != null;
        },
        buildForm:function(ef, rec) {
          ef.li('Name').textbox('name');
          ef.li('Description').textarea('comment');
        },
        save:function(rec, onsuccess, onerror) {
          rec = self.rec.aug(rec);
          Ajax.Reporting.save(rec, onsuccess, onerror);
        },
        remove:function(rec, onsuccess) {
          Ajax.Reporting.deleteReport(rec.reportId, onsuccess);
        },
        getDeleteNoun:function() {
          return 'report';
        }
      }
    })
  }
} 
/**
 * Pop CritRecEntryPop 
 */ 
CritRecEntryPop = {
  /*
   * @arg RepCritRec rec 
   */
  pop:function(rec) {
    return this.create().pop(rec);
  },
  create:function() {
    return CritRecEntryPop = Html.Pop.create('Entry', 500).extend(function(self) {
      return {
        onsave:function(rec) {},
        ondelete:function() {},
        //
        init:function() {
          self.form = CritRecForm.create(self.content).bubble('onchange', self.form_onchange);
          self.cb = Html.CmdBar.create(self.content).save(self.save_onclick).del(self.del_onclick).cancel(self.close);
        },
        pop:function(rec) {
          self.setCaption(rec._name + ' Criteria');
          self.dirty = false;
          self.form.load(rec);
          self.showPosCursor();
          self.cb.showDelIf(rec._name != 'Patients');
          return self;
        },
        //
        isDirty:function() {
          return self.dirty;
        },
        form_onchange:function() {
          self.dirty = true;
        },
        close:function(saved) {
          if (saved) 
            Pop.close(true);
          else
            Pop.Confirm.closeCheckDirty(self, self.save_onclick);
        },
        save_onclick:function() {
          self.onsave(self.form.getRec());
          self.close(true);
        },
        del_onclick:function() {
          Pop.Confirm.showDelete('criteria', function() {
            self.ondelete();
            self.close(true);
          })
        }
      }
    })
  }
} 
/**
 * Tile CritRecForm
 */
CritRecForm = {
  create:function(container) {
    var My = this;
    return Html.Tile.create(container, 'CritRecEntry').extend(function(self) {
      return {
        onchange:function() {},
        //
        init:function() {
          self.table = Html.Table.create().into(self).tbody();
        },
        /*
         * @arg RepCritRec rec
         */
        load:function(rec) {
          self.rec = rec;
          self.table.clean();
          self.entries = [];
          var i = 0;
          rec.forEachValue(function(cv) {
            self.entries.push(My.CritValueEntry.create(self.table.tr(), cv).bubble('onchange', self));
          })
        },
        /*
         * @return RepCritRec updated record
         */
        getRec:function() {
          self.entries.forEach(function(entry) {
            self.rec.update(entry.getValue());
          }) 
          return self.rec;
        }
      }
    })
  },
  CritValueEntry:{
    /*
     * @arg TrAppender tr
     * @arg RepCritValue cv 
     */
    create:function(tr, cv) {
      var My = this;
      return Html.Span.create('CritValueEntry').extend(function(self) {
        return {
          onchange:function() {},
          //
          init:function() {
            self.tr = tr._tr(); 
            tr.td(self.label = Html.Span.create('FidLabel'), 'Label');
            self.tdOp = tr.td(self.op = Html.Select.create().bubble('onset', self.toggle).bubble('onchange', self.toggle), 'Op')._cell;
            self.tdSpans = tr.td(self.spans = My.Spans.create().bubble('onchange', self), 'Spans')._cell;
            self.load(cv);
          },
          load:function(cv) {
            self.cv = cv;
            self.op.load(Map.extract(RepCritValue.OPS, cv.getFixedOps()), '');
            self.spans.load(cv);
            self.label.setText(cv._label);
            self.op.setValue(cv.op);
            self.spans.setValue(cv.value);
            return self;
          },
          getValue:function() {
            return self.cv.update(self.op.getValue(), self.spans.getValue());
          },
          //
          toggle:function() {
            self.tr.addClassIf('sel', self.op.getValue());
            self.spans.toggle(self.op.getValue());
            self.onchange();
          }
        }
      })
    },
    Spans:{
      create:function() {
        var My = this;
        return Html.Span.create().extend(function(self) {
          return {
            onchange:function() {},
            //
            init:function() {
              self.spanSingle = My.SpanSingle.create(self).bubble('onchange', self);
              self.spanBetween = My.SpanBetween.create(self).bubble('onchange', self);
              self.spanSelect = My.SpanSelect.create(self).bubble('onchange', self);
              self.spanPicker = My.SpanPicker.create(self).bubble('onchange', self);
              self.spanRecPicker = My.SpanRecPicker.create(self).bubble('onchange', self);
              self.hide();
            },
            /*
             * @arg RepCritValue cv
             */
            load:function(cv) {
              self.cv = cv;
              if (cv.getFixedValues) {
                self.spanSelect.load(cv);
                self.showDefault = self.showSelect;
              } else if (cv.getRecPicker) {
                self.spanRecPicker.load(cv);
                self.showDefault = self.showRecPicker;
              } else if (cv.getPicker) {
                self.spanPicker.load(cv);
                self.showDefault = self.showPicker;
              } else { 
                self.showDefault = self.showSingle;
              }
            },
            toggle:function(op) {
              switch (op) {
                case '':
                  self.hide();
                  break;
                case RepCritValue.OP_BETWEEN:
                case RepCritValue.OP_AGERANGE:
                  self.showBetween();
                  break;
                case RepCritValue.OP_NULL:
                case RepCritValue.OP_NOT_NULL:
                  self.hide();
                  break;
                default:
                  self.showDefault();
              }
            },
            hide:function() {
              self.spanSingle.hide();
              self.spanBetween.hide();
              self.spanSelect.hide();
              self.spanRecPicker.hide();
              self.spanPicker.hide();
              self.span = null;
            },
            showSingle:function() {
              self.spanBetween.hide();
              self.spanSelect.hide();
              self.spanRecPicker.hide();
              self.spanPicker.hide();
              self.span = self.spanSingle.show();
            },
            showBetween:function() {
              self.spanSingle.hide();
              self.spanSelect.hide();
              self.spanRecPicker.hide();
              self.spanPicker.hide();
              self.span = self.spanBetween.show();
            },
            showSelect:function() {
              self.spanSingle.hide();
              self.spanBetween.hide();
              self.spanRecPicker.hide();
              self.spanPicker.hide();
              self.span = self.spanSelect.show();
            },
            showPicker:function() {
              self.spanSingle.hide();
              self.spanBetween.hide();
              self.spanSelect.hide();
              self.spanRecPicker.hide();
              self.span = self.spanPicker.show();
            },
            showRecPicker:function() {
              self.spanSingle.hide();
              self.spanBetween.hide();
              self.spanSelect.hide();
              self.spanPicker.hide();
              self.span = self.spanRecPicker.show();
            },
            setValue:function(value) {
              if (self.span)
                self.span.setValue(value);
            },
            getValue:function() {
              if (self.span)
                return self.span.getValue();
            }
          }
        })  
      },
      SpanSingle:{
        create:function(container) {
          return Html.Span.create().into(container).extend(function(self) {
            return {
              onchange:function() {},
              //
              init:function() {
                self.input = Html.InputText.create().into(self).bubble('onchange', self);
              },
              show:function() {
                Html._proto.show.call(self);
                self.input.setFocus();
                return self;
              },
              setValue:function(value) {
                self.input.setValue(value);
              },
              getValue:function() {
                return self.input.getValue();
              }
            }
          })
        }
      },
      SpanBetween:{
        create:function(container) {
          return CritRecForm.CritValueEntry.Spans.SpanSingle.create(container).extend(function(self) {
            return {
              onchange:function() {},
              //
              init:function() {
                self.input.setSize(5);
                self.label = Html.Label.create('p5', 'and').into(self);
                self.input2 = Html.InputText.create(null).setSize(5).into(self).bubble('onchange', self);
              },
              setValue:function(value) {
                var a = value.split(',');
                self.input.setValue(a[0]);
                self.input2.setValue((a.length > 0) ? a[1] : null);
              },
              getValue:function(value) {
                var a = [self.input.getValue(), self.input2.getValue()];
                return a.join(',');
              }
            }
          })
        }
      },
      SpanSelect:{
        create:function(container) {
          return Html.Span.create().into(container).extend(function(self) {
            return {
              onchange:function() {},
              //
              load:function(cv) {
                self.cv = cv;
                self.select = Html.Select.create(cv.getFixedValues()).into(self).bubble('onchange', self.select_onchange);
                self._saveText();
                return self;
              },
              show:function() {
                Html._proto.show.call(self);
                self.select.setFocus();
                return self;
              },
              setValue:function(value) {
                self.select.setValue(value);
              },
              getValue:function() {
                return self.select.getValue();
              },
              //
              _saveText:function() {
                self.cv.text_ = self.select.getText();
              },
              select_onchange:function() {
                self._saveText();
                self.onchange();
              }
            }
          })
        }
      },
      SpanPicker:{
        create:function(container) {
          return Html.Span.create().into(container).extend(function(self) {
            return {
              onchange:function() {},
              //
              load:function(cv) {
                self.cv = cv;
                self.picker = cv.getPicker().create().into(self);
                return self;
              },
              show:function() {
                Html._proto.show.call(self);
                self.picker.setFocus();
                return self;
              },
              setValue:function(value) {
                self.picker.setValue(value);
              },
              getValue:function() {
                return self.picker.getValue();
              }
            }
          })
        }
      },
      SpanRecPicker:{
        create:function(container) {
          return CritRecForm.CritValueEntry.Spans.SpanPicker.create(container).extend(function(self) {
            return {
              onchange:function() {},
              //
              load:function(cv) {
                self.cv = cv;
                self.picker = cv.getRecPicker().create().into(self).bubble('onset', self);
                if (self.picker.load) 
                  self.picker.load();
                return self;
              },
              setValue:function(value) {
                if (self.cv._rec)
                  self.picker.set(self.cv._rec);
                else
                  self.picker.setValueText(self.cv.value, self.cv.text_);
              },
              getValue:function() {
                return self.picker.getValue();
              },
              onset:function(rec) {
                self.cv.text_ = self.picker.getText();
                self.cv._rec = rec;
              }
            }
          })
        }
      }
    }
  }
}
/**
 * Rec ReportCriteria
 *   RepCritRec Rec
 */
ReportCriteria = Object.Rec.extend({
  //
  onload:function() {
    this.Rec = RepCritRec.revive(this.Rec);
    if (this.comment == null)
      this.comment = '(No Description)';
  },
  addJoin:function(join) {
    this.Rec.Joins.add(join);
  },
  isTable:function(table) {
    return this.Rec.table_ == table;
  }
})
/**
 * Rec RepCritRec
 *   RepCritJoin[] Joins
 */
RepCritRec = Object.Rec.extend({
  //
  _name:'Record',  // @abstract
  getProto:function(json) {
    switch (json.table_) {
      case RepCritRec.T_CLIENTS:
        return RepCrit_Client;
      case RepCritRec.T_ADDRESS:
        return RepCrit_Address;
      case RepCritRec.T_DIAGNOSES:
        return RepCrit_Diagnosis;
      case RepCritRec.T_MEDS:
        return RepCrit_Med;
      case RepCritRec.T_ALLERGIES:
        return RepCrit_Allergy;
      case RepCritRec.T_PROCS:
        return RepCrit_Proc;
      case RepCritRec.T_RESULTS:
        return RepCrit_ProcResult;
      case RepCritRec.T_IMMUNS:
        return RepCrit_Immun;
      case RepCritRec.T_VITALS:
        return RepCrit_Vital;
      case RepCritRec.T_AUDITS:
        return RepCrit_Audit;
      default:
        return this;
    }
  },
  onload:function(json) {
    for (var fid in json) 
      if (fid == 'Joins') 
        this.Joins = RepCritJoins.revive(json.Joins || []);
      else if (this._isCritValueFid(fid)) 
        this[fid] = this._getCritValueProto(fid).revive(json[fid]).setFidLabel(fid, this._getFidLabel(fid));
  },
  /*
   * @arg fn(RepCritValue) oneach
   */
  forEachValue:function(oneach) {
    for (var fid in this)
      if (this[fid] && this[fid]._fid)
        oneach(this[fid]);
  },
  /*
   * @arg RepCritValue cv
   */
  update:function(cv) {
    this[cv._fid] = cv;
  },
  /*
   * @arg RepCritJoin join 
   */
  dropJoin:function(join) {
    this.Joins.unset(join._i);
  },
  /*
   * @return string
   */
  summary:function() {
    var s = [];
    this.forEachValue(function(cv) {
      if (cv.hasData()) 
        s.push(cv.summary());
    })
    return (s.length == 0) ? 'Any' : s.join(' and ');
  },
  //
  _isCritValueFid:function(fid) {
    return ! fid.endsWith('_');
  },
  _getCritValueProto:function(fid) {
    return RepCritValue;  
  },
  _getFidLabel:function(fid) {
    return this._fixLabel(fid);
  },
  _fixLabel:function(fid) {
    return fid.substr(0, 1).toUpperCase() + fid.substr(1).replace(/([A-Z])/g, function($1){return " "+$1.toUpperCase()});
  }
})
/**
 * RepCritRec Subclasses
 */
RepCrit_Client = RepCritRec.extend({
  _name:'Patients',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'birth':
        return RepCritValueAge;
      case 'sex':
        return RepCritValueSex;
      case 'deceased':
        return RepCritValueBool;
      case 'ethnicity':
        return RepCritValueFixed.from(C_Client.ETHNICITIES);
      case 'race':
        return RepCritValueFixed.from(C_Client.RACES);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'uid':
        return 'Patient ID';
      case 'birth':
        return 'Age';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
})
RepCrit_Audit = RepCritRec.extend({
  _name:'Audits',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'date':
        return RepCritValuePickerDate;
      case 'clientId':
        return RepCritValueRecPicker.from(ClientSelector);
      case 'userId':
        return RepCritValueRecPicker.from(UserSelector);
      case 'action':
        return RepCritValueFixed.from(AuditRec.ACTIONS);
//      case 'recName':
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'clientId':
        return 'Patient';
      case 'userId':
        return 'User';
      case 'recName':
        return 'Record';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
}) 
RepCrit_Address = RepCritRec.extend({
  _name:'Address',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'state':
        return RepCritValueFixed.from(C_Address.STATES);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'addr1':
        return 'Address 1';
      case 'addr2':
        return 'Address 2';
      case 'phone1':
        return 'Phone';
      case 'email1':
        return 'Email';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
})
RepCrit_Diagnosis = RepCritRec.extend({
  _name:'Diagnoses',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'active':
        return RepCritValueBool;
      case 'status':
        return RepCritValueFixed.from(C_Diagnosis.STATUSES);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'icd':
        return 'ICD Code';
      case 'text':
        return 'Description';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
})
RepCrit_Med = RepCritRec.extend({
  _name:'Medications',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'active':
        return RepCritValueBool;
      default:
        return RepCritValue;
    }
  }
})
RepCrit_Allergy = RepCritRec.extend({
  _name:'Allergies',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'active':
        return RepCritValueBool;
      default:
        return RepCritValue;
    }
  }
}) 
RepCrit_Proc = RepCritRec.extend({
  _name:'Procedures',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'ipc':
        return RepCritValueRecPicker.from(IpcPicker);
      case 'date':
        return RepCritValuePickerDate;
      case 'providerId':
        return RepCritValueRecPicker.from(ProviderPicker);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'ipc':
        return 'IPC';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
}) 
RepCrit_ProcResult = RepCritRec.extend({
  _name:'Results',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'ipc':
        return RepCritValueRecPicker.from(IpcPicker);
      case 'date':
        return RepCritValuePickerDate;
      case 'value':
        return RepCritValueNumeric;
      case 'interpretCode':
        return RepCritValueFixed.from(C_ProcResult.INTERPRET_CODES);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'ipc':
        return 'IPC';
      case 'interpretCode':
        return 'Interpretation'
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
}) 
RepCrit_Immun = RepCritRec.extend({
  _name:'Immunizations',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'dateGiven':
        return RepCritValuePickerDate;
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'manufac':
        return 'Manufacturer';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
})
RepCrit_Vital = RepCritRec.extend({
  _name:'Vitals',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'date':
        return RepCritValuePickerDate;
      default:
        return RepCritValueNumeric;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'bpSystolic':
        return 'Systolic';
      case 'bpDiastolic':
        return 'Diastolic';
      case 'wt':
        return 'Weight';
      case 'hc':
        return 'Head';
      case 'wc':
        return 'Waist';
      case 'bmi':
        return 'BMI';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
}) 
/*
 * RecArray IndexedRecArray
 */
IndexedRecArray = Object.RecArray.extend({
  getItemProto:function(jsons) {},
  //
  onload:function() {
    this.reindex();
  },
  reindex:function() {
    this.forEach(function(rec, i) {
      rec._i = i;
    })
  },
  add:function(rec) {
    this.push(rec);
    this.reindex();
  },
  drop:function(rec) {
    this.unset(rec._i);
    this.reindex();
  }
})
/*
 * IndexedRecArray RepCritJoins 
 */
RepCritJoins = IndexedRecArray.extend({
  revive:function(jsons) {
    var proto = Object.create(this);  // create a new instance of proto to allow distinct onempty bubbling
    proto.itemProto = Object.create(RepCritJoin);
    return Object.RecArray.revive.call(proto, jsons, proto.itemProto);
  },
  onload:function() { 
    this.itemProto.bubble('onempty', this.join_onempty.bind(this));
    this.reindex();
  },
  add:function(json) {
    IndexedRecArray.add.call(this, this.itemProto.revive(json));
  },
  join_onempty:function(join) {
    this.drop(join);
  }
})
/**
 * Rec RepCritJoin
 *   RepCritRec[] Recs
 */
RepCritJoin = Object.Rec.extend({
  onempty:function(join) {},
  //
  onload:function() {
    RepCritRecs.revive(this.Recs);
  },
  allowable:function() {
    if (this.Recs && this.Recs.length > 1)
      return [this.JT_HAVE_ONE, this.JT_HAVE_ALL, this.JT_NOT_HAVE_ANY];
    else
      return [this.JT_HAVE, this.JT_NOT_HAVE];
  },
  update:function(jt) {
    this.jt = jt;
  },
  add:function(rec) {
    this.Recs.add(rec);
    if (this.jt == this.JT_HAVE)  
      this.jt = this.JT_HAVE_ALL;
    else if (this.jt == this.JT_NOT_HAVE)
      this.jt = this.JT_NOT_HAVE_ANY;
  },
  drop:function(rec) {
    this.Recs.drop(rec._i);
    if (this.Recs.length == 1) {
      if (this.jt == this.JT_HAVE_ALL) {  
        this.jt = this.JT_HAVE;
      } else if (this.jt == this.JT_NOT_HAVE_ANY) {
        this.jt = this.JT_NOT_HAVE;
      }
    } else if (this.Recs.length == 0) {
      this.onempty(this);
    }
  }
})
/**
 * IndexedRecArray RepCritRecs
 */
RepCritRecs = IndexedRecArray.extend({ 
  getItemProto:function() {
    return RepCritRec; 
  }
})
/**
 * Rec RepCritValue
 */
RepCritValue = Object.Rec.extend({
  //
  /*
   * @arg string fid 'clientId'
   * @arg string label 'Patient ID'
   */
  setFidLabel:function(fid, label) {
    this._fid = fid;
    this._label = label;
    return this;
  },
  /*
   * @arg string op OP_
   * @arg string value
   */
  update:function(op, value) {
    this.op = String.nullify(op);
    this.value = String.nullify(value);
    return this;
  },
  isValueless:function() {
    return this.op == RepCritValue.OP_NULL || this.op == RepCritValue.OP_NOT_NULL;  
  },
  hasData:function() {
    return this.op && (this.value || this.isValueless());
  },
  /*
   * @return string
   */
  summary:function() {
    var s = this._label.toUpperCase() + ' ' + this.OPS[this.op];
    if (! this.isValueless())
      s += ' "' + (this.text_ ? this.text_ : this.value) + '"';
    return s;
  },
  //
  getFixedOps:function() {  
    return [RepCritValue.OP_EQ, RepCritValue.OP_NEQ, RepCritValue.OP_START, RepCritValue.OP_CONTAIN, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];
  }
})
/**
 * RepCritValue Subclasses
 */
RepCritValueNumeric = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_EQ, RepCritValue.OP_NEQ, RepCritValue.OP_LTN, RepCritValue.OP_GTN, RepCritValue.OP_BETWEEN, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];  
  }
})
RepCritValueAge = RepCritValue.extend({ 
  getFixedOps:function() {
    return [RepCritValue.OP_EQ, RepCritValue.OP_NEQ, RepCritValue.OP_OLDER, RepCritValue.OP_YOUNGER, RepCritValue.OP_AGERANGE, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];
  }
})
RepCritValueFixed = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_IS, RepCritValue.OP_IS_NOT]; 
  },
  from:function(values) {
    return this.extend({
      getFixedValues:function() {
        return values;
      }
    }) 
  }
})
RepCritValueSex = RepCritValueFixed.from({'M':'Male','F':'Female'});
RepCritValueBool = RepCritValueFixed.from({'1':'Yes','0':'No'});
//
RepCritValuePicker = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_EQ, RepCritValue.OP_NEQ, RepCritValue.OP_BEFORE, RepCritValue.OP_AFTER, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];
  },
  from:function(picker) {
    return this.extend({
      getPicker:function() {
        return picker;
      }
    })
  }
})
RepCritValuePickerDate = RepCritValuePicker.from(QuestionDateEntry);
//
RepCritValueRecPicker = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_IS, RepCritValue.OP_IS_NOT];  
  },
  from:function(picker) {
    return this.extend({
      getRecPicker:function() {
        return picker;
      }
    })
  }
})
/**
 * RecArray RepRecs
 * Data records result of criteria query
 */
RepRecs = Object.RecArray.extend({
  joinCt:null,
  joinTables:null,
  //
  reviveFrom:function(report, recs) {
    if (report.isTable(RepCritRec.T_AUDITS))
      return this.revive(recs, AuditRec.create(report));
    else
      return this.revive(recs, RepRec);
  },
  onload:function() {
    this.joinTables = [];
    RepRec.joinFids = [];
    var rec = this.current();
    for (var fid in rec) { 
      if (fid.beginsWith('Join')) {
        RepRec.joinFids.push(fid);
        this.joinTables.push(rec[fid].current()._table);
      }
    }
    this.joinCt = this.joinTables.length;
  } 
})
/**
 * Rec RepRec
 */
RepRec = Object.Rec.extend({
  joinFids:null,  // ['fid',..]
  /*
   * @return [JoinData,..]
   */
  getJoinDatas:function() {
    var js = [];
    for (var i = 0; i < this.joinFids.length; i++) 
      js.push(JoinData.from(this[this.joinFids[i]]));
    return js;
  }
})
/**
 * RepRec AuditRec
 *   Snapshot before
 *   Snapshot after
 */
AuditRec = RepRec.extend({
  oncreate:function(report) {
    this.report = report;
  },
  onload:function() {
    this._label = this._label.ellips(50);
    this.Snapshot = Snapshot.create(this);
    this.before = this.Snapshot.revive(this.before);
    this.after = this.Snapshot.revive(this.after);
  }
})
/**
 * Rec Snapshot
 */
Snapshot = Object.Rec.extend({
  oncreate:function(audit) {
    this.audit = audit;
  },
  onload:function(json) {
    this.json = json;
  },
  getSnapshot:function() {
    if (this._snap == null)
      this.build();
    return this._snap;
  },
  build:function() {
    if (this.json) {
      this._snap = {};  
      var d;
      for (var fid in this.json) {
        if (this.reportable(fid)) 
          this._snap[fid] = this.decorate(fid);
      }
    }
  },
  reportable:function(fid) {
    if (fid.beginsWith('_'))
      return false;
    switch (fid) {
      case 'userGroupId':
      case 'clientId':
        return false;
    }
    return true;
  },
  decorate:function(fid) {
    var v = this.json[fid];
    var d = this.getDecorator(fid, v);
    if (fid.endsWith('Date'))
      return (d) ? d : v;
    return (d) ? v + ' (' + d + ')' : v;
  },
  getDecorator:function(fid, v) {
    switch (fid) { 
      case 'active':
      case 'asNeeded':
      case 'meals':
        return (v == '1') ? 'Yes' : 'No';
      case 'clientId':
        if (v == this.audit.clientId.value)
          return this.audit.clientId.text_;
      case 'orderBy':
      case 'schedBy':
      case 'closedBy':
      case 'userId':
        return C_Users[v];
    }
    return this.json["_" + fid];
  }
})
/**
 * JoinData
 */
JoinData = {
  table:null,
  labels:null,
  /*
   * @arg RepRecJoin repRecJoin
   * @return JoinData {'table':'Table','labels':['label'..]}
   */
  from:function(repRecJoin) {
    var rec = Object.create();
    rec.table = RepCritRec.TABLES[repRecJoin.current()._table];
    rec.labels = Array.from(repRecJoin, '_label');
    return rec;
  }
}
