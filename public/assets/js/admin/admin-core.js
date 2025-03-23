// Using SweetAlert 2 in this Function
function showAlert($type, $title, $message){
    return Swal.fire({
      icon:$type,
      title:$title,
      text:$message,
      showClass: {
        backdrop: 'swal2-noanimation', // disable backdrop animation
        popup: '',                     // disable popup animation
        //icon: ''                       // disable icon animation
      },
      hideClass: {
        popup: '',                     // disable popup fade-out animation
      }
    });
}

// Check if the string value is Json String or Not
function isJsonString(str) { 
  try {
      JSON.parse(str);
  } catch (e) {
      return false;
  }
  return true;
}

function makeRequest (data, url) {
  const dataArr = Object.entries(data);
  const formData = new FormData();
  dataArr.forEach(([key, value]) => {
    console.log(key, value);
    formData.append(key, value);
  });

  return new Promise(function (resolve, reject) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', site_url + url);
    xhr.onload = function () {
      if (xhr.status >= 200 && xhr.status < 300) {
        resolve(xhr.response);
      } else {
        reject({
          status: xhr.status,
          statusText: xhr.statusText
        });
      }
    };
    xhr.onerror = function () {
      reject({
        status: xhr.status,
        statusText: xhr.statusText
      });
    };
    xhr.send(formData);
  });
}