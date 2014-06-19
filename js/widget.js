(function ($) {

  Drupal.behaviors.widget = {
    attach: function (context, settings) {
      $('.region-quicktabs-wrapper').tabs();
    }
  };

})(jQuery, Drupal);
