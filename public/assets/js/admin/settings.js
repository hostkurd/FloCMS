function filterSelection(c) {
    var items, i;
    items = document.getElementsByClassName("filterDiv");
    if (c == "all") c = "";
    // Add the "show" class (display:block) to the filtered elements, and remove the "show" class from the elements that are not selected
    for (i = 0; i < items.length; i++) {
      removeClass(items[i], "show");
      if (items[i].className.indexOf(c) > -1) addClass(items[i], "show");
    }
}
  // Show filtered elements
function addClass(element, name) {
    var i, arr1, arr2;
    arr1 = element.className.split(" ");
    arr2 = name.split(" ");
    for (i = 0; i < arr2.length; i++) {
        if (arr1.indexOf(arr2[i]) == -1) {
        element.className += " " + arr2[i];
        }
    }
}
// Hide elements that are not selected
function removeClass(element, name) {
    var i, arr1, arr2;
    arr1 = element.className.split(" ");
    arr2 = name.split(" ");
    for (i = 0; i < arr2.length; i++) {
        while (arr1.indexOf(arr2[i]) > -1) {
        arr1.splice(arr1.indexOf(arr2[i]), 1);
        }
    }
    element.className = arr1.join(" ");
}
function SetCard(e) {
    console.log(e.target.id);
    var curValue = e.target.attributes.value.value;
    var elementId = e.target.id;

    var element = document.getElementById(elementId);
    if(curValue == 1){
        element.setAttribute('value',0);
    }else{
        element.setAttribute('value',1);
    }
}
  // Add active class to the current control button (highlight it)
var btnContainer = document.getElementById("tab-container");
var btns = btnContainer.getElementsByClassName("filter-tab");
for (var i = 0; i < btns.length; i++) {
btns[i].addEventListener("click", function() {
    //resetActiveTab();
    var current = document.getElementsByClassName("actived");
    //removeClass(current, "active");
    current[0].className = current[0].className.replace(" actived", "");
    this.className += " actived";
});
}
  
