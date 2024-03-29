/**
 * @file
 * Use the Web Share API on individual figures.
 */

(function () {
  if (!!navigator.canShare) {
    var keyFigures = document.querySelectorAll('.ocha-key-figures__figure');

    keyFigures.forEach((keyFigure) => {
      // Create share button.
      let shareButton = document.createElement('button');
      // @TODO: fix non-translatable text
      shareButton.innerHTML = '<span class="visually-hidden">Share this figure</span><img src="/themes/custom/common_design_subtheme/components/num-key-figures-share/share.svg" aria-hidden="true" focusable="false">';
      shareButton.classList.add(
        'ocha-key-figures__share-button',
        'cd-social-links__link',
        'cd-social-links__link--copy',
      );

      // Using this as an error, as opposed to success as it appears in the
      // default cd-social-links component.
      shareButton.dataset.message = 'Failed to share Figure';

      // Set up Web Share API when button is clicked.
      shareButton.addEventListener('click', (ev) => {
        let keyFigure = ev.target.closest('.ocha-key-figures__figure');

        let shareTitle = window.title;
        let shareUrl = window.location.href + '#' + keyFigure.id;
        let sharetext = [
          keyFigure.querySelector('.ocha-key-figures__figure__label').innerHTML,
          ': ',
          keyFigure.querySelector('.ocha-key-figures__figure__value').innerHTML,
          '\n',
        ].join('');

        // Hide share button while capturing an image of the figure.
        keyFigure.classList.add('is--capturing');

        // Try sharing image.
        html2canvas(keyFigure).then((canvas) => {
          // Attempt to create image from DOM.
          canvas.toBlob(async (blob) => {
            try {
              // Attempt to share image.
              const images = [new File([blob], 'image.png', { type: blob.type })];
              let shareData = {
                title: shareTitle,
                text: sharetext,
                url: shareUrl,
                files: images
              };

              // Share everything.
              if (navigator.canShare(shareData)) {
                navigator.share(shareData);
              }
              else {
                // Share without files.
                delete shareData.files;
                if (navigator.canShare(shareData)) {
                  navigator.share(shareData);
                }
                else {
                  // Share without text.
                  delete shareData.text;
                  if (navigator.canShare(shareData)) {
                    navigator.share(shareData);
                  }
                  else {
                    // Share without title.
                    delete shareData.title;
                    if (navigator.canShare(shareData)) {
                      navigator.share(shareData);
                    }
                  }
                }
              }
            } catch (err) {
              // Show user feedback and remove after some time.
              shareButton.classList.add('is--showing-message');
              setTimeout(function () {
                shareButton.classList.remove('is--showing-message');
              }, 2500);

              // Log error to console.
              console.error('Sharing failed:', err);
            }
          });
        });

        // Show share button now that image capture was attempted.
        keyFigure.classList.remove('is--capturing');
      });

      // Insert button into DOM.
      keyFigure.append(shareButton);
      // Mark this figure as shareable.
      keyFigure.classList.add('ocha-key-figures__figure--can-share');
    });
  }
}());

