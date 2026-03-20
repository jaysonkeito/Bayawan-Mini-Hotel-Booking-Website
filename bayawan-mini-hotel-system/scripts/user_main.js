/*bayawan-mini-hotel-system/scripts/user_main.js */
function checkLoginToBook(status,room_id){
  if(status){
    window.location.href='user_confirm_booking.php?id='+room_id;
  }
  else{
    alert('error','Please login to book room!');
  }
}