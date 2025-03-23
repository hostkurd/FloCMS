function fadeOutOnScroll(element) {
    if (!element) {
      return;
    }

    var distanceFromTop = element.getBoundingClientRect().top;

    var opacity = 1;

    var valueFromDistance = (distanceFromTop/1000).toFixed(2);
    valueFromDistance = valueFromDistance > 1 ? 1 : valueFromDistance;
    valueFromDistance = valueFromDistance < 0 ? 0 : valueFromDistance;

    opacity = valueFromDistance-0.1;

    if(distanceFromTop > 600){
      opacity = 0.95;
    }
    if (opacity >= 0) {
      element.style.opacity = opacity;
    }else{
      element.style.opacity = 0;
    }
}

var header = document.getElementById('mouseIcon'); 

function scrollHandler() {
  fadeOutOnScroll(header);
}

window.addEventListener('scroll', scrollHandler);