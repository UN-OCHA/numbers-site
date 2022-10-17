/**
 * Numbers Table of Contents
 */
(function () {
  'use strict';

  Drupal.behaviors.numTableOfContents = {
    attach: function (context, settings) {
      // Initialize with a few variables.
      const toc = [];
      const paragraphs = context.querySelectorAll('.field--name-field-paragraphs > .field__item > *');
      if (paragraphs.length == 0) {
        return;
      }

      const targets = document.querySelectorAll('.rw-toc__list');
      let output = '';

      // Loop through Paragraphs in the main content column and collect essential
      // info about them.
      paragraphs.forEach(function (el) {
        var paragraphTitle = el.querySelector('.field--name-field-title') && el.querySelector('.field--name-field-title').innerText;

        // Skip a paragraph if:
        // - it has no title
        // - it's a ToC
        if (paragraphTitle === null || el.dataset.type === 'table_of_contents') {
          // Do nothing.
        }
        else {
          toc.push({
            id: el.id,
            title: paragraphTitle
          });
        }
      });

      if (toc.length <= 1) {
        // Remove toc.
        targets.forEach(function (target) {
          target.parentElement.parentElement.remove();
        });

        return;
      }

      // For all items found, construct some HTML.
      toc.forEach(function (item) {
        output += '<li class="rw-toc__section">';
        output += '<a href="#' + item.id + '">' + item.title + '</a>';
        output += '</li>';
      });

      // Append to DOM of all ToC Paragraphs on page.
      targets.forEach(function (target) {
        target.innerHTML = output;
      });

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
    }
  };
})();
