(function ($) {

  Drupal.behaviors.widget = {
    attach: function (context, settings) {
      $('.widget-tabbed-content').tabs();
    }
  };

})(jQuery);
