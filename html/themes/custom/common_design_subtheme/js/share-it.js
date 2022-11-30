/**
 * @file
 * Use native browser share mechanism on individual figures.
 */

(function () {
  if (!!navigator.canShare) {
    var keyFigures = document.querySelectorAll('.rw-key-figures__figure');

    keyFigures.forEach((keyfigure) => {
      // Attach share button.
      let button = document.createElement('button');
      button.innerHTML = '<span class="hidden">Share</span>';
      button.classList.add('rw-key-figures__share-button');

      button.addEventListener('click', (ev) => {
        let keyFigure = ev.target.parentElement;

        let shareTitle = window.title;
        let shareUrl = window.location.href + '#' + keyFigure.id;
        let sharetext = [
          keyFigure.querySelector('.rw-key-figures__figure__label').innerHTML,
          ': ',
          keyFigure.querySelector('.rw-key-figures__figure__value').innerHTML
        ].join('');

        // Hide share button.
        keyFigure.querySelector('.rw-key-figures__share-button').style.display = 'none';

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
                    else {
                      button.style.backgroundColor = 'red';
                    }
                  }
                }
              }
            } catch (err) {
              button.style.backgroundColor = 'red';
              console.error('Sharing failed:', err);
            }
          });
        });

        // Show share button.
        keyFigure.querySelector('.rw-key-figures__share-button').style.display = 'block';
      });

      keyfigure.append(button);
    });
  }
}());

