/**
 * Browser Pop
 * Global static
 */
Browser = {
  callback:null,
  /*
   * - callback: optional
   */
  pop:function(callback) {
    this.callback = callback;
    overlayWorking(true);
    Includer.get(Includer.HTML_BROWSER, function() {
      overlayWorking(false);
      showOverlayPop('pop-bro');
    });
  },
  pClose:function() {
    if (this.callback) 
      this.callback();
    closeOverlayPop();
  }
};

