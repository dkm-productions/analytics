/**
 * NewCrop Page Helper
 * Global static
 * @author Warren Hornsby
 */
var NewCrop = {
  cid:null,
  auditsSince:null,
  // 
  SENT_CURRENT:1,  // Sent to NewCrop to update current meds
  SENT_PLAN:2,     // Sent to NewCrop to update med plan
  SENT_ALLERGY:3,  // Sent to NewCrop to update allergies
  SENT_ANY:9,      // Sent from facesheet
  sent:null,
  //
  NC_ADD:'nc.add',  // Audit keys
  NC_RX:'nc.rx',
  NC_DC:'nc.dc',
  NC_CURRENT:'nc.current',
  NC_ALLERGY:'nc.allergy',  
  qs:null,  // {auditKey:JQuestion,..}
  //
  DEST_COMPOSE:'compose',
  DEST_MEDENTRY:'medentry',
  DEST_RENEWAL:'renewal',
  //
  _loaded:false,
  _warned:false,
  _sending:false,
  /*
   * Loader
   */
  load:function(er, cid, auditsSince, trialPatient) {
    if (er) {
      this._loaded = true;
      this.cid = cid;
      this.auditsSince = auditsSince;
      this.qs = (auditsSince) ? {} : null;
      this.trialPatient = (trialPatient == 1) || me.trial;
    }
  },
  loadFromFacesheet:function(er, client) {
    this.load(er, client.clientId, null, client.trial);
  },
  loadFromSession:function(er, session) {
    this.load(er, session.clientId, session.dcsql, session.trial);
  },
  loadFromMsg:function(er, client) {
    this.load(er, client.clientId, now, client.trial);
  },
  /*
   * Validate required erx fields
   * - callback() if valid
   */
  validate:function(callback) {
    if (this._loaded) {
      if (this.trialPatient) {
        Html.Window.working(false);
        Pop.Msg.showCritical('This function is not available for trial users/patients.');
        return;
      }
      var self = this;
      Ajax.Erx.validate(self.cid,
        function(required) {
          Html.Window.working(false);
          if (required == null) 
            callback();
          else {
            var a = ['The following information is required for electronic prescribing:<ul style="list-style-type:disc;margin-left:2em;margin-top:1em"><li>'];
            a.push(required.join('</li><li>'));
            a.push('</li></ul>');
            Pop.Msg.showCritical(a.join(''));
          }
        });
    }
  },
  /*
   * Send to New Crop
   * - callback(fs)  // or null if this.cid=null 
   */
  send:function(dest, sentReason, args, callback, norefresh) {
    if (this._loaded) {
      if (this.trialPatient) {
        Html.Window.working(false);
        Pop.Msg.showCritical('This function is not available for trial users/patients.');
        return;
      }
      this.sent = sentReason || NewCrop.SENT_ANY;
      this._sending = true;
      args = args || {};
      if (this.cid) 
        args.id = this.cid;
      if (dest) 
        args.dest = dest;
      var self = this;
      self._warned = false;
      /*
      NewCropPop.pop(args, function() {
        self._sending = false;
        if (norefresh || dest == NewCrop.DEST_RENEWAL || self.cid == null) {
          callback(null);
        } else {
          var since = (self.sent == NewCrop.SENT_PLAN) ? self.auditsSince : null;
          self.refreshFromNewCrop(self.cid, since, false, false, callback);
        }
      })
      */
      Page.pop(Page.PAGE_NEWCROP, args, Page.HIDE_MENU, null, 
        function() {
          self._sending = false;
          if (Pop.isActive('pop-msg')) 
            Pop.close();
          if (norefresh || dest == NewCrop.DEST_RENEWAL || self.cid == null) {
            callback(null);
          } else {
            var since = (self.sent == NewCrop.SENT_PLAN) ? self.auditsSince : null;
            self.refreshFromNewCrop(self.cid, since, false, false, callback);
          }
        }, 
        function(p) {
          if (! self._warned) {
            self._warned = true;
            Pop.Msg.showCritical('The ePrescribe window is still open. Please close it to refresh facesheet.', function() {
              p.focus();
            });
          }
        });
    }
  },
  /*
   * Refresh meds/allergies from New Crop
   * @arg int cid
   * @arg string since (optional)
   * @arg bool auditless (default false)
   * @arg bool silent true for no popups (default false)
   * @callback(fs)  
   */
  refreshFromNewCrop:function(cid, since, auditless, silent, callback) {
    if (! silent)
      Pop.Working.show('Compiling ePrescribe updates...');
    Ajax.Erx.refresh(cid, since, auditless, 
      function(fs) {
        callback(fs);
        if (! silent) 
          Pop.Working.close();
      },
      function(errorMsg) {
        if (! silent) {
          Html.Window.clearWorking();
          Page.showErrorDetails('There was a problem compiling ePrescribe updates.', errorMsg);
        }
      });
  },
  /*
   * Send from face.php
   */
  sendFromFacesheet:function(dest, callback) {
    this.send(dest, null, null, callback);
  },
  /*
   * Send from erxstatus.php
   * - id: optional
   */
  sendFromStatus:function(id, dest, callback) {
    this._loaded = true;
    this.cid = id;
    var dest = dest;
    this.send(dest, null, null, callback, true);
  },
  /*
   * Send from erxpharm.php
   * - id: optional, leave null to pass 'not found' patient
   */
  sendFromPharm:function(id, rxguid, callback) {
    this._loaded = true;
    this.cid = id;
    var args = {'rxguid':rxguid};
    var dest = NewCrop.DEST_RENEWAL;
    this.send(dest, null, args, callback);
  },
  /*
   * Send from console
   */
  sendFromConsole:function(q, callback) {
    if (q.erx == NewCrop.SENT_CURRENT || q.erx == NewCrop.SENT_ALLERGY) 
      dest = NewCrop.DEST_MEDENTRY;
    else 
      dest = NewCrop.DEST_COMPOSE;
    this.send(q.erdest, q.erx, null, callback);
  },
  /*
   * Send from TemplateUI
   */
  sendFromTui:function(q, tui, callback) {
    var args = null;
    var refills = null;
    if (tui.data && tui.data.refill)
      args = {'rf':Json.encode(tui.data.refill)};
    this.send(q.erdest, q.erx, args, callback);
  },
  /*
   * Determine whether med question should defer to NewCrop
   * If so, q.erx assigned to proper NewCrop.SENT_ reason and question cached 
   * - q: JQuestion
   * - inTable: par's IN_TABLE (required for med, null for allergy)
   * - outData: question's OUT_DATA (required for med, null for allergy)
   */
  setQuestionErx:function(q, inTable, outData) {
    if (this._loaded) {
      if (q.type == C_Question.TYPE_ALLERGY) {
        q.erx = NewCrop.SENT_ALLERGY;
        q.erdest = NewCrop.DEST_MEDENTRY;
      } else if (outData || inTable) { 
        if (outData && inTable) {
          q.erx = NewCrop.SENT_CURRENT;
          q.erdest = NewCrop.DEST_MEDENTRY;
        } else {
          q.erx = NewCrop.SENT_PLAN;
          q.erdest = NewCrop.DEST_COMPOSE;
        } 
        q.ncmeds = {};
      }
      if (q.erx)
        this._cacheQuestion(q);
    }
  },
  _cacheQuestion:function(q) {
    switch (q.erx) {
      case NewCrop.SENT_CURRENT:
        this.qs[NewCrop.NC_CURRENT] = q;
        break;
      case NewCrop.SENT_ALLERGY:
        this.qs[NewCrop.NC_ALLERGY] = q;
        break;
      case NewCrop.SENT_PLAN:
        this.qs[this._auditKeyFromQ(q)] = q;
        break;
    }
  },
  /*
   * Get cached med question 
   */
  getQuestion:function(key) {
    return this.qs[key];
  },
  getOppositeMedQuestion:function(key) {
    switch (key) {
      case NewCrop.NC_ADD:
        return this.qs[NewCrop.NC_CURRENT];
      case NewCrop.NC_CURRENT:
        return this.qs[NewCrop.NC_ADD];
      case NewCrop.NC_DC:
        return this.qs[NewCrop.NC_CURRENT];
    }
    return null;
  },
  /*
   * Returns true if was sent to NewCrop
   */
  wasSent:function() {
    return (this._loaded && this._sending);
  },
  //
  _auditKeyFromQ:function(q) {
    switch (q.uid.substr(0, 3)) {
      case '@ad': 
        return NewCrop.NC_ADD;
      case '@rf':
        return NewCrop.NC_RX;
      case '@dc':
        return NewCrop.NC_DC;
    }
  }
}
NewCropPop = {
  pop:function(args, callback) {
    return Html.Pop.singleton_pop.apply(this, arguments).fullsize();
  },
  create:function() {
    return Html.BrowserPop.create('Electronic Prescribing').extend(function(self) {
      return {
        init:function() {
          self.SideFrame = Html.IFrame.create().hide().addClass('mr5').setWidth(255);
          Html.Table2Col.create(self.content, self.SideFrame, self.IFrame);
          self.withExit();
        },
        fullsize:function(maxw, maxh) {
          var vmar = 40 + self.getHeight() - self.IFrame.getHeight();
          var hmar = 40;
          self.SideFrame.setHeight(self.IFrame.getHeight());
          self.IFrame.fullsize(vmar, hmar, maxh, maxw);
          if (self.args && self.args.rf) {
            self.SideFrame.show().nav(Page.url('newcrop-refill.php', self.args));
            self.IFrame.setWidth(self.IFrame.getWidth() - 255);
          } else {
            self.SideFrame.hide();
          }
          return self.reposition();
        },
        onshow:function(args, callback) {
          self.args = args;
          self.callback = callback;
          self.nav(Page.url('newcrop-body.php', args));
          self.fullsize();
        },
        close:function() {
          self.reset();
          Pop.close(true);
          self.callback && self.callback();
        } 
      }
    })
  }
}