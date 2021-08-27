/**
 * @file
 * Implements the interaction logic for each gallery component.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.gallery = {
    attach: function () {
      const galleries = document.querySelectorAll('.ecl-gallery');

      if (!galleries || !galleries.length) {
        return;
      }

      for (let i = 0; i < galleries.length; i++) {
        defineGallery(galleries[i], i);
      }
    },
  };

  /**
   * Implements the interaction logic for the image gallery.
   */
  var varsAssigned = false
  var currentSlide = []
  var listenersAssigned = []

  function defineGallery(gallery, index) {
    if (!varsAssigned) {
      let galleries = document.querySelectorAll('.ecl-gallery')

      for (let i = 0; i < galleries.length; i++) {
        currentSlide[i] = 1
        listenersAssigned[i] = false
      }

      varsAssigned = true
    }

    if (!listenersAssigned[index]) {
      gallery.querySelector('.ecl-gallery__action__forward').addEventListener('click', e => {
        e.preventDefault();
        showSlide(gallery, currentSlide[index] += 1)
      });
      gallery.querySelector('.ecl-gallery__action__back').addEventListener('click', e => {
        e.preventDefault();
        showSlide(gallery, currentSlide[index] += -1)
      });

      listenersAssigned[index] = true;
    }

    showSlide(gallery, currentSlide[index])
  }

  function showSlide(gallery, n) {
    let slides = gallery.querySelectorAll(".ecl-gallery__main-slides__slide");

    if (n > slides.length) n = 1;
    if (n < 1) n = slides.length;

    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }

    slides[n - 1].style.display = "block";
  }
})(ECL, Drupal);
