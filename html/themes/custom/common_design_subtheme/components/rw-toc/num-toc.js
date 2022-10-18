(function (Drupal) {
  'use strict';

  Drupal.behaviors.numTableOfContents = {
    attach: function (context, settings) {
      // Set up smooth-scrolling for ToC.
      //
      // First, check for prefers-reduced-motion and only continue if the media
      // query resolves to false.
      const tocLinks = document.querySelectorAll('.rw-toc__list a');
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches === false) {
        tocLinks.forEach(function (link) {
          link.addEventListener('click', function (ev) {
            ev.preventDefault();
            var linkTarget = '#' + link.getAttribute('href').split('#')[1];
            document.querySelector(linkTarget).scrollIntoView({behavior: 'smooth'});
          });
        });
      }
    },
  };
})(Drupal);
