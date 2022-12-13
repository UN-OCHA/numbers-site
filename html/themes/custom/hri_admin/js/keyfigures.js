/**
 * @file
 * Nested checkboxes for keyfigures.
 */
(function () {
    'use strict';

    Drupal.behaviors.hri_keyfigures = {
      attach: function (context) {
        let figures = context.querySelector('[data-drupal-selector="edit-field-figures"]');
        if (!figures) {
            return;
        }

        let activeFigures = context.querySelector('[data-drupal-selector="edit-field-active-sparklines"]');

        for (var figure of figures.querySelectorAll('.form-checkbox')) {
            let activeFigure = activeFigures.querySelector('.form-checkbox[value="' + figure.value + '"]');
            let activeLabel = activeFigures.querySelector('[for="' + activeFigure.id + '"]');

            activeLabel.innerText = 'Show sparkline?';
            figure.closest('.form-checkboxes--child').nextElementSibling.appendChild(activeFigure);
            figure.closest('.form-checkboxes--child').nextElementSibling.appendChild(activeLabel);
        }

        activeFigures.closest('.field--name-field-active-sparklines').remove();
      }
    };

})();
