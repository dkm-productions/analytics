/**
 * Message Page
 */
MessagePage = page = {
  pageTile:null,
  workingTile:null,
  newThreadTile:null,
  newPostTile:null,
  postsTile:null,
  clientTile:null,
  thread:null,
  facesheet:null,
  state:null, // page state
  _PS_LOADING:0,
  _PS_SHOW_THREAD:1,
  _PS_REPLY:2,
  _PS_SAVING_REPLY:3,
  _PS_COMPLETE:4,
  _PS_QUICK_COMPLETE:5,
  _PS_NEW_THREAD:6,
  /*
   * Loader
   */
  load:function(query, dao) {
    RecipPop.load(dao.recips, Lu_Recips);
    Page.setEvents();
    this._loadTiles(dao);
    if (query.id)
      this.getThread(query.id, query.ob);
    else
      this.newThread(query);
  },
  pNewPost:function() {
    this._new(MessagePage._PS_REPLY);
  },
  _new:function(state) {
    page._loadMedPopHistory();
    page._setState(state);
  },
  pCancel:function() {
    var self = this;
    Pop.Confirm.showYesNo('Are you sure you want to cancel this post?', function() {
      if (self.state == MessagePage._PS_NEW_THREAD) {
        Html.Window.working(true);
        self._goInbox();
      } else {
        self.newPostTile.reset();
        self._setState(MessagePage._PS_SHOW_THREAD);
      }
    });
  },
  pSend:function(date) {
    Page.workingCmd(true);
    var self = this;
    if (this.state == MessagePage._PS_REPLY) {
      var post = this._getValidatedPost();
      if (post) {
        post.dateActive = date;
        self._setState(MessagePage._PS_SAVING_REPLY);
        Ajax.post(Ajax.SVR_MSG, 'reply', post, function() {
          self._goInbox();
        });
      }
    } else {
      var thread = this._getValidatedThread();
      if (thread) {
        thread.dateActive = date;
        this._setState(MessagePage._PS_SAVING_REPLY);
        Ajax.post(Ajax.SVR_MSG, 'newThread', thread, function() {
          self._goInbox();
        });
      }
    }
  },
  pComplete:function() {
    Page.workingCmd(true);
    if (this.state == MessagePage._PS_NEW_THREAD) {
      page._setState(MessagePage._PS_QUICK_COMPLETE);
      var thread = this._getValidatedThread();
      var self = this;
      if (thread) {
        this._setState(MessagePage._PS_SAVING_REPLY);
        Ajax.post(Ajax.SVR_MSG, 'quickComplete', thread, function() {
          self._goFacesheet(thread.cid);
        });
      } else {
        page._setState(MessagePage._PS_NEW_THREAD);
      }
    } else {
      page._setState(MessagePage._PS_COMPLETE);
      var self = this;
      var post = self.newPostTile.getRecord();
      self._setState(MessagePage._PS_SAVING_REPLY);
      Ajax.post(Ajax.SVR_MSG, 'complete', post, function() {
        self._goInbox();
      });
    }
  },
  pClearClient:function() {
    this.clientTile.clearClient();
    this.setFacesheet(null);
  },
  pEditClient:function(popEdit) {
    this.clientTile.editClient(popEdit);
  },
  pEditMeds:function() {
    this.clientTile.editMeds();
  },
  setFacesheet:function(fs) {
    page.facesheet = fs;
    this.newPostTile.loadFacesheet(fs);
    this.newThreadTile.loadFacesheet(fs);
    page._loadMedPopHistory();
  },
  getThread:function(mtid, forUserId) {
    page._setState(MessagePage._PS_LOADING);
    MsgThread.ajax().fetch(mtid, forUserId, function(thread) {
      page.reset();
      if (thread == null) {
        page._goInbox();
      } else {
        Header.Mail.setUnread(thread._unreadCt);
        page.thread = thread;
        page.setFacesheet(thread.facesheet);
        page._consolidateData();
        page._loadMedPopHistory();
        page._setTitle(thread.subject, thread._closed);
        page._setTag(thread);
        page.clientTile.loadThread(thread);
        page.newPostTile.loadThread(thread);
        page.postsTile.load(thread);
        if (thread._closed) // || (thread.MsgInbox && thread.MsgInbox.isRead == C_MsgInbox.IS_SENT))
          page._setState(MessagePage._PS_SHOW_THREAD);
        else
          page.pNewPost();
      }
    })
  },
  newThread:function(query) {
    this.reset(query);
    this.clientTile.showAdd();
    this._setTitle('New Message');
    page._loadMedPopHistory();
    this._setState(MessagePage._PS_NEW_THREAD);
  },
  _loadMedPopHistory:function() {
    if (page.facesheet && window.loadMedHistory) {
      loadMedHistory(page.facesheet.meds);
    }
  },
  medsChangedCallback:function(facesheet) {
    this.clientTile.medsChangedCallback(facesheet);
  },
  reset:function(query) {
    this._setState(MessagePage._PS_LOADING);
    this.thread = null;
    this.facesheet = null;
    this.syncs = null;
    this.newThreadTile.reset(query);
    this.newPostTile.reset(query);
    this.postsTile.reset();
    this.clientTile.reset(query);
    this._setTitle();
  },
  _loadTiles:function(dao) {
    this.workingTile = new Tile('message-working');
    this.pageTile = new Tile('thread');
    this.newThreadTile = NewThreadTile.getInstance();
    this.newPostTile = NewPostTile.getInstance(dao);
    this.postsTile = PostsTile.getInstance();
    this.clientTile = ClientTile.getInstance();
    this.pageTile.div.style.display = 'none';
    this.pageTile.div.style.visibility = '';
  },
  _setState:function(state) {
    if (this.state != state) {
      this.state = state;
      switch (state) {
      case MessagePage._PS_LOADING:
        this.pageTile.show(false);
        this.workingTile.show(true);
        Html.Window.working(true, this.workingTile.div);
        break;
      default:
        Html.Window.working(false);
        this.pageTile.show(true);
        this.workingTile.show(false);
        switch (state) {
        case MessagePage._PS_SHOW_THREAD:
          this.newThreadTile.show(false);
          this.newPostTile.show(false);
          this.postsTile.show(true);
          break;
        case MessagePage._PS_NEW_THREAD:
          this.newThreadTile.show(true);
          this.newPostTile.show(true);
          this.postsTile.show(false);
          _$('cmd-send').show();
          _$('cmd-save-complete').hide();
          break;
        case MessagePage._PS_REPLY:
          this.newThreadTile.show(false);
          this.newPostTile.show(true);
          this.postsTile.show(true)
          _$('cmd-send').show();
          _$('cmd-save-complete').hide();
          break;
        case MessagePage._PS_COMPLETE:
        case MessagePage._PS_QUICK_COMPLETE:
          this.newThreadTile.show(false);
          this.newPostTile.show(true);
          this.postsTile.show(true);
          _$('cmd-send').hide();
          show('cmd-save-complete');
          break;
        case MessagePage._PS_SAVING_REPLY:
          Html.Window.working(true, _$('new-post'));
          break;
        }
      }
    }
  },
  onFirstTemplateWindowAdd:function(w) {
    this.newThreadTile.defaultSubject(w.caption);
  },
  _getValidatedPost:function() {
    var post;
    try {
      post = this.newPostTile.getRecord();
    } catch (e) {
      Page.workingCmd(false);
      Page.showError(e);
      post = null;
    }
    return post;
  },
  _getValidatedThread:function() {
    var thread;
    try {
      thread = this.newThreadTile.getRecord();
      mergeInto(thread, this.newPostTile.getRecord());
      mergeInto(thread, this.clientTile.getRecord());
    } catch (e) {
      Page.workingCmd(false);
      Page.showError(e);
      thread = null;
    }
    return thread;
  },
  _goInbox:function() {
    Page.Nav.goMessages();
  },
  _goFacesheet:function(cid) {
    Page.Nav.goFacesheet(cid)
  },
  _consolidateData:function() { // create consolidate thread.data from
    // individual [post.data]
    var thread = this.thread;
    thread.data = {
      'qsyncs':{}, // {qsid:[seltext,..],..}
      'osyncs':{}, // {osid:1,..} // only 'on' osyncs
      'dsyncs':{}
    };
    for ( var i = 0; i < thread.MsgPosts.length; i++) {
      var post = thread.MsgPosts[i];
      if (post.data) {
        var data = eval(post.data);
        for ( var j = 0; j < data.length; j++) {
          var syncs = data[j].syncs;
          this._consolidateQsyncs(syncs.qsyncs);
          this._consolidateOsyncs(syncs.osyncs);
          this._consolidateDsyncs(syncs.dsyncs, j);
        }
      }
    }
  },
  _consolidateQsyncs:function(qsyncs) {
    var cache = this.thread.data.qsyncs;
    if (qsyncs) {
      for ( var qsid in qsyncs) {
        if (cache[qsid] == null) {
          cache[qsid] = qsyncs[qsid];
        }
      }
    }
  },
  _consolidateOsyncs:function(osyncs) {
    var cache = this.thread.data.osyncs;
    if (osyncs) {
      for ( var i = 0; i < osyncs.length; i++) {
        var osync = osyncs[i];
        if (cache[osync] == null) {
          cache[osync] = 1;
        }
      }
    }
  },
  _consolidateDsyncs:function(dsyncs, j) { // j=tui instance
    var cache = this.thread.data.dsyncs;
    if (dsyncs) {
      for ( var dsynci in dsyncs) {
        var dsyncij = dsynci + '@' + j;
        cache[dsyncij] = dsyncs[dsynci];
      }
    }
  },
  _setTag:function(thread) {
    var h = [];
    h.push('Started by <b>');
    h.push(thread.creator);
    h.push('</b>: ');
    h.push(thread.dateCreated);
    if (thread._closed) {
      // h.push('<br>Closed by <b>');
      // h.push(thread.closedBy); TODO
      // h.push('</b>: ');
      // h.push(thread.dateClosed); TODO
    }
    _$('thread-head-tag').html(h.join(''));
  },
  _setTitle:function(text, closed) {
    if (text) {
      _$('h2').setText(text);
    } else {
      _$('h2').html('&nbsp');
    }
    h2.className = (closed) ? 'closed' : 'thread';
  }
};
/**
 * Posts Tile Singleton: getInstance()
 */
