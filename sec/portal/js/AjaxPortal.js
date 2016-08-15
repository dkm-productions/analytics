Ajax.Login = {
  _SVR:'Login',
  /*
   * @arg string id
   * @arg string pw
   * @arg fn(PortalSession) onsuccess
   */
  login:function(id, pw, onsuccess) {
    Ajax.postr(this._SVR, 'login', {'id':id,'pw':pw}, PortalSession, null, onsuccess, Ajax.Error);
  },
  /*
   * @arg string[] responses
   * @arg fn(PortalSession) onsuccess
   */
  respond:function(responses, onsuccess) {
    Ajax.postr(this._SVR, 'respond', responses, PortalSession, null, onsuccess, Ajax.Error);
  },
  /*
   * @arg string[] responses (new password + repeat)
   * @arg fn(PortalSession) onsuccess
   */
  setPassword:function(responses, onsuccess) {
    Ajax.postr(this._SVR, 'setPassword', responses, PortalSession, null, onsuccess, Ajax.Error);
  },
  /*
   * @arg fn(PortalSession) onsuccess
   */
  acceptTerms:function(onsuccess) {
    Ajax.postr(this._SVR, 'acceptTerms', null, PortalSession, null, onsuccess, Ajax.Error);
  }
}
Ajax.Facesheet = {
  _SVR:'Facesheet',
  /*
   * @arg fn(PortalFacesheet) onsuccess
   */
  get:function(onsuccess) {
    Ajax.getr(this._SVR, 'get', null, PortalFacesheet, null, onsuccess, Ajax.Error);
  }
}
Ajax.Messaging = {
  _SVR:'Messaging',
  /*
   * @arg fn(int) onsuccess
   */
  getMyUnreadCt:function(onsuccess) {
    Ajax.get(this._SVR, 'getMyUnreadCt', null, onsuccess, Ajax.Error);
  },
  /*
   * @arg fn(MsgThreads) onsuccess
   */
  getMyInboxThreads:function(onsuccess) {
    Ajax.getr(this._SVR, 'getMyInboxThreads', null, MsgThreads, null, onsuccess, Ajax.Error);
  },
  /*
   * @arg int mtid
   * @arg fn(MsgThread) onsuccess
   */
  openThread:function(mtid, onsuccess) {
    Ajax.getr(this._SVR, 'openThread', mtid, MsgThread, null, onsuccess, Ajax.Error);
  },
  /*
   * @arg int mtid
   * @arg int[] sendTos
   * @arg string body
   * @arg fn() onsuccess
   */
  postReply:function(mtid, sendTos, body, file, onsuccess) {
    Ajax.post(this._SVR, 'postReply', {'mtid':mtid,'sendTos':sendTos,'body':body,'file':file}, onsuccess, Ajax.Error);
  },
  /*
   * @arg string subject
   * @arg int[] sendTos
   * @arg string body
   * @arg fn() onsuccess
   */
  newThread:function(subject, sendTos, body, file, onsuccess) {
    Ajax.post(this._SVR, 'newThread', {'subject':subject,'sendTos':sendTos,'body':body,'file':file}, onsuccess, Ajax.Error);
  }
}
Ajax.Error = function(e) {
  Page.workingCmd(false);
  Html.Window.clearWorking();
  PopMsg.create().show(e.message);
}
