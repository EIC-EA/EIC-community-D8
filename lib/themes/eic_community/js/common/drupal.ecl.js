/**
 * @file
 * Initiates the ECL component library
 */
(function (ECL, Drupal) {
  Drupal.behaviors.defineECL = {
    attach: function attach() {
      window.ECL.autoInit();
    },
  };
})(ECL, Drupal);