var PostsTile = {
  authors:null,
  div:null,
  topPost:null,
  getInstance:function() {
    this.div = _$('posts');
    this.div.maxw = this.div.getWidth() - 40
    return this;
  },
  load:function(thread) {
    this.authors = [];
    this.reset();
    for ( var i = 0; i < thread.MsgPosts.length; i++) {
      var post = this._createPost(thread.MsgPosts[i]);
      if (i == 0) {
        this.topPost = post;
      }
      this.div.appendChild(post);
    }
  },
  fadeTopPost:function() {
    fade(this.topPost);
  },
  reset:function() {
    Tile.clear(this.div);
  },
  show:function(on) {
    Page.show(this.div, on);
  },
  _createPost:function(post) {
    var table = createTable();
    var tr = appendTr(table);
    var th = createTh(null, 'r' + this._getAuthorIx(post));
    th.appendChild(Html.Div.create('time').setText(post.dateCreated));
    th.appendChild(Html.Div.create().setText(post.author));
    if (post.action == C_MsgThread.POST_ACTION_CLOSE) {
      th.appendChild(Html.Span.create('lock', 'Closed'));
    } else {
      th.appendChild(Html.Span.create(null, this._splitRecips(post.sendTo)));
    }
    tr.appendChild(th);
    tr = appendTr(table);
    tr.appendChild(createTdHtml(post.body));
    if (post.Stub) {
      tr = appendTr(table);
      tr.appendChild(createTdAppend(null, this.Attachment.create(DocStub.revive(post.Stub))));
    }
    if (post.portalFile) {
      tr = appendTr(table);
      tr.appendChild(createTdAppend(null, Html.Image.create(null, 'portal-image.php?id=' + post.portalFile + '&w=' + this.div.maxw)));
    }
    var div = Html.Div.create('post').add(table);
    return div;
  },
  _splitRecips:function(sendTo) {
    return (sendTo) ? sendTo.split(';').join(', ') : '';
  },
  _getAuthorIx:function(post) {
    for ( var i = 0; i < this.authors.length; i++) {
      if (this.authors[i] == post.author) {
        return i;
      }
    }
    this.authors.push(post.author);
    return i;
  },
  Attachment:{
    create:function(stub) {
      return AnchorStubAttach.create(stub);
    }
  }
};
/**
 * New Thread Tile Singleton: getInstance()
 */
