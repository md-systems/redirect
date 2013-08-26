
(function ($) {

Drupal.behaviors.redirectFieldsetSummaries = {
  attach: function (context) {
    $('fieldset.redirect-list', context).drupalSetSummary(function (context) {
      if ($('table.redirect-list tbody td.empty', context).length) {
        return Drupal.t('No redirects');
      }
      else {
        var redirects = $('table.redirect-list tbody tr', context).length;
        return Drupal.formatPlural(redirects, '1 redirect', '@count redirects');
      }
    });
  }
};

})(jQuery);
