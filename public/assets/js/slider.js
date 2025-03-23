const homeSlider = new Swiper('.home-slider', {
  // Optional parameters
  spaceBetween: 30,
  autoplay:false,
  // autoplay: {
  //   delay: 5000,
  // },
  effect: "coverflow", // Creative, cube, fade, coverflow
  //direction: 'vertical',
  //loop: true,
 /* creativeEffect: {
        prev: {
          shadow: true,
          translate: [0, 0, -800],
          rotate: [180, 0, 0],
        },
        next: {
          shadow: true,
          translate: [0, 0, -800],
          rotate: [-180, 0, 0],
        }
      },*/
  // If we need pagination
  pagination: {
    el: '.swiper-pagination',
    clickable: true
  },

  // Navigation arrows
  navigation: {
    nextEl: '.swiper-button-next',
    prevEl: '.swiper-button-prev',
  },

  // And if we need scrollbarr
  scrollbar: {
    el: '.swiper-scrollbar'
  },
  runCallbacksOnInit: true,
  on: {
    slideChangeTransitionStart: function () {

    },
    slideChangeTransitionEnd: function () {

    }
  }
});