var NewThreadTile = {
  //
  entryForm:null,
  F_SUBJECT:'subject',
  F_PRIORITY:'priority',
  getInstance:function() {
    this.entryForm = this._loadEntryForm(C_MsgThread.PRIORITIES);
    return this;
  },
  reset:function(query) {
    this.entryForm.reset();
    if (query) {
      if (query.aname && query.subject == null)
        query.subject = query.aname;
      this.entryForm.setValue(this.F_SUBJECT, query.subject);
      this.entryForm.setValue(this.F_PRIORITY, query.priority);
    }
  },
  loadFacesheet:function(fs) {
  },
  show:function(on) {
    _$('new-thread').showIf(on);
    this.entryForm.focus('subject');
  },
  /*
   * Returns {'subject':subject,'priority':priority}
   */
  getRecord:function() {
    var rec = this.entryForm.getRecord();
    if (rec.subject.length == 0) { 
      page._focus = this.entryForm.getField(this.F_SUBJECT);
      this.entryForm.getField(this.F_SUBJECT).focus();
      throw new Error('Subject cannot be blank.');
    }
    return rec;
  },
  defaultSubject:function(text) {
    if (this.entryForm.getValue(this.F_SUBJECT) == '') {
      this.entryForm.setValue(this.F_SUBJECT, text);
    }
  },
  _loadEntryForm:function(priorities) {
    var ef = new EntryForm(_$('new-thread-ul'));
    ef.li();
    ef.appendTextbox(this.F_SUBJECT, 30, 'Subject', '');
    ef.appendField(this.F_PRIORITY, Html.Select.create(priorities), 'Priority');
    return ef;
  }
};
/**
 * New Post Tile Singleton: getInstance()
 */
