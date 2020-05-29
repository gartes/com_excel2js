function setAccordion(id, childrenTag, cookieName, collapsible, collapsed) {
    var icons = {
        header: "ui-icon-circle-arrow-e",
        activeHeader: "ui-icon-circle-arrow-s"
    };
    var $headers = jQuery(id).children(childrenTag);
    var active_tab = jQuery.cookie(cookieName);
    var default_state = collapsed ? "none" : 0;
    active_tab = (typeof active_tab == 'undefined') ? default_state : parseInt(active_tab);

    if (collapsible && active_tab == -1) active_tab = 'none';
    jQuery(id).accordion({
        active: active_tab,
        icons: icons,
        collapsible: collapsible,
        heightStyle: "content",
        activate: function (e, ui) {
            jQuery.cookie(cookieName, $headers.index(ui.newHeader));
        }
    });
}

jQuery(document).ready(function(){
  jQuery(".ui-icon-closethick").click(function(){
      jQuery(this).parent().hide();
  });
});