/**
 * RecArray ReportStubs
 */
ReportStubs = Object.RecArray.extend({
  getItemProto:function(json) {
    return ReportStub;
  },
  //
  ajax:function(worker) {
    return {
      getAll:function(callback) {
        Ajax.Reporting.getStubs(worker, callback);
      }
    }
  }
})
/**
 * Rec ReportStub
 */
ReportStub = Object.LevelRec.extend({
  onload:function() {
    this._level = (this.isAppLevel()) ? 'Application' : 'Custom';
  },
  uiComment:function() {
    var comment = (String.isBlank(this.comment)) ? this.IpcHm && this.IpcHm.Ipc.name : this.comment;
    if (comment) {
      if (! String.isBlank(this.refs)) {
        comment += "\n\nREFERENCES\n" + this.refs;
      }
      if (comment.length > 80) {
        comment = comment.substr(0, 80) + "<a href='javascript:' onclick='this.nextSibling.style.display=\"inline\";this.style.display=\"none\"'>... (More)</a><span style='display:none'>" + comment.substr(80) + "</span>";
      }
      comment = String.crlfToBr(comment);
      comment = comment.replace('<br/>REFERENCES', '<br/><b>REFERENCES</b>');
    }
    return comment;
  }
})
/**
 * ReportStub ReportCriteria
 *   RepCritRec Rec
 */
ReportCriteria = ReportStub.extend({
  //
  getProto:function(json) {
    switch (json.type) {
      case ReportCriteria.TYPE_AUDIT:
        return ReportCriteria_Audit;
      default:
        return ReportCriteria;
    }
  },
  onload:function() {
    this.Rec = RepCritRec.revive(this.Rec);
    if (this.RecDenom)
      this.RecDenom = RepCritRec.revive(this.RecDenom);
    this.reviveResultRecs();
    this.app = this.isAppLevel();
    this._dirty = false;
  },
  getPrompts:function() {
    var map = this.Rec.getPrompts();
    if (this.RecDenom)
      this.RecDenom.getPrompts(map);
    return Map.length(map) ? map : null;
  },
  reviveResultRecs:function() {
    this.recs = RepRecs.revive(this.recs, RepRec.create());
    this.recsDenom = RepRecs.revive(this.recsDenom, RepRec.create());
  },
  isNew:function() {
    return this.reportId == null;
  },
  addDenom:function() {
    this.RecDenom = this.Rec.clone();
  },
  removeDenom:function() {
    this.RecDenom = null;
  },
  addIpcHm:function(ipc) {
    this.IpcHm = IpcHm_Cds.from(ipc);
  },
  removeIpcHm:function() {
    this.IpcHm = null;
  },
  addJoin:function(rec, join) {  // todo: fix this, should be on rec object and bubble up
    rec.Joins.add(join);
    this._dirty = true;
  },
  getRecName:function() {
    return this.Rec._name;
  },
  isTable:function(table) {
    return this.Rec.table_ == table;
  },
  isTable_Audit:function() {
    return this.isTable(RepCritRec.T_AUDITS);
  },
  isFractioned:function() {
    return this.RecDenom != null || this.hasIpcHm();
  },
  isFractionable:function() {
    return this.type == ReportCriteria.TYPE_PATIENT || this.type == ReportCriteria.TYPE_MU || this.type == ReportCriteria.TYPE_MU2 || this.type == ReportCriteria.TYPE_CQM;
  },
  isEditable:function() {
    return ! this.isAppLevel() || me.admin; 
  },
  isTypeCds:function() {
    return this.type == ReportCriteria.TYPE_CDS;
  },
  hasIpcHm:function() {
    return this.IpcHm != null;
  },
  summary:function(joiner, rec) {
    rec = rec || this.Rec;
    var a = [rec._name + ' (' + rec.summary() + ')'];
    if (rec.Joins && rec.Joins.length)
      a.push(rec.Joins.summary(joiner));
    return a.join(joiner || ' ');
  },
  doPrompts:function(onfinish) {
    var prompts = this.getPrompts();
    if (prompts) {
      Map.each(prompts, function(crits) {  // TODO: this only works for just one
        var crit = crits[0].saveBeforeState();
        async(function() {
          CritRecEntryPop.pop_asRunPrompt(crit).bubble('onsave', function() {
            for (var i = 1; i < crits.length; i++) 
              crits[i].copyPromptsFrom(crit);
            if (ReportingTile)
              ReportingTile.reportview.refreshSummary();  // yech
            onfinish();
          })
        })
      })
    } else {
      onfinish();
    }
  },
  //
  ajax:function(worker) {
    var self = this;
    return {
      save:function(callback) {
        Ajax.Reporting.save(self, worker, callback);
      },
      generate:function(callback) {
        worker.working(false);
        self.doPrompts(function() {
          worker.working(true);
          Ajax.Reporting.generate(self, worker, callback);  
        })
      },
      download:function(asNumerator, noncomps, duenow) {
        Ajax.Reporting.download(self, asNumerator, noncomps, duenow);
      },
      addJoin:function(rec, table, callback) {
        Page.work(function() {
          Ajax.Reporting.getJoin(table, worker, function(join) {
            Page.work(false);
            self.addJoin(rec, join);
            callback();
          })
        })
      },
      //
      asNew:function(type, callback) {
        Ajax.Reporting.newReport(type, worker, callback);
      },
      get:function(id, callback) {
        Ajax.Reporting.getReport(id, worker, callback);
      },
      remove:function(id, callback) {
        Ajax.Reporting.deleteReport(id, worker, callback);
      }
    }
  }
})
ReportCriteria_Audit = ReportCriteria.extend({
  reviveResultRecs:function() {
    if (this.recs) 
      this.recs = RepRecs.revive(this.recs, AuditRec.create(this));
  }
})
/**
 * Rec RepCritRec
 */
