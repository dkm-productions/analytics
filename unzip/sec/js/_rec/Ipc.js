/**
 * LevelRec Ipc
 */
Ipc = Object.LevelRec.extend({
  //
  getGroupLevelProto:function() {
    return Ipc_Custom;
  },
  onload:function() {
    this._cat = C_Ipc.CATS[this.cat];
    if (this.desc == null)
      this.desc = this.name;
  }
})
Ipc_Custom = Ipc.extend({
  custom:1,
  //
  asNew:function(text, cat) {
    return Ipc_Custom.revive({
      name:text,
      desc:text,
      cat:cat
    })
  }
})
//
Ipcs = Object.RecArray.of(Ipc, {
  isMatch:function(rec, search) {
    return rec.name.match(search) || (rec.desc && rec.desc.match(search)) || (rec.code && rec.code.match(search));
  },
  lev:function(rec, search) {
    return Math.min(search.lev(rec.name), search.lev(rec.desc));
  },
  //
  ajax:function() {
    var self = this;
    return {
      fetchAll:function(callback) {
        self.ajax_fetchAll(Ajax.Ipc.getAll, callback);
      },
      fetchMatches:function(text, callback) {
        self.ajax_fetchMatches(this.fetchAll, text, callback);
      }
    }
  }
})
/**
 * LevelRec IpcHm
 */
IpcHm = Object.LevelRec.extend({
  /*
   ipc
   reportId
   userGroupId
   clientId
   every
   interval
   active
   Ipc
   Report
   */
  onload:function() {
    this.Ipc = Ipc.revive(this.Ipc);
    this.Proc_last = this.getMostRecentProc();
    this._comment = this.uiComment();
  },
  isClientLevel:function() {
    return this.clientId > 0;
  },
  isNew:function() {
    return this.userGroupId == null;
  },
  getKey:function() {
    return this.userGroupId + ',' + this.ipc;
  },
  hasInterval:function() {
    return this.every > 0;
  },
  getMostRecentProc:function() {
    if (this.fs && this.fs.procedures) 
      return this.fs.procedures.getMostRecent(this.Ipc.ipc);
  },
  uiLastResults:function() {
    if (this.Proc_last) {
      var d = '<b>' + this.Proc_last.date + '</b>';
      var r = this.Proc_last.uiResults();
      return (r) ? d + ': ' + r : d;
    } else { 
      return '(None)';
    }
  },
  uiComment:function() {
    if (this._comment == null)
      this._comment = this.summaryEvery();
    return this._comment;
  },
  uiApplies:function() {
    var a = [];
    a.push((this.active) ? 'Yes' : 'No');
    if (this.active && this.hasInterval())
      a.push(', ' + this.summaryEvery().toLowerCase());
    if (this.isClientLevel()) 
      a.push(' (Customized)');
    return a.join('');
  },
  summary:function() {
    if (this.auto) 
      return this.summaryEvery() + ': ' + this.summaryCriteria();
    else 
      return '(None)';
  },
  summaryEvery:function() {
    return (this.hasInterval()) ? 'Every ' + this.every + ' ' + C_IpcHm.INTERVALS[this.interval] : 'N/A'; 
  },
  summaryCriteria:function() {
    return (this.criteria) ? RepCritRec.revive(this.criteria).summary() : '';
  },
  cloneAsClientLevel:function(cid, rec) {
    return this.asNewClientLevel(cid, rec.Ipc).aug({
      reportId:rec.reportId,
      every:rec.every,
      interval:rec.interval,
      _comment:rec._comment,
      _name:rec._name});
  },
  //
  asNewClientLevel:function(cid, Ipc) {
    return IpcHm.revive({
      active:1,
      clientId:cid,
      every:1,
      interval:C_IpcHm.INT_YEAR,
      ipc:Ipc.ipc,
      Ipc:Ipc,
      _name:Ipc.name});
  },
  ajax:function(worker) {
    var self = this;
    return {
      save:function(callback) {
        Ajax.Ipc.saveIpcHm(self, worker, callback);
      },
      remove:function(callback) {
        Ajax.Ipc.delIpcHm(self, worker, callback);
      }
    }
  }
})
//
IpcHms = Object.RecArray.of(IpcHm, {
  //
  actives:function() {
    return this.filterOn('active');
  },
  //
  from:function(fs) {
    IpcHm.fs = fs;
    this.cid = fs.cid;
    return this.revive(fs.hms);
  },
  ajax:function(worker) {
    return {
      refetch:function(callback) {
        if (IpcHms.cid)
          Ajax.Ipc.getIpcHmsFor(IpcHms.cid, worker, callback);
      }
    }
  }
})
/**
 * DummyReportCriteria
 */ 
DummyReportCriteria = {
  /*
   * @arg RepCritRec rec
   */
  from:function(rec) {
    return ReportCriteria.revive({'type':0,'Rec':rec});
  }
}