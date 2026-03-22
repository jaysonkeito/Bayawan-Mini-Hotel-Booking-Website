/* bayawan-mini-hotel-system/admin/scripts/admin_dashboard.js */

function booking_analytics(period=1) {
  let xhr = new XMLHttpRequest();
  xhr.open("POST","ajax/admin_dashboard.php",true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function(){
    let data = JSON.parse(this.responseText);
    document.getElementById('total_bookings').textContent = data.total_bookings ?? 0;
    document.getElementById('total_amt').textContent      = '₱' + (data.total_amt ?? 0);
    document.getElementById('active_bookings').textContent = data.active_bookings ?? 0;
    document.getElementById('active_amt').textContent      = '₱' + (data.active_amt ?? 0);
    document.getElementById('cancelled_bookings').textContent = data.cancelled_bookings ?? 0;
    document.getElementById('cancelled_amt').textContent      = '₱' + (data.cancelled_amt ?? 0);
  }

  xhr.send('booking_analytics&period='+period);
}

function user_analytics(period=1) {
  let xhr = new XMLHttpRequest();
  xhr.open("POST","ajax/admin_dashboard.php",true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function(){
    let data = JSON.parse(this.responseText);
    document.getElementById('total_new_reg').textContent  = data.total_new_reg;
    document.getElementById('total_queries').textContent  = data.total_queries;
    document.getElementById('total_reviews').textContent  = data.total_reviews;
  }

  xhr.send('user_analytics&period='+period);
}

window.onload = function(){
  booking_analytics();
  user_analytics();
}