var NewPostTile = {
  entryForm:null,
  templateWindows:null,
  free:null,
  thread:null,
  F_TO:'to',
  getInstance:function(dao) {
    this.sendTile = this.SendTile.create(dao.recips)
      .bubble('onsend', page.pSend.bind(page))
      .bubble('onsendlater', this.sendTile_onsendlater);
    this.templateWindows = TemplateWindows.getInstance(dao.sections);
    this.free = Html.Input.$('post-free');
    this.attachment = NewPostTile.Attachment.create(_$('post-attach'));  //.bubble('onpatient', self);
    return this;
  },
  reset:function(query) {
    this.sendTile.reset();
    this.templateWindows.reset();
    this.free.setValue('');
    this.thread = null;
    this.attachment.reset();
    if (query) {
      if (query.to)
        this.sendTile.setOfficeRecips(Json.decode(query.to));
      else if (query.portal)
        this.sendTile.setPatientRecip();
      if (query.pids) {
        var pids = Json.decode(query.pids);
        this.templateWindows.add(pids);
      }
      if (query.aid) {
        this.attachment.load(this._getStub(query));
      }
    }
  },
  _getStub:function(query) {
    return DocStub.revive({
      'type':String.toInt(query.atype),
      'id':query.aid,
      'name':query.aname
    });
  },
  sendTile_onsendlater:function(date) {
    page.pSend.call(page, date);
  },
  loadFacesheet:function(fs) {
    this.sendTile.load(fs);
    this.attachment.loadFacesheet(fs);
  },
  loadThread:function(thread) {
    this.thread = thread;
    this.templateWindows.loadThread(thread);
    this.defaultRecips(thread);
  },
  defaultRecips:function(thread) {
    if (thread.isPortal()) {
      this.sendTile.setPatientRecip();
    } else {
      var post = thread.getLastPost();
      if (post && post.authorId != me.userId)
        this.sendTile.setOfficeRecips([ post.authorId ]);
    }
  },
  /*
   * Returns { 'id':mtid, // if reply 'to':[id,..],
   * 'data':'[{'pid':pid,'syncs':syncs},..]', // serialized; see
   * TemplateUi.getSyncValues 'html':html }
   */
  getRecord:function() {
    var efr = this.sendTile.getRecord();
    var to = efr.to || [];
    var puser = efr.portalUserId;
    var email = efr.cc;
    var blank = this.templateWindows.isBlank();
    var dataOut = this.templateWindows.getDataOut();
    var freeText = this.free.getValue();
    var stub = this.attachment.getStub();
    if (freeText != '')
      dataOut.out.push(freeText);
    var html = '<p>' + dataOut.out.join('</p><p>') + '</p>';
    if (page.state != MessagePage._PS_COMPLETE && page.state != MessagePage._PS_QUICK_COMPLETE) {
      if (to.length == 0 && puser == null) {
        this.sendTile.popRecips();
        throw new Error('At least one recipient must be selected.');
      }
      if (dataOut.out.length == 0) {
        this.free.setFocus();
        throw new Error('Message cannot be blank.');
      }
      if (blank) {
        throw new Error("Massage cannot be sent until all unanswered entries (in red) are completed.")
      }
    } else {
      if (dataOut.out.length == 0)
        html = '(No message)';
    }
    var rec = {
      'to':to,
      'portalUserId':puser,
      'data':dataOut.data,
      'stub':stub,
      'html':html,
      'email':email
    };
    if (this.thread)
      rec.id = this.thread.threadId;
    return rec;
  },
  show:function(on) {
    _$('new-post').showIf(on);
    this.sendTile.show(page.state != MessagePage._PS_COMPLETE);
    if (on && page.state != MessagePage._PS_NEW_THREAD && page.state != MessagePage._PS_QUICK_COMPLETE)
      focus('post-free');
  },
  SendTile:{
    create:function(recips) {
      var My = this;
      container = _$('send-tile');
      var self = Html.Tile.create(container);
      return self.aug({
        onsend:function() {},
        onsendlater:function(date) {},
        //
        init:function() {
          //
          self.type = My.TypeCombo.create(self).setClass('mr5').bubble('onchange', self.draw);
          self.tiles = Html.Tiles.create(self, [ 
            self.recips = My.RecipsForm.create(recips), 
            self.portal = My.PortalTile.create(self)]);
          Html.TableCol.create(self, [self.type, self.tiles]).addClass('vtop');
          self.notify = Html.UlEntry.create(self).extend(My.NotifyForm);
          self.cb = Html.CmdBar.create(self)
            .button('Send Now', Function.defer(self, 'onsend'), 'send')
            .lbl('or')
            .button('Send Later...', self.later_onclick, 'calendar cmdnone');
        },
        load:function(fs) {
          self.portal.load(fs);
          self.notify.load(fs);
        },
        draw:function() {
          if (self.type.isOffice())
            self.recips.select();
          else
            self.portal.select();
          self.notify.visibleIf(! self.type.isOffice());
        },
        reset:function() {
          self.type.reset();
          self.recips.reset();
          self.portal.reset();
          self.draw();
        },
        setPatientRecip:function() {
          self.type.setPatient();
          self.draw();
        },
        setOfficeRecips:function(to) {
          self.type.setOffice();
          self.recips.setRecips(to);
        },
        popRecips:function() {
          if (self.type.isOffice())
            self.recips.pop();
        },
        getRecord:function() {
          var rec = self.tiles.selected().getRecord();
          if (! self.type.isOffice())
            rec.cc = self.notify.getCc();
          return rec;
        },
        later_onclick:function() {
          SendLaterPop.pop(function(date) {
            self.onsendlater(date);
          })
        }
      })
    },
    NotifyForm:function(self) {
      return {
        init:function() {
          self.line().check('send', 'Send email notification', self.send_oncheck).textbox('email', 30);
          self.draw();
        },
        getCc:function() {
          if (self.getValue('send'))
            return self.getValue('email');
        },
        //
        onbeforeload:function(fs) {
          var rec = fs && fs.portalUser;
          if (rec && rec.email)
            rec.send = true;
          return rec;
        },
        send_oncheck:function(lc) {
          self.draw();
          if (lc.isChecked())
            self.focus('email');
        },
        draw:function() {
          self.showIf(self.rec);
          self.$('email').visibleIf(self.getValue('send'));
        }
      }
    },
    TypeCombo:{
      create:function(container) {
        var opts = {
          '1':'To Office',
          '2':'To Patient'
        };
        var self = Html.Select.create(opts).into(container);
        return self.aug({
          reset:function() {
            self.setOffice();
          },
          setOffice:function() {
            self.setValue('1');
          },
          setPatient:function() {
            self.setValue('2');
          },
          isOffice:function() {
            return self.getValue() == '1';
          },
          isPatient:function() {
            return self.getValue() == '2';
          }
        })
      }
    },
    RecipsForm_old:{
      create:function(container, recips) {
        var ef = Html.EntryForm.create(container);
        var self = ef.ul;
        return self.aug({
          init:function() {
            var at = new AnchorTab('Select Recipient(s)', 'recips');
            at.loadChecks(recips, 'userId', 'name', null, null, null, 3);
            at.appendCmd();
            ef.li();
            ef.appendAnchorTab('to', at);
          },
          setRecips:function(recips) {
            ef.setValue('to', recips);
          },
          pop:function() {
            ef.getField('to').pop();
          },
          getRecord:function() {
            return ef.getRecord();
          },
          reset:function() {
            ef.reset();
          }
        })
      }
    },
    RecipsForm:{
      create:function(/*User[]*/users) {
        return Html.AnchorAction.create('client mt5').extend(function(self) {
          return {
            init:function() {
              self.reset();
            },
            setRecips:function(/*int[]*/recips) {
              var names;
              if (! Array.isEmpty(recips))
                names = RecipPop.joinNames(recips);
              if (! String.isBlank(names))
                self.setText(names).removeClass('red');
              else
                self.setText('Select Recipient(s)').addClass('red');
              self.recips = recips;
            },
            pop:function() {
              RecipPop.pop(self.recips, self.setRecips);
            },
            getRecord:function() {
              return {'to':self.recips};
            },
            reset:function() {
              self.setRecips(null);
            },
            //
            onclick:function() {
              self.pop();
            }
          }
        })
      }
    },
    PortalTile:{
      create:function(container) {
        return PortalAnchorTile.create(container).addClass('mt5').extend(function(self) {
          return {
            init:function() {
              self.selector = Html.AnchorAction.create('client', 'Select a Patient').bubble('onclick', self.selector_onclick);
              self.tiles.add(self.selector);
            },
            getRecord:function() {
              if (self.portalUser == null)
                throw new Error('Portal ID is required.');
              return self.portalUser;
            },
            //
            draw:function() {
              if (self.client == null)
                self.selector.select();
              else if (self.portalUser)
                self.login.select();
              else
                self.creator.select();
            },
            selector_onclick:function() {
              ClientTile.selectClient();
            }
          }
        })
      }
    }
  },
  Attachment:{
    create:function(container) {
      return Html.Tile.create(container, 'Attachment').extend(function(self) {
        return {
          init:function() {
            self.addLink = Html.AnchorAction.asAttach('Attach...', self.anchorAdd_onclick).into(self);
            self.label = Html.Label.create(null, 'Attachment:').into(self);
            self.stubBox = Html.Span.create().into(self);
            self.reset();
          },
          reset:function(fs) {
            self.fs = fs;
            self.stub = null;
            self.stubBox.clean();
            self.draw();
          },
          loadFacesheet:function(fs) {
            self.fs = fs;
            self.draw();
          },
          getStub:function() {
            return self.stub;
          },
          load:function(stub) {
            self.stub = stub;
            AnchorStubAttach.create(stub).into(self.stubBox).bubble('ondetach', self.detach_onclick);
            self.draw();
          },
          draw:function() {
            self.addClassIf('attached', self.stub);
            self.addLink.showIf(self.stub == null);
            self.label.showIf(self.stub);
            self.stubBox.showIf(self.stub);
            // self.showIf(self.fs);
          },
          anchorAdd_onclick:function() {
            if (self.fs) {
              DocHistoryPop.pop(self.fs, null, function(stub) {
                AttachPreviewPop.pop_forAttach(stub, function() {
                  DocHistoryPop.close();
                  self.load(stub);
                })
              })
            } else {
              ClientTile.selectClient(function() {
                self.anchorAdd_onclick();
              })
            }
          },
          detach_onclick:function() {
            self.reset(self.fs);
          }
        }
      })
    }
  }
}
/**
 * Template Windows Singleton: getInstance()
 */
