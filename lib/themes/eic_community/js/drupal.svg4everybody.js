/**
 * @file
 * ECL fix for SVGs in IE 11.
 */
(function (Drupal) {
  Drupal.behaviors.defineSVG4Everybody = {
    attach: function () {
      svg4everybody();
    }
  };
})(Drupal);