RepCritRec = Object.Rec.extend({
  /*
   * RepCritJoin[] Joins
   * table_
   * pid_
   */
  _name:'Record',  // @abstract
  getProto:function(json) {
    switch (json.table_) {
      case RepCritRec.T_CLIENTS:
        return RepCrit_Client;
      case RepCritRec.T_ADDRESS:
        return RepCrit_Address;
      case RepCritRec.T_DIAGNOSES:
        return RepCrit_Diagnosis;
      case RepCritRec.T_SESSIONS:
        return RepCrit_Session;
      case RepCritRec.T_MEDS:
        return RepCrit_Med;
      case RepCritRec.T_MEDHIST:
        return RepCrit_MedHist;
      case RepCritRec.T_SOCTOB:
        return RepCrit_SocTob;
      case RepCritRec.T_OFFICEVISIT:
        return RepCrit_OfficeVisit;
      case RepCritRec.T_ADMINIPC:
        return RepCrit_AdminIpc
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
      case RepCritRec.T_ICARDS:
        return RepCrit_ICard;
      default:
        return this;
    }
  },
  onload:function(json) {
    for (var fid in json) {
      if (fid == 'Joins') 
        this.Joins = RepCritJoins.create().revive(json.Joins, RepCritJoin.create());
      else if (fid == 'prompted_')
        this.prompted_ = json.prompted_;
      else if (this._isCritValueFid(fid)) 
        this[fid] = this._getCritValueProto(fid).revive(json[fid]).setFidLabel(fid, this._getFidLabel(fid));
    }
  },
  getPrompts:function(a) {  // returns {'key':[RepCritRec,..],..}
    a = a || {};
    if (this.prompted_) 
      Map.pushInto(a, this.prompted_, this);
    if (this.Joins) {
      this.Joins.each(function(j) {
        if (j.Recs)
          j.Recs.each(function(rec) {
            rec.getPrompts(a);
          })
      })
    }
    return a;
  },
  eachValue:function(oneach) {
    for (var fid in this)
      if (this[fid] && this[fid]._fid)
        oneach(this[fid]);
  },
  eachFid:function(oneach) {
    for (var fid in this)
      if (this[fid] && this[fid]._fid)
        oneach(fid);
  },
  /*
   * @arg RepCritRec that
   */
  copyPromptsFrom:function(that) {
    var self = this;
    this.eachFid(function(fid) {
      if (self.prompted_ == 'DO' && fid == 'userId') {
        // don't copy userId into procedure
      } else {   
        if (that[fid] && self[fid].equalsBeforeStateOf(that[fid]))
          self[fid] = that[fid];
      }
    })
  },
  saveBeforeState:function() {
    this.eachValue(function(value) {
      value.saveBeforeState();
    })
    return this;
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
  summary:function(withName) {
    var s = [];
    this.eachValue(function(cv) {
      if (cv.hasData()) 
        s.push(cv.summary());
    })
    s = (s.length == 0) ? 'Any' : s.join(' and ');
    return withName ? this._name + ': ' + s : s;
  },
  //
  ajax:function(worker) {
    var self = this;
    return {
      loadParInfo:function(callback) {
        if (self.pid_ && self._pi == null) {
          Page.work(function() {
            Ajax.JTemplates.getParInfo(self.pid_, function(pi) {
              Page.work(false);
              self._pi = pi;
              callback();
            })
          })
        } else {
          callback();
        }
      }
    }
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
      case 'userGroupId':
        return RepCritValueRecPicker.from(PracticeSelector);
      case 'age':
        return RepCritValueAge;
      case 'birth':
        return RepCritValueDate;
      case 'sex':
        return RepCritValueSex;
      case 'deceased':
        return RepCritValueDate;
      case 'ethnicity':
        return RepCritValueFixed.from(C_Client.ETHNICITIES);
      case 'race':
        return RepCritValueFixed.from(C_Client.RACES);
      case 'releasePref':
        return RepCritValueFixed.from(C_Client.RELEASE_PREFS);
      case 'cdata5':
      case 'cdata6':
      case 'active':
        return RepCritValueBool;
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'userGroupId':
        return 'Practice';
      case 'uid':
        return 'Patient ID';
      case 'birth':
        return 'Birth Date';
      case 'releasePref':
        return 'Release Pref';
      case 'cdata5':
        return 'Living Will';
      case 'cdata6':
        return 'POA';
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
        return RepCritValueDateWithinOver;
      case 'clientId':
        return RepCritValueRecPicker.from(ClientSelector);
      case 'userId':
        return RepCritValueRecPicker.from(UserAnchorTab);
      case 'action':
        return RepCritValueFixed.from(AuditRec.ACTIONS);
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
        return 'Record Name';
      case 'recId':
        return 'Record ID';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
}) 
RepCrit_ICard = RepCritRec.extend({
  _name:'Insurance',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'dateEffective':
        return RepCritValueDateWithinOver;
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'planName':
        return 'Plan';
      case 'groupNo':
        return 'Group';
      case 'subscriberNo':
        return 'Policy';
      case 'dateEffective':
        return 'Effective';
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
      case 'date':
        return RepCritValueDateWithinOver;
      case 'dateClosed':
        return RepCritValueDateWithinOver;
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'icd':
        return 'ICD Code';
      case 'snomed':
        return 'SNOMED';
      case 'text':
        return 'Description';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
})
RepCrit_Session = RepCritRec.extend({
  _name:'Documents',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'templateId':
        return RepCritValueFixed.from(C_Templates);
      case 'dateService':
        return RepCritValueDateWithinOver;
      case 'closedBy':
      case 'createdBy':
        return RepCritValueRecPicker.from(UserAnchorTab);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'templateId':
        return 'Template';
      case 'dateService':
        return 'Date of Service';
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
      case 'drugSubclass':
        return RepCritValueRegex.from(C_DrugSubclasses);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'index':
        return 'RxNorm';
      case 'drugSubclass':
        return 'Subclass';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
})
RepCrit_MedHist = RepCritRec.extend({
  _name:'Med History',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'date':
        return RepCritValueDateWithinOver;
      case 'userId':
        return RepCritValueRecPicker.from(UserAnchorTab);
      case 'ncOrderGuid':
        return RepCritValueFixed.from(C_SessionMedNc.FINAL_DEST_TYPES);
      case 'ncOrigrxGuid':
        return RepCritValueFixed.from(C_SessionMedNc.DEA_CLASS_CODES);
      case 'ncExtPhysId':
        return RepCritValueRecPicker.from(UserAnchorTab);
      case 'ncFormularyChecked':
        return RepCritValueFixed.from(C_SessionMedNc.FORMULARY_CHECKED);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'index':
        return 'RxNorm';
      case 'userId':
        return 'Provider';        
      case 'ncOrderGuid':
        return 'Final Dest';
      case 'ncOrigrxGuid':
        return 'DEA Class Code';
      case 'ncExtPhysId':
        return 'Provider';
      case 'ncFormularyChecked':
        return 'Formulary';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
})
RepCrit_SocTob = RepCritRec.extend({
  _name:'Social: Tobacco',
  _getCritValueProto:function(fid) {
    switch (fid) {
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'value':
        return 'Recode';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
})
RepCrit_OfficeVisit = RepCritRec.extend({
  _name:'Encounter',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'userId':
        return RepCritValueRecPicker.from(UserAnchorTab);
      case 'date':
        return RepCritValueDateWithinOver;
      case 'userGroupId':
        return RepCritValueRecPicker.from(PracticeSelector);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'userId':
        return 'Provider';
      case 'userGroupId':
        return 'Practice';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
    }
  }
})
RepCrit_AdminIpc = RepCritRec.extend({
  _name:'MU Event',
  _getCritValueProto:function(fid) {
    switch (fid) {
      case 'ipc':
        return RepCritValueRecPicker.from(IpcPicker_Admin);
      case 'userId':
        return RepCritValueRecPicker.from(UserAnchorTab);
      case 'date':
        return RepCritValueDateWithinOver;
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'ipc':
        return 'Event';
      case 'userId':
        return 'Provider';
      default:
        return RepCritRec._getFidLabel.call(this, fid);
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
      case 'cat':
        return RepCritValueFixed.from(C_Ipc.CATS);
      case 'date':
        return RepCritValueDateWithinOver;
      case 'providerId':
        return RepCritValueRecPicker.from(ProviderPicker);
      case 'userId':
        return RepCritValueRecPicker.from(UserAnchorTab);
      case 'addrFacility':
        return RepCritValueRecPicker.from(FacilityPicker);
      case 'value':
        return RepCritValueProc;
      case 'userGroupId':
        return RepCritValueRecPicker.from(PracticeSelector);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'ipc':
        return 'Test/Proc';
      case 'cat':
        return 'Category';
      case 'providerId':
        return 'Provider';
      case 'userId':
        return 'Internal Provider';
      case 'addrFacility':
        return 'Facility';
      case 'userGroupId':
        return 'Practice';
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
        return RepCritValueDateWithinOver;
      case 'value':
        return RepCritValueResult;
      case 'interpretCode':
        return RepCritValueFixed.from(C_ProcResult.INTERPRET_CODES);
      case 'orderBy':
        return RepCritValueRecPicker.from(UserAnchorTab);  
      case 'userGroupId':
        return RepCritValueRecPicker.from(PracticeSelector);
      default:
        return RepCritValue;
    }
  },
  _getFidLabel:function(fid) {
    switch (fid) {
      case 'ipc':
        return 'Test/Proc';
      case 'interpretCode':
        return 'Interpretation'
      case 'orderBy':
        return 'Ordered By';
      case 'userGroupId':
        return 'Practice';
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
        return RepCritValueDateWithinOver;
      case 'name':
        return RepCritValueDsyncQuestion.from('imm.name');
      case 'manufac':
        return RepCritValueDsyncQuestion.from('imm.manufac');
      case 'dose':
        return RepCritValueDsyncQuestion.from('imm.dose');
      case 'route':
        return RepCritValueDsyncQuestion.from('imm.route');
      case 'site':
        return RepCritValueDsyncQuestion.from('imm.site');
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
        return RepCritValueDateWithinOver;
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
    this.each(function(rec, i) {
      rec._i = i;
    })
  },
  add:function(rec, i) {
    if (Object.isUndefined(i))
      this.push(rec);
    else
      this.pushBefore(rec, i);
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
  onload:function(itemProto) { 
    itemProto.bubble('onempty', this.join_onempty.bind(this));
    this.reindex();
  },
  add:function(json) {
    IndexedRecArray.add.call(this, this.itemProto.revive(json));
  },
  join_onempty:function(join) {
    this.drop(join);
  },
  summary:function(joiner) {
    var a = [];
    this.each(function(j) {
      a.push(j.summary());
    })
    return (a.length) ? a.join(joiner || ' and ') : '';
  }
})
/**
 * Rec RepCritJoin
 *   RepCritRec[] Recs
 */
RepCritJoin = Object.Rec.extend({
  /*
   jt
   table
   Recs
   */
  onempty:function(join) {},
  //
  getJoinTypeLabel:function() {
    return this.JTS[this.jt];
  },
  getCountLabel:function() {
    if (this.isCountType())
      return this.ct;  
  },
  getJoinTypeCount:function() {
    return [this.getJoinTypeLabel(), this.getCountLabel()].filter().join(' ');
  },
  isCountType:function() {
    switch (this.jt) {
      case this.JT_HAVE_CT:
      case this.JT_HAVE_CT_LT:
      case this.JT_HAVE_CT_GT:
        return true;
    }
  },
  isCaseType:function() {
    switch (this.jt) {
      case this.JT_HAVE_ONE:
      case this.JT_NOT_HAVE_ANY:
        return true;
    }
  },
  getRecName:function() {
    if (this.Recs)
      return this.Recs[0]._name;
  },
  summary:function() {
    var name = this.Recs.getSameName();
    if (name)
      return this.getJoinTypeCount() + ' ' + this.getRecName() + ' ' + this.Recs.summary();  
    else
      return this.getJoinTypeCount() + ' ' + this.Recs.summary(true);  
  },
  onload:function() {
    this.Recs = RepCritRecs.revive(this.Recs);
  },
  allowable:function() {
    if (this.Recs && this.Recs.length > 1)
      return [this.JT_HAVE_ONE, this.JT_HAVE_ALL, this.JT_NOT_HAVE_ANY, this.JT_NOT_HAVE_ALL];
    else
      return [this.JT_HAVE, this.JT_HAVE_CT, this.JT_HAVE_CT_LT, this.JT_HAVE_CT_GT, this.JT_NOT_HAVE];
  },
  updateJoinType:function(jt) {
    this.jt = jt;
    if (this.isCountType())
      this.ct = this.ct || 1;
  },
  updateCount:function(ct) {
    this.ct = ct;
  },
  remove:function() {
    this.Recs = RepCritRecs.revive([]);
    this.onempty(this);
  },
  add:function(rec, i) {
    this.Recs.add(rec, i);
    if (this.Recs.length == 2) 
      this.jt = (this.jt == this.JT_NOT_HAVE) ? this.JT_NOT_HAVE_ANY : this.JT_HAVE_ALL;
  },
  refreshCaseFlags:/*bool(true if any)*/function() {
    if (this.Recs && this.Recs.length) {
      var set;
      for (var i = 1; i < this.Recs.length; i++) {
        if (this.Recs[i].case_) {
          set = true;
          break;
        }
      }
      this.Recs[0].case_ = set;
      return set;
    }
  },
  drop:function(rec) {
    this.Recs.drop(rec);
    this.refreshCaseFlags();
    switch (this.Recs.length) {
      case 1:
        this.jt = (this.jt == this.JT_NOT_HAVE_ANY) ? this.JT_NOT_HAVE : this.JT_HAVE;
        break;
      case 0:
        this.onempty(this);
        break;
    }
  },
  //
  ajax:function(worker) {
    var self = this;
    return {
      add:function(table, callback, i, asNewCase) {
        Ajax.Reporting.getJoin(table, worker, function(join) {
          var rec = RepCritRec.revive(join.Recs[0]);
          self.add(rec, i);
          if (asNewCase) {
            rec.case_ = true;
            self.refreshCaseFlags();
          }
          callback(rec);
        })
      },
      add_asNewCase:function(table, callback) {
        return this.add(table, callback, null, true);
      }
    }
  }
})
/**
 * IndexedRecArray RepCritRecs
 */
RepCritRecs = IndexedRecArray.extend({ 
  getItemProto:function() {
    return RepCritRec; 
  },
  getSameName:function() {
    var name = this[0]._name;
    for (var i = 1; i < this.length; i++) {
      if (this[i]._name != name) 
        return null;
    }
    return name;
  },
  summary:function(withName) {
    var a = [];
    this.each(function(rec) {
      a.push(rec.summary(withName));
    })
    return '(' + a.join(', ') + ')'; 
  }
})
/**
 * Rec RepCritValue
 */
RepCritValue = Object.Rec.extend({
  /* 
   * op
   * value  // single value e.g. 'Singulair' or comma-delimited e.g. '1,10'
   * text_  // picker text, e.g. 'Colonoscopy'
   */
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
    if (this.op == null) {
      this.text_ = null;
    }
    return this;
  },
  isValueless:function() {
    return this.op == RepCritValue.OP_NULL || this.op == RepCritValue.OP_NOT_NULL || this.op == RepCritValue.OP_NUMERIC;  
  },
  hasData:function() {
    return this.op && (this.value || this.isValueless());
  },
  saveBeforeState:function() {
    this._op = this.op;
    this._value = this.value;
  },
  equalsBeforeStateOf:function(that) {
    return this.op == that._op && this.value == that._value;
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
    return [RepCritValue.OP_EQ, RepCritValue.OP_NEQ, RepCritValue.OP_START, RepCritValue.OP_CONTAIN, RepCritValue.OP_IN, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];
  }
})
/**
 * RepCritValue Subclasses
 */
RepCritValueNumeric = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_EQ, RepCritValue.OP_NEQ, RepCritValue.OP_LTN, RepCritValue.OP_GTN, RepCritValue.OP_BETWEEN, RepCritValue.OP_IN, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];  
  }
})
RepCritValueResult = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_EQ, RepCritValue.OP_NUMERIC, RepCritValue.OP_NEQ, RepCritValue.OP_LTN, RepCritValue.OP_GTN, RepCritValue.OP_BETWEEN, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];  
  }
})
RepCritValueProc = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_NUMERIC];  
  }
})
RepCritValueAge = RepCritValue.extend({ 
  getFixedOps:function() {
    return [RepCritValue.OP_EQ, RepCritValue.OP_NEQ, RepCritValue.OP_OLDER, RepCritValue.OP_YOUNGER, RepCritValue.OP_AGERANGE, RepCritValue.OP_SPLITAGERANGE, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];
  }
})
RepCritValueFixed = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_IS, RepCritValue.OP_IS_NOT, RepCritValue.OP_IN, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL]; 
  },
  from:function(values) {
    return this.extend({
      getFixedValues:function() {
        return values;
      }
    }) 
  }
})
RepCritValueDsyncQuestion = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_IS, RepCritValue.OP_IS_NOT];  
  },
  from:function(dsync) {
    return this.extend({
      createPicker:function(cv) {
        var pi = cv._parent._pi;
        var q = pi.getQuestionByDsync(dsync);
        return QuestionEntry.create(q);
      }
    })
  }
})
RepCritValueRegex = RepCritValueFixed.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_REGEX, RepCritValue.OP_NOT_REGEX]; 
  }
})
RepCritValueSex = RepCritValueFixed.from({'M':'Male','F':'Female'});
RepCritValueBool = RepCritValueFixed.from({'1':'Yes','0':'No'});
//
RepCritValueDate = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_ON, RepCritValue.OP_NOT_ON, RepCritValue.OP_BEFORE, RepCritValue.OP_AFTER, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];
  },
  createPicker:function() {
    return QuestionDateEntry.create();
  }
})
RepCritValueDateWithinOver = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_ON, RepCritValue.OP_NOT_ON, RepCritValue.OP_BEFORE, RepCritValue.OP_AFTER, RepCritValue.OP_BETWEEN_DATES, RepCritValue.OP_WITHIN, RepCritValue.OP_OVER, RepCritValue.OP_NULL, RepCritValue.OP_NOT_NULL];
  },
  createPicker:function() {
    return QuestionDateEntry.create();
  }
})
//
RepCritValueRecPicker = RepCritValue.extend({
  getFixedOps:function() {
    return [RepCritValue.OP_IS, RepCritValue.OP_IS_NOT, RepCritValue.OP_IN];  
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
  onload:function(itemProto) {
    this.joinTables = [];
    itemProto.joinFids = [];
    var rec = this.current();
    for (var fid in rec) { 
      if (fid.beginsWith('Join') && Array.is(rec[fid])) {
        itemProto.joinFids.push(fid);
        this.joinTables.push(rec[fid].current()._table);
      }
    }
    this.joinCt = this.joinTables.length;
  },
  count:function(by, firstJoinOnly) {
    if (by == ReportCriteria.COUNT_BY_PATIENT)
      return this.length;
    var ct = 0, jct;
    this.each(function(rec) {
      jct = rec.getJoinRecCt(firstJoinOnly);
      if (jct)  // if (rec.Join0 && rec.Join0.length)
        ct += jct;
      else
        ct++;
    })
    return ct;
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
  },
  getJoinRecCt:function(firstJoinOnly) {
    var fid, ct = 0, j = firstJoinOnly ? 1 : 99;
    for (var i = 0; i < j; i++) {
      fid = 'Join' + i;
      if (Object.isUndefined(this[fid]))
        break;
      if (Array.is(this[fid]))
        ct += this[fid].length;
      else if (Object.is(this[fid]))
        ct++;
    }
    return ct;
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
    if (repRecJoin) {
      rec.table = RepCritRec.TABLES[repRecJoin.current()._table];
      rec.labels = Array.from(repRecJoin, '_label');
    } else {
      rec.labels = [];
    }
    return rec;
  }
}
/**
 * RepRec AuditRec
 *   Snapshot before
 *   Snapshot after
 */
AuditRec = RepRec.extend({
  /*
   * @arg ReportCriteria report
   */
  create:function(report) {
    this.report = report;
    return RepRec.create.call(this);
  },
  onload:function() {
    this._label = this._label.ellips(80);
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
IpcHm_Cds = Object.Rec.extend({
  //
  from:function(Ipc) {
    return this.revive({
      'ipc':Ipc.ipc,
      'every':1,
      'interval':C_IpcHm.INT_YEAR,
      'Ipc':Ipc});
  }
})