var TemplateWindows = {
  sections:null,
  container:null,
  atabs:null,
  div:null,
  pars:null,
  thread:null,
  getInstance:function(sections) {
    this.sections = sections;
    this.container = _$('templates');
    this.atabs = _$('tchooser-atabs');
    this.div = _$('tuis');
    this.pars = {}; // {pid:{'desc':pdesc,'pi':JParInfo,'tuis':[tuis,..]},..}
    this.loadTemplateChooser();
    this.reset();
    return this;
  },
  reset:function() {
    Tile.clear(this.div);
    this.thread = page.thread;
  },
  loadThread:function(thread) {
    this.thread = thread;
  },
  /*
   * Returns { 'data':"[{'pid':pid,'syncs':syncs},..]", // serialized; see
   * TemplateUi.getSyncValues 'out':[html,..] // see TemplateUi.out }
   */
  getDataOut:function() {
    var dataOut = {
      'data':null,
      'out':[]
    };
    var windows = this.getWindows();
    if (windows.length > 0) {
      var data = [];
      var out = [];
      for ( var i = 0; i < windows.length; i++) {
        var w = windows[i];
        data.push({
          'pid':w.pid,
          'syncs':w.tui.getSyncValues(true)
        });
        out.push(w.tui.out());
      }
      dataOut.data = Json.encode(data);
      dataOut.out = out;
    }
    return dataOut;
  },
  isBlank:function() {
    var windows = this.getWindows();
    for (var i = 0; i < windows.length; i++) {
      if (windows[i].tui.isBlank())
        return true;
    }
  },
  /*
   * Returns [<div>,..]
   */
  getWindows:function() {
    return this.div.children;
  },
  loadTemplateChooser:function() {
    var self = this;
    for ( var sid in this.sections) {
      var s = this.sections[sid];
      var at = new AnchorTab(s.name, 'templates');
      at.loadChecks(s.ParMsgs, 'parId', 'desc', AnchorTab.SEL_TEXT_AS_NONE, null, false);
      at.appendCmd(null, function(atab) {
        self.templateOk(atab)
      }, 'Insert');
      this.atabs.appendChild(at.anchor);
      for ( var pid in s.ParMsgs) {
        this.pars[pid] = {
          'desc':s.ParMsgs[pid].desc
        };
      }
    }
  },
  templateOk:function(atab) {
    var pids = atab.getValue();
    atab.resetChecks();
    this.add(pids);
  },
  add:function(pids) {
    var wc = this.getWindows().length;
    for ( var i = 0; i < pids.length; i++) {
      var pid = pids[i];
      var w = this.createTuiWindow(pid);
      this.div.appendChild(w);
      if (wc == 0 && i == 0) {
        page.onFirstTemplateWindowAdd(w);
      }
    }
  },
  createTuiWindow:function(pid) {
    var par = this.pars[pid];
    var window = Html.Div.create('post-entry');
    var caption = par.desc;
    window.appendChild(this.createTuiCap(caption));
    var tui;
    if (this.thread) {
      tui = new TemplateUi(null, this.thread.facesheet, this.thread.data, pid);
    } else {
      tui = new TemplateUi(null, page.facesheet, null, pid);
    }
    window.appendChild(tui.doc);
    window.tui = tui;
    window.pid = pid;
    window.caption = caption;
    return window;
  },
  createTuiCap:function(caption) {
    var self = this;
    var t = createTable();
    var tr = appendTr(t);
    tr.appendChild(createTh(caption));
    var a = createAnchor(null, null, null, 'X', null, function() {
      self.closeTui(this)
    });
    var td = createTd();
    td.appendChild(a);
    tr.appendChild(td);
    return Html.Div.create('pcap').add(t);
  },
  closeTui:function(a) {
    var div = findAncestorWith(a, 'className', 'post-entry');
    Pop.Confirm.showDelete('template section', function(confirmed) {
      if (confirmed) {
        TemplateUi.clearInstance(div.tui);
        deflate(div);
      }
    });
  }
};
/**
 * ClientTile Singleton: getInstance()
 */
