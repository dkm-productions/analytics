/**
 * Page WelcomePage
 */
WelcomePage = PortalPage.extend(function(self) {
  return {
    onbodyload:function() {
      self.tile = WelcomeTile.create(_$('page'));
      self.tile.load();
    }
  }
})
WelcomeTile = {
  create:function(container) {
    var My = this;
    return Html.Tile.create(container).extend(function(self) {
      return {
        init:function() {
          Html.H1.create('Welcome, ' + me.Client.firstName).into(self);
        },
        load:function() {
          My.DetailTile.asMsg(self).load();
        }
      }
    })
  },
  DetailTile:{
    create:function(container) {
      return Html.Tile.create(container, 'DetailTile').extend(function(self) {
        return {
          init:function() {
            self.head = Html.H2.create().into(self);
            self.text = Html.Tile.create(self, 'Text');
            self.link = Html.Tile.create(self, 'Link');
          }
        }
      })
    },
    asMsg:function(container) {
      return this.create(container).extend(function(self) {
        return {
          load:function() {
            self.head.setText('Messages');
            if (C_Unread == 0)
              self.text.setText('You have no new messages.');
            else
              self.text.setText('You have ' + 'unread message'.plural(C_Unread) + '.');
            Html.Anchor.create('go', 'Go to My Profile').into(self.link).bubble('onclick', window.goFacesheet);
          }
        }
      })
    }
  }
}
 
