/**
 * @file
 * Initiates the group request in AJAX
 */
(function () {
  const requestBtn = document.querySelectorAll('[isajaxrequest]')

  if (!requestBtn || !requestBtn.length) {
    return;
  }

  for (let i = 0; i < requestBtn.length; i++) {
    defineAjaxRequest(requestBtn[i]);
  }

  function defineAjaxRequest (context) {
    const action = context.dataset.action;
    const elementToFetch = context.dataset.element;
    context.addEventListener('click', function(e) {
      e.preventDefault();
      fetch(action)
      .then(function (response) {
        return response.text()
      })
      .then(function (html){
        const parser = new DOMParser();
        const document =  parser.parseFromString(html, 'text/html');
        const form = document.querySelector(elementToFetch);
        showModalWithForm(form)
      }).catch(function (error) {
        console.error('Error:', error);
      });
    })
  }

  function showModalWithForm(form) {
    const modalViewport = createElementWithClass('div', 'ecl-viewport__modal')
    const modal = createElementWithClass('div', 'ecl-viewport__modal__content')
    modal.innerHTML = `
      <span id="closeModal" class="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__modal__call-to-action ecl-link--button ecl-link--button-ghost">
        <span class="ecl-link__label">Close</span>
        &nbsp;
        <div>
          <svg class="ecl-icon ecl-icon ecl-icon--2xs ecl-link__icon" width="10px" height="10px" viewBox="0 0 10 10" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
              <polygon fill="currentColor" points="6.32 4.99 9.519 1.796 8.187 0.464 4.987 3.657 1.812 0.48 0.48 1.812 3.657 4.989 0.462 8.188 1.796 9.521 4.989 6.321 8.206 9.538 9.539 8.205"></polygon>
          </svg>
        </div>
      </span>`
    modal.appendChild(form)
    modalViewport.appendChild(modal)
    document.body.appendChild(modalViewport)

    document.addEventListener('click', function() {
      removeModal(modalViewport)
    })
  }

  function removeModal(modal) {
    document.removeEventListener('click', function(){})
    modal.remove()
  }

  function createElementWithClass(el, className) {
    const element = document.createElement(el)
    element.classList.add(className)

    return element
  }

})();
