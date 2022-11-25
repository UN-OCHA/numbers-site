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
      button.innerHTML = 'Share';
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
              navigator.share({
                title: shareTitle,
                text: sharetext,
                url: shareUrl,
                files: images
              }).catch(error => {
                // If the first share attempt failed, we will log it but try again
                // with plain text before reporting failure to the user.
                console.log('Image sharing failed:', error);

                // Attempt to share text.
                navigator.share({
                  title: shareTitle,
                  text: sharetext,
                  url: shareUrl
                }).catch(error => {
                  button.innerHTML = 'Share failed...';
                  console.log('Text sharing failed:', error);
                });
              });
            } catch (err) {
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

