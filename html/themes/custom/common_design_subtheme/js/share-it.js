/**
 * @file
 * Attach destination to login link.
 */

 (function () {
  if (typeof navigator.share !== 'function') {
    // No support.
    //return;
  }

  var keyFigures = document.querySelectorAll('.rw-key-figures__figure');
  if (!keyFigures) {
    // No figures found.
    return;
  }

  keyFigures.forEach(keyfigure => {
    // Attach share button.
    let button = document.createElement('button');
    button.innerHTML = 'Share';
    button.classList.add('rw-key-figures__share-button');

    button.addEventListener('click', e => {
      let keyFigure = e.target.parentElement;

      let shareTitle = window.title;
      let shareUrl = window.location.href + '#' + keyFigure.id;
      let sharetext = [
        keyFigure.querySelector('.rw-key-figures__figure__label').innerHTML,
        ': ',
        keyFigure.querySelector('.rw-key-figures__figure__value').innerHTML
      ].join('');

      try {
        // Hide share button.
        keyFigure.querySelector('.rw-key-figures__share-button').style.display = 'none';

        // Try sharing image.
        html2canvas(keyFigure).then(canvas => {
          canvas.toBlob(async (blob) => {
            const images = [new File([blob], 'image.png', { type: blob.type })];
            navigator.share({
              title: shareTitle,
              text: sharetext,
              url: shareUrl,
              files: images
            })
          });
        });
      } catch (error) {
        // Share as text.
        try {
          navigator.share({
            title: shareTitle,
            text: sharetext,
            url: shareUrl
          })
        } catch (error) {
          console.log('Sharing failed!', error)
        }
      }

      // Show share button.
      keyFigure.querySelector('.rw-key-figures__share-button').style.display = 'inherit';
    });

    keyfigure.append(button);
  });

}());

