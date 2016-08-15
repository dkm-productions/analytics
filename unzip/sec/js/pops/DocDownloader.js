/**
 * Doc Downloader
 * Global static 
 * Requires: HTML_DOC_DOWNLOAD_FORM
 * 
 * @deprecated
 * - First attempt at componentizing engine-download.js
 * - HTML generation now done by DocFormatter.js
 */
var DocDownloader = {
  session:null,
  sigName:null,
  _pcustom:null,
  _tcustom:null,
  _tableInserted:null,
  /*
   * Download to Word
   */
  toWord:function(session, s, sigName, ptCustoms) {
    this.session = session;
    this.sigName = sigName;
    var pc = this._getPrintCustoms(ptCustoms);
    var d = "";
    var head = "";
    for (var si = 0; si < s.length; si++) {
      var secs = s[si].childNodes;
      var addendum = si > 0;
      if (secs) {
        for (var i = 0; i < secs.length; i++) {
          var sec = secs[i];
          var pars = sec.childNodes;
          var sig = (sec.id == "sig" || sec.id == "\\'sig\\'");
          if (addendum || sig) {
            if (sig) d += "\\f1\\fs18 ";  // arial on
            for (var j = 0; j < pars.length; j++) {
              d += this._spanText(pars[j]);
            }
            if (d.substring(d.length - 4, d.length) != "\\par") d += "\\par ";
            if (sig) d += "\\f0\\fs22 ";  // arial off
          } else {
            var suidchar1 = sec.uid.substring(0, 1);
            if (sec.className == "v" || suidchar1 == "@") {
              if (sec.uid == "@header") {
                head = this._parsText(pars, suidchar1);
                head = head.substring(0, head.length - 3);
              } else {
                d += this._parsText(pars, suidchar1);
              }
            }
          }
        }
      }
    }
    d = d.replace(/<b>/g, "\\b ");
    d = d.replace(/<\/b>/g, "\\b0 ");
    d = d.replace(/<u>/g, "\\ul ");
    d = d.replace(/<\/u>/g, "\\ulnone ");
      
    // Copy generated documented to form and submit
    var df = $('docForm');
    df.docText.value = d;
    df.docHead.value = head;
    df.docName.value = this.session.cname + "_" + this.session.id;
    df.docFmt.value = this._pcustom.fmtAsRtf ? "rtf" : "doc";
    df.docImg.value = denull(pc.logofile);
    df.docImg2.value = denull(pc.logofile2);
    df.docSigImg.value = denull(pc.sigfile);
    df.docNoTag.value = this._pcustom.noTag ? "1" : "0";
    df.docLeftHead.value = pc.leftHead ? "1" : "0";
    df.docSigName.value = this.sigName;
    if (pc.sigDos) {
      df.docSigName.value += "<br>" + this.session.dos;    
    }
    df.docDos.value = this.session.dos;
    df.submit();
  },
  _parsText:function(pars, suidchar1) {
    var d = "";
    if (pars.length == 0) return "";
    var j = 0;
    if (pars[0].className == "pTitle") {
      j = 1;
      var sTitle = pars[0].innerText;
      if (this._pcustom.singleSpace) {
        d += "\\ul\\b " + sTitle + "\\b0\\ulnone\\par ";
      } else {
        d += "\\ul\\b " + sTitle + "\\b0\\ulnone\\par\\par ";
      }
    }
    for (var i = j; i < pars.length; i++) {
      var par = pars[i];
      var x = "";
      if (par.className == "v" || suidchar1 == "@") {
        x = this._parText(par);
        d += x;
      }
      if (x != "") {
        if (d.substring(d.length - 9, d.length) != "\\par \\par") {
          if (d.substring(d.length - 4, d.length) == "\\par") {
            d += "\\par ";
          } else {
            d += "\\par\\par ";
          }
        } else {
          d = trim(d) + " ";
        }
      }
    }
    return d;
  },
  _parText:function(par) {
    var d = "";
    var pTags = par.childNodes;
    for (var k = 0; k < pTags.length; k++) {
      var pTag = pTags[k];
      if (pTag.className != "h") {
        var spans = this._parSpans(pTag);
        if (spans) {
          this._tableInserted = false;
          for (var m = 0; m < spans.length; m++) {
            var span = spans[m];
            d += this._spanText(span);
            if (this._tableInserted) {
              break;
            }
          }
        }
      }
    }
    return this._fixPunc(d);
  },
  _parSpans:function(pTag) {
    var nodes = pTag.childNodes;
    var spans = [];
    for (var i = 0; i < nodes.length; i++) {
      var span = nodes[i];
      if (span.dq) {  // delete tag
        if (span.prt != "n") {
          for (var j = 0, k = span.children.length; j < k; j++) {
            spans.push(span.children[j]);
          }
        }
      } else {
        spans.push(span);
      }
    }
    return spans;
  },
  _spanText:function(span) {
    if (span.tagName == "TBODY") {
      return this._fixTable(span);
    }
    if (span.tagName == "TABLE") {
      return this._fixTable(span.children[0]);
    }
    if (span.className == "h" || span.className == "notext") {
      return "";
    }
    if (span.name == "clonePop") {
      return this._cloneText(span, false);
    }
    if (span.id == "ft") {
      return this._freePopText(span);
    }
    if (span.prt == "n") {
      return "";
    }
    var t = "";
    var tags = span.childNodes;
    if (tags == null) {
      return span.innerText;
    }
    if (tags.length == 0 && span.tagName == "P") {
      t += "\\par\\par ";
    } else {
      for (var i = 0; i < tags.length; i++) {
        var tt = trim(this._tagText(tags[i]));
        if (tt.length) {
          t += tt + " ";
        }
      }
    }
    return t;
  },
  _tagText:function(tag) {
    if (tag.tagName == null) {
      return tag.toString();
    } else {
      if (tag.tagName == "A") {
        return this._spanText(tag);
      } else if (tag.tagName == "SPAN") {
        return this._spanText(tag);
      } else if (tag.tagName == "P") {
        return "\\par\\par ";
      } else if (tag.tagName == "U") {
        return "\\ul " + tag.innerText +"\\ulnone ";
      } else if (tag.tagName == "B") {
        return "\\b " + this._spanText(tag) + "\\b0 ";
      } else if (tag.tagName == "I") {
        return "\\i " + this._spanText(tag) + "\\i0 ";
      } else if (tag.tagName == "BR") {
        return "\\par ";
      } else if (tag.tagName == "TBODY") {
        return this._fixTable(tag);
      } else if (tag.tagName == "TABLE") {
        return this._fixTable(tag.children[0]);
      }
    }
    return "";
  },
  _cloneText:function(span, asHtml) {
    var divs = span.getElementsByTagName("DIV");  // each instance is wrapped in a DIV  
    
    // If no clones inserted, return nothing  
    if (divs.length == 0) {
      return "";
    }
    // Append before-text plus clone-texts
    var t = span.firstChild.innerText;
    t += this._crlf(asHtml);
    for (var j = 0; j < divs.length; j++) {
      var tags = divs[j].childNodes;
      for (var i = 0; i < tags.length; i++) {
        var tag = tags[i];
        if (tag.className != "clone" && tag.innerText != null) {
          if (tag.id == "ft") {
            t += " " + this._freePopText(tag);
          } else {
            t += tag.innerText;
          }
        }
      }
      t += this._crlf(asHtml);
    }
    return t;
  },
  TR_DEF:"\\trowd\\intbl\\trbrdrt\\brdrs\\brdrw10\\brdrcf2\\trbrdrl\\brdrs\\brdrw10\\brdrcf2\\trbrdrb\\brdrs\\brdrw10\\brdrcf2\\trbrdrr\\brdrs\\brdrw10\\brdrcf2\\trbrdrh\\brdrs\\brdrw10\\brdrcf2\\trbrdrv\\brdrs\\brdrw10\\brdrcf2\\trgaph50",
  _fixTable:function(h) {
    this._tableInserted = true;
    var trs = h.childNodes;
    var x = "";
    for (var i = 0; i < trs.length; i++) {
      var ex = this._extractTds(trs[i].childNodes);
      if (ex != null) {
        var tds = ex.tds;
        var cw = 9000 / ex.cols;  // do integer division
        var c = 0;
        var def = DocDownloader.TR_DEF;
        var j;
        for (j = 0; j < tds.length; j++) {
          c += tds[j].colSpan;
          def += "\\clbrdrt\\brdrs\\brdrw10\\brdrcf2\\clbrdrl\\brdrs\\brdrw10\\brdrcf2\\clbrdrb\\brdrs\\brdrw10\\brdrcf2\\clbrdrr\\brdrs\\brdrw10\\brdrcf2";
          if (tds[j].tagName == "TH") {
            def += "\\clcbpat3";
          }
          def += "\\cellx" + cw * c;
        }
        x += def + "{";
        for (j = 0; j < tds.length; j++) {
          x += "\\qc ";
          if (tds[j].tagName == "TH") {
            x += "\\b ";
          }
          x += this._spanText(tds[j]) + "\\b0\\cell ";
        }
        x += "}{" + def + "\\row }";
      }
    }
    x += "\\pard ";
    return x;
  },
  _extractTds:function(a) {
    var tds = [];
    var cols = 0;
    for (var i = 0; i < a.length; i++) {
      if (a[i].tagName == "TD" || a[i].tagName == "TH") {
        tds.push(a[i]);
        cols += parseInt(a[i].colSpan); 
      }
    }
    if (cols == 0) {
      return null;
    } else {
      return {"tds":tds,"cols":cols};
    }
  },
  _crlf:function(asHtml) {
    return asHtml ? "<br>" : "\\par ";
  },
  _freePopText:function(span) {
    var a = span.childNodes[0];
    if (a.className == "ftd") {
      return "";
    }
    return a.innerText + "  ";
  },
  _fixPunc:function(d) {
    d = d.replace(/\xa0/g, " ");  // change hex-160 to space
    d = d.replace(/\s+\./g, ".");  // elim spaces before period
    d = d.replace(/\.\s+/g, ". ");  // one space after period
    return trim(d);
  },
  _getPrintCustoms:function(ptCustoms) {
    this._pcustom = ptCustoms.print;
    this._tcustom = ptCustoms.template;
    var logofile = this._pcustom.logofile;
    var logofile2 = this._pcustom.logofile2;
    var sigfile = this._pcustom.sigfile;
    var leftHead = false;
    var sigDos = false;
    var c = this._tcustom[this.session.tid];
    if (c != null) {
      if (c.logofile) logofile = c.logofile;
      if (c.logofile2) logofile2 = c.logofile2;
      if (c.sigfile) sigfile = c.sigfile;
      if (c.leftAlignHead) leftHead = true;
      if (c.sigDos) sigDos = true;
    }
    return {
        logofile:logofile,
        logofile2:logofile2,
        sigfile:sigfile,
        leftHead:leftHead,
        sigDos:sigDos};
  },
  /*
   * Copy to Clipboard
   */ 
  toClipboard:function(forSigning) {
    var d = "<div id='dSections'>";
    var s = $("dSections");
    var secs = s.childNodes;
    if (secs) {
      for (var i = 0; i < secs.length; i++) {
        var sec = secs[i];
        var char1 = sec.uid.substring(0, 1);
        d += "<div id='" + sec.id +"' uid='" + sec.uid + "'";
        if (char1 == "@") {
          d += " class='h' style='display:none'>";
        } else {
          d += " class='v'>";
        }
        var pars = sec.childNodes;
        if (sec.className == VISIBLE || char1 == "@") {
          if (sec.uid != "@header" || forSigning) {
            d += parsCopyText(pars, sec.uid);
          }
        }
        d += "</div>";
      }
    }
    d += "</div>";
    if (! forSigning && ! this._pcustom.noTag) {
      d += "<div><center><small><br/><br/><br/>This note created at clicktate.com</small></center></div>";
    }
    // Fix hex chars
    //d = d.replace(/\xa0/g, "&nbsp;");  see fixPunc
    d = d.replace(/\u2022/g, "&#149;")  // bullets
    return d;
  },
  _parsCopyText:function(pars, suid) {
    var d = "";
    if (pars[0].className != HIDDEN) {
      var sTitle = pars[0].innerText;
      d = "<p class=pTitle><u><b>" + sTitle + "</b></u></p>";
    }
    for (var j = 1; j < pars.length; j++) {
      var par = pars[j];
      if (par.className == VISIBLE || suid.substring(0, 1) == "@") {
        d += "<div class=v><p><span class=v>" + this._parCopyText(par) + "</span></p></div>";
      }
    }
    return d;
  },
  _parCopyText:function(par) {
    var d = "";
    var pTags = par.childNodes;
    for (var k = 0; k < pTags.length; k++) {
      var pTag = pTags[k];
      if (pTag.className != HIDDEN) {
        var spans = pTag.childNodes;
        if (spans) {
          this._tableInserted = false;
          for (var m = 0; m < spans.length; m++) {
            var span = spans[m];
            d += this._spanCopyText(span);
            if (this._tableInserted) {
              break;
            } 
          }
        }
      }
    }
    return this._fixPunc(d);
  },
  _spanCopyText:function(span) {
    if (span.tagName == "TBODY") {
      return this._fixCopyTable(span);
    }
    if (span.tagName == "TABLE") {
      return this._fixCopyTable(span.children[0]);
    }
    if (span.className == HIDDEN) {
      return "";
    }
    if (span.name == "clonePop") {
      return this._cloneText(span, true);
    }
    if (span.id == "ft") {
      return this._freePopText(span);
    }
    if (span.innerText.indexOf("chpgn") >= 0) {
      return "";
    }
    if (span.prt == "n") {
      return "";
    }
    var t = "";
    var tags = span.childNodes;
    if (tags == null) {
      return span.innerText;
    }
    for (var i = 0; i < tags.length; i++) {
      t += this._tagCopyText(tags[i]);
    }
    return t;
  },
  _tagCopyText:function(tag) {
    if (tag.tagName == null) {
      return tag.toString();
    } else {
      if (tag.tagName == "A") {
        return this._spanCopyText(tag);
      } else if (tag.tagName == "SPAN") {
        return this._spanCopyText(tag);
      } else if (tag.tagName == "P") {
        return "</p><p> ";
      } else if (tag.tagName == "U") {
        return "<u>" + tag.innerText +"</u>";
      } else if (tag.tagName == "B") {
        return "<b> " + tag.innerText + "</b>";
      } else if (tag.tagName == "BR") {
        return "<br>";
      } else if (tag.tagName == "TBODY") {
        return this._fixCopyTable(tag);
      } else if (tag.tagName == "TABLE") {
        return this._fixCopyTable(tag.children[0]);
      }
    }
    return "";
  },
  _fixCopyTable:function(h) {
    this._tableInserted = true;
    var trs = h.childNodes;
    var x = "<table width=100% border=0 cellpadding=0 cellspacing=0 style='border-collapse:collapse;'>";
    for (var i = 0; i < trs.length; i++) {
      var ex = this._extractTds(trs[i].childNodes);
      if (ex != null) {
        x += "<tr>";
        var tds = ex.tds;
        for (var j = 0; j < tds.length; j++) {
          var cs = "";
          if (tds[j].colSpan > 0) {
            cs = " colspan=" + tds[j].colSpan;
          }
          if (tds[j].tagName == "TH") {
            x += "<th" + cs + " style='border:1px solid #a0a0a0;padding:0 4px;background-color:#eaeaea;'>";
            x += this._spanText(tds[j]);
            x += "</th>";
          } else {
            x += "<td" + cs + " style='border:1px solid #a0a0a0;padding:0 4px;text-align:center;'>";
            x += this._spanText(tds[j]);
            x += "</td>";
          }
        }
        x += "</tr>";
      }
    }
    x += "</table><table border=0 cellpadding=0 cellspacing=0><tr><td height=10></td></tr></table>";
    return x;
  }
};