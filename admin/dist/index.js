
init();

async function init() {
  await waitForDocumentLoad();
}

async function waitForDocumentLoad() {
  return new Promise((resolve) => {
      document.addEventListener('DOMContentLoaded', function() {
          resolve();
      });
  });
}