ClientTile = {
  td:null,
  facesheet:null,
  client:null,
  addTile:null,
  entryForm:null,
  existingTile:null,
  _state:null, // tile state
  _TS_HIDDEN:0,
  _TS_ADD:1,
  _TS_ADD_LOADING:2,
  _TS_ADD_VERIFY:3,
  _TS_EXISTING:4,
  getInstance:function() {
    this.td = _$('td-client');
    this.addTile = new Tile('client-add');
    this.entryForm = this._loadEntryForm();
    this.existingTile = new Tile('client-existing');
    this._setState(ClientTile._TS_HIDDEN);
    return this;
  },
  reset:function(query) {
    this.facesheet = null;
    this.client = null;
    this.entryForm.reset();
    if (query && query.cid) {
      this.loadClient(query.cid);
    }
  },
  getRecord:function() {
    var cid = (this.facesheet && this.facesheet.client) ? this.facesheet.client.clientId : null;
    if (page.state == MessagePage._PS_QUICK_COMPLETE && cid == null)
      throw new Error('No patient was selected.');
    return {
      'cid':cid
    };
  },
  clearClient:function() {
    this.reset();
    this._setState(ClientTile._TS_ADD);
  },
  selectClient:function(callback) {
    this.client_callback = callback;
    this.entryForm.clientAnchor.click();
  },
  _setState:function(state) {
    this.state = state;
    switch (state) {
    case ClientTile._TS_HIDDEN:
      Page.show(this.td, false);
      break;
    case ClientTile._TS_ADD:
      Page.show(this.td, true);
      this.addTile.show(true);
      _$('client-clear-a').hide();
      _$('client-edit-a').hide();
      this.existingTile.show(false);
      break;
    case ClientTile._TS_ADD_LOADING:
      this.addTile.working(true);
      break;
    case ClientTile._TS_ADD_VERIFY:
      this.addTile.working(false);
      Page.show(this.td, true);
      this.addTile.show(false);
      _$('client-clear-a').show();
      _$('client-edit-a').hide();
      this.existingTile.show(true);
      break;
    case ClientTile._TS_EXISTING:
      Page.show(this.td, true);
      this.addTile.show(false);
      _$('client-clear-a').hide();
      _$('client-edit-a').show();
      this.existingTile.show(true);
      break;
    }
  },
  showAdd:function() {
    this._setState(ClientTile._TS_ADD);
  },
  loadThread:function(thread) {
    if (thread.clientId) {
      this._loadFacesheet(thread.facesheet);
      this._setState(ClientTile._TS_EXISTING);
    } else {
      this._setState(ClientTile._TS_HIDDEN);
    }
  },
  loadClient:function(cid) {
    if (cid) {
      this._setState(ClientTile._TS_ADD_LOADING);
      var self = this;
      MsgFacesheet.ajax().fetch(cid, function(facesheet) {
        self._loadFacesheet(facesheet);
        self._setState(ClientTile._TS_ADD_VERIFY);
        if (self.client_callback)
          self.client_callback();
      });
    }
  },
  editClient:function(popEdit) {
    var self = this;
    Includer.get(Includer.PATIENT_EDITOR, function() {
      PatientEditor.pop(self.client, popEdit, self);
    });
  },
  patientEditorCallback:function(client) {
    this.facesheet.client = client;
    this._loadFacesheet(this.facesheet);
  },
  editMeds:function() {
    Includer.getFaceMeds_pop(this.facesheet);
  },
  medsChangedCallback:function(facesheet) {
    this.facesheet.meds = facesheet.meds;
    this.facesheet.activeMeds = facesheet.activeMeds;
    this.facesheet.medsHistByMed = facesheet.medsHistByMed;
    this.facesheet.medsHistByDate = facesheet.medsHistByDate;
    this._loadFacesheet(this.facesheet);
  },
  _loadFacesheet:function(facesheet) {
    if (facesheet) {
      var c = facesheet.client;
      _$('h2-client').setText(c.name).className = c.sex;
      _$('client-uid').setText(c.uid);
      _$('client-dob').setText(c.birth + ' (' + c.age + ')');
      this._formatAddress('client-contact', c.Address_Home);
      this._formatAddress('client-emer', c.Address_Emergency);
      this._formatAddress('client-rx', c.Address_Rx);
      _$('allergies').html(this._joinData(Array.filterOn(facesheet.allergies, 'active', 1), 'agent', ' &bull; '));
      _$('meds').html(this._joinData(facesheet.activeMeds, 'name', '<br>'));
      this._formatVitals(facesheet.vitals);
      var links = _$('client-links').clean();
      var self = this;
      var a = Html.Anchor.create('gogo', 'Patient Facesheet', function() {
        Page.popFace(c.clientId, ClientTile.loadClient.bind(self, c.clientId));
      })
      //var a = createAnchor(null, 'javascript:Page.popFace(' + c.clientId + ',ClientTile.loadClient(' + c.clientId + '))', 'gogo', 'Patient Facesheet');
      links.appendChild(a);
      this.client = c;
      NewCrop.loadFromMsg(me.isErx(), c);
      QPopLegacyMed.loadMedHistory(facesheet.meds);
    }
    this.facesheet = facesheet;
    page.setFacesheet(facesheet);
  },
  _formatAddress:function(id, a) {
    var e = _$(id);
    var h = [];
    h.push(a.addr1);
    h.push(a.addr2);
    h.push(a.csz);
    if (a && a.name) {
      h.unshift(a.name);
    }
    h.push(AddressUi.formatPhone(a.phone1, a.phone1Type));
    h.push(AddressUi.formatPhone(a.phone2, a.phone2Type));
    var s = h.filter().join('<br>');
    if (s == '') {
      e.innerHTML = '[None on file]';
      e.style.color = 'red';
    } else {
      e.innerHTML = s;
      e.style.color = '';
    }
  },
  _loadEntryForm:function() {
    var ef = new EntryForm(_$('client-add-ul'));
    ef.li();
    var a = createAnchor(null, null, 'client', 'Select a Patient');
    ef.appendClientPicker('cid', a);
    var self = this;
    ef.setOnChange(function(value) {
      self.loadClient(value)
    });
    ef.clientAnchor = a;
    return ef;
  },
  _formatVitals:function(vitals) {
    var v = null;
    for ( var d in vitals) {
      v = vitals[d];
      break;
    }
    if (v && v.all) {
      _$('h3-vitals').setText('Vitals (' + v.date.substr(0, 6) + ')');
      _$('vitals').html(bulletJoin(v.all));
    } else {
      _$('h3-vitals').setText('Vitals');
      _$('vitals').html('');
    }
  },
  _joinData:function(a, field, glue) {
    var text;
    if (a) {
      var v = [];
      for ( var i = 0; i < a.length; i++) {
        v.push(a[i][field]);
      }
      text = v.join(glue);
    } else {
      text = '(None)';
    }
    return text;
  }
}
SendLaterPop = {
  pop:function(callback) {
    return Html.Pop.singleton_pop.apply(this, arguments);
  },
  create:function() {
    return Html.Pop.create('Send Later').extend(function(self) {
      return {
        POP_POS:Pop.POS_CURSOR,
        //
        init:function() {
          self.Form = Html.UlEntry.create(self.content, function(ef) {
            ef.line().lbl('Date to Send').date('date');
          })
          Html.CmdBar.create(self.content).ok(self.ok_onclick).cancel(self.close);
        },
        onshow:function(callback) {
          self.callback = callback;
          if (self.getValue() == null) 
            self.Form.getField('date').dateInput.pop();
        },
        getValue:function() {
          return String.nullify(self.Form.getValue('date'));
        },
        //
        ok_onclick:function() {
          var value = self.getValue();
          if (value) {
            self.close();
            self.callback(value);
          } else {
            Pop.Msg.showCritical('Please supply a date to send.');
          }
        }
      }
    })
  } 
}
RecipPop = {
  load:function(/*User[]*/users, /*int[]*/defaults) {
    RecipPop.users = Map.from(users, 'userId');
    Users_P.load(users);
    RecipPop.loadDefaults(defaults);
  },
  loadDefaults:function(defaults) {
    RecipPop.defaults = defaults || [];
  },
  pop:function(/*int[]*/checked, callback/*int[]*/) {
    return Html.Pop.singleton_pop.apply(RecipPop, arguments);
  },
  joinNames:function(ids) {
    var names = [];
    ids.each(function(id) {
      if (RecipPop.users[id]) 
        names.push(RecipPop.users[id].name);
    })
    return names.join(', ');
  },
  create:function() {
    var My = this;
    return Html.Pop.create('Recipient Selector').extend(function(self) {
      return {
        POP_POS:Pop.POS_CURSOR,
        //
        init:function() {
          self.Recips = My.Recips.create(self.content)
            .bubble('onresize', self.reposition)
            .bubble('oncr', self.ok_onclick);
          Html.CmdBar.asOkCancel(self.content, self.ok_onclick, self.close);
        },
        onshow:function(checked, callback) {
          self.Recips.reset();
          self.callback = callback;
          RecipPop.defaults.each(function(id) {
            self.Recips.add(RecipPop.users[id]);
          })
          if (checked)
            self.setValue(checked);
          self.Recips.add();
        },
        setValue:function(/*int[]*/ids) {
          ids.each(function(id) {
            self.Recips.add(RecipPop.users[id], true);
          })
        },
        getValue:/*int[]*/function() {
          return self.Recips.getCheckedValues();
        },
        //
        ok_onclick:function() {
          self.close();
          self.saveDefaults();
          self.callback && self.callback(self.getValue());
        },
        saveDefaults:function() {
          var defaults = self.Recips.getValues();
          var after = Json.encode(defaults);
          var before = Json.encode(RecipPop.defaults);
          if (after != before) {
            Ajax.post('Lookup', 'saveRecips', after, Ajax.NO_CALLBACK);
            RecipPop.loadDefaults(defaults);
          }
        }
      }
    })
  },
  Recips:{
    create:function(container) {
      var My = this;
      return Html.Tile.create(Html.Pop.Frame.create(container).addClass('Recips'), 'rscroll').extend(function(self) {
        return {
          onresize:function() {}, 
          oncr:function() {},
          //
          reset:function(users) {
            self.map = {/*userId:Recip*/};
            self.clean();
            if (users)
              users.each(self.add);
          },
          getCheckedValues/*int[]*/:function() {
            var values = [];
            Map.each(self.map, function(Recip, id) {
              if (Recip.isChecked())
                values.push(id);
            })
            return values;
          },
          getValues:function() {
            return Map.keys(self.map);
          },
          del:function(user) {
            if (self.map[user.userId])
              delete self.map[user.userId];
          },
          add:function(user, checked) {
            if (user) {
              if (self.map[user.userId] == null) {
                var Recip = My.Recip(self, user, checked).bubble('ondel', self.del);
                self.map[user.userId] = Recip;
                user.Recip = Recip;
                self.onresize();
              } else {
                self.map[user.userId].check(checked);
              }
            } else {
              My.Recip(self).bubble('oncr', self, 'oncr').bubble('onset', function(user) {
                self.add(user, true);
                self.add();
              })
              self.onresize();
            }
          }
        }
      })
    },
    Recip:function(container, user, checked) {
      return Html.Tile.create(container, 'Recip').extend(function(self) {
        return {
          onset:function(user) {},
          ondel:function(user) {},
          oncr:function() {},
          //
          init:function() {
            self.CheckTile = Html.TableRow.create(self, 'CheckTile').tds(
              self.Check = Html.InputCheck.create()
                .bubble('onclick', self.Check_onclick),
              self.Anchor = Html.AnchorAction.create().setWidth(200)
                .bubble('onclick', self.Anchor_onclick),
              self.Delete = Html.Anchor.create('xdel')
                .bubble('onmouseover', self.Delete_onhover)
                .bubble('onmouseout', self.Delete_onhoverout)
                .bubble('onclick', self.Delete_onclick));
            self.UserTile = Html.Tile.create(self, 'UserTile');
            self.User = UserPicker.create().into(Html.Tile.create(self.UserTile))
              .bubble('onset', self.User_onset)
              .bubble('oncr', self);
            self.load(user);
            self.check(checked);
          },
          load:function(rec) {
            self.rec = rec;
            if (rec) {
              self.CheckTile.show();
              self.UserTile.hide();
              self.Anchor.setClass(AnchorUser.getClass(rec)).setText(rec.name);
            } else {
              self.CheckTile.hide();
              self.UserTile.show()
              self.User.setFocus();
            }
          },
          isChecked:function() {
            return self.rec && self.Check.isChecked();
          },
          check:function(b) {
            self.Check.setCheck(b);
            self.Check_onclick();
          },
          toggle:function() {
            self.check(! self.Check.isChecked());
          },
          //
          Anchor_onclick:function() {
            self.toggle();
          },
          Check_onclick:function() {
            self.addClassIf('run', ! self.Check.isChecked());
          },
          Delete_onclick:function() {
            Html.Animator.deflate(self.CheckTile, function() {
              self.ondel(user);
            })
          },
          Delete_onhover:function() {
            self.CheckTile.addClass('hover');
          },
          Delete_onhoverout:function() {
            self.CheckTile.removeClass('hover');
          },
          User_onset:function(rec) {
            if (rec) {
              self.hide();
              self.onset(rec);
            }
          }
        }
      })
    }
  }
}
//
User_P = Object.Rec.extend({
  // 
})
Users_P = Object.RecArray.of(User_P, {
  //
  ajax:function() {
    var self = this;
    return {
      fetchAll:function(callback) {
        self.ajax_fetchAll(null, callback);
      },
      fetchMatches:function(text, callback) {
        self.ajax_fetchMatches(this.fetchAll, text, callback);
      }
    }
  },
  load:function(users) {
    this.cache = Users_P.revive(users);
  }
})
UserInput = {
  create:function() {
    return Html.InputAutoComplete.create().extend(function(self) {
      return {
        //
        fetch:function(value, callback) {
          Users_P.ajax().fetchMatches(value, callback);
        }
      }
    })
  }
}
UserPicker = {
  create:function() {
    return Html.RecPicker.create(26, UserPickerPop, UserInput).extend(function(self) {
      return {
        oncr:function() {},
        //
        init:function() {
          self.input.onkeypresscr = function() {
            if (String.isBlank(self.input.getValue()))
              self.oncr();
          }
        },
        getValueFrom:function(rec) {
          return rec.userId;
        },
        getTextFrom:function(rec) {
          return rec.name;
        }
      }
    })
  }
}
UserPickerPop = {
  pop:function(value, text) {
    return Html.Pop.singleton_pop.apply(UserPickerPop, arguments);
  },
  create:function() {
    return Html.PickerPop.create('User Selector').extend(function(self) {
      return {
        table_fetch:function(callback_recs) {
          Users_P.ajax().fetchAll(callback_recs);
        },
        table_applies:function(rec, search) {
          return Users_P.isMatch(rec, search);
        },
        table_add:function(rec, tr) {
          tr.select(rec, AnchorUser.create(rec));
        }
      }
    })
  }
}
