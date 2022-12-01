self.addEventListener('fetch', (event) => {
  // Ensure we can bypass basic auth in non-prod environments.
  const modifiedRequest = new Request(event.request, {credentials: 'same-origin'});

  event.respondWith((async () => {
    return fetch(modifiedRequest);
  })());
});
