/**
 * RW River Letter Navigation
 *
 * @see https://raw.githubusercontent.com/UN-OCHA/rwint9-site/41d232253529a2b86e0954954491f229611e0b52/html/themes/custom/common_design_subtheme/components/rw-river-letter-navigation/rw-river-letter-navigation.js
 */
(function () {
  'use strict';

  /**
   * Update the active anchor link when the current url fragment changes.
   */
  function updateActiveFragmentLink() {
    var hash = location.hash || '#';
    var links = document.querySelectorAll('.rw-river-letter-navigation__link[href^="#"]');
    for (var i = links.length - 1; i >= 0; i--) {
      var link = links[i];
      if (link.getAttribute('href') !== hash) {
        link.classList.remove('rw-river-letter-navigation__link--active');
      }
      else {
        link.classList.add('rw-river-letter-navigation__link--active');
      }
    }
  }

  // Update the active fragment link when the window hash changes.
  window.addEventListener('hashchange', updateActiveFragmentLink);

  // Initial update of the active fragment link.
  updateActiveFragmentLink();
})();
