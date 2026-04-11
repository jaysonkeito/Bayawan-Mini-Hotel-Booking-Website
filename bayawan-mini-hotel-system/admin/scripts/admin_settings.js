/* bayawan-mini-hotel-system/admin/scripts/admin_settings.js */
// IMPROVEMENT: all alert() calls replaced with show_alert()

let general_data, contacts_data;

let general_s_form     = document.getElementById('general_s_form');
let site_title_inp     = document.getElementById('site_title_inp');
let site_about_inp     = document.getElementById('site_about_inp');
let contacts_s_form    = document.getElementById('contacts_s_form');
let team_s_form        = document.getElementById('team_s_form');
let member_name_inp    = document.getElementById('member_name_inp');
let member_picture_inp = document.getElementById('member_picture_inp');

// ─── General Settings ────────────────────────────────────────────────

function get_general() {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_settings_crud.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    try {
      const raw = this.responseText.substring(this.responseText.indexOf('{'));
      general_data = JSON.parse(raw);
    } catch(e) {
      console.error('get_general parse error:', this.responseText);
      return;
    }

    document.getElementById('site_title').innerText = general_data.site_title ?? '';
    document.getElementById('site_about').innerText = general_data.site_about ?? '';
    site_title_inp.value = general_data.site_title ?? '';
    site_about_inp.value = general_data.site_about ?? '';

    let toggle = document.getElementById('shutdown-toggle');
    toggle.checked = general_data.shutdown == 1;
    toggle.value   = general_data.shutdown;
  };

  xhr.send('get_general=1');
}

general_s_form.addEventListener('submit', function (e) {
  e.preventDefault();
  upd_general(site_title_inp.value, site_about_inp.value);
});

function upd_general(site_title_val, site_about_val) {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_settings_crud.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    bootstrap.Modal.getInstance(document.getElementById('general-s'))?.hide();
    if (this.responseText == 1) {
      show_alert('success', 'Changes saved!');
      get_general();
    } else {
      show_alert('error', 'No changes made!');
    }
  };

  xhr.send(
    'site_title='  + encodeURIComponent(site_title_val) +
    '&site_about=' + encodeURIComponent(site_about_val) +
    '&upd_general=1'
  );
}

function upd_shutdown(val) {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_settings_crud.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    if (this.responseText == 1 && general_data.shutdown == 0) {
      show_alert('success', 'Site has been shutdown!');
    } else {
      show_alert('success', 'Shutdown mode off!');
    }
    get_general();
  };

  xhr.send('upd_shutdown=' + val);
}

// ─── Contact Settings ────────────────────────────────────────────────

function get_contacts() {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_settings_crud.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    try {
      const raw = this.responseText.substring(this.responseText.indexOf('{'));
      contacts_data = JSON.parse(raw);
    } catch(e) {
      console.error('get_contacts parse error:', this.responseText);
      return;
    }

    document.getElementById('address').innerText = contacts_data.address ?? '';
    document.getElementById('gmap').innerText    = contacts_data.gmap    ?? '';
    document.getElementById('pn1').innerText     = contacts_data.pn1     ?? '';
    document.getElementById('pn2').innerText     = contacts_data.pn2     ?? '';
    document.getElementById('email').innerText   = contacts_data.email   ?? '';
    document.getElementById('fb').innerText      = contacts_data.fb      ?? '';
    document.getElementById('insta').innerText   = contacts_data.insta   ?? '';
    document.getElementById('tw').innerText      = contacts_data.tw      ?? '';
    const iframeHtml = contacts_data.iframe ?? '';
    const iframeSrc  = iframeHtml.match(/src="([^"]+)"/);
    document.getElementById('iframe').src = iframeSrc ? iframeSrc[1] : '';

    document.getElementById('address_inp').value = contacts_data.address ?? '';
    document.getElementById('gmap_inp').value    = contacts_data.gmap    ?? '';
    document.getElementById('pn1_inp').value     = contacts_data.pn1     ?? '';
    document.getElementById('pn2_inp').value     = contacts_data.pn2     ?? '';
    document.getElementById('email_inp').value   = contacts_data.email   ?? '';
    document.getElementById('fb_inp').value      = contacts_data.fb      ?? '';
    document.getElementById('insta_inp').value   = contacts_data.insta   ?? '';
    document.getElementById('tw_inp').value      = contacts_data.tw      ?? '';
    document.getElementById('iframe_inp').value  = contacts_data.iframe  ?? '';
  };

  xhr.send('get_contacts=1');
}

contacts_s_form.addEventListener('submit', function (e) {
  e.preventDefault();
  upd_contacts();
});

function upd_contacts() {
  let data_str =
    'address=' + encodeURIComponent(document.getElementById('address_inp').value) + '&' +
    'gmap='    + encodeURIComponent(document.getElementById('gmap_inp').value)    + '&' +
    'pn1='     + encodeURIComponent(document.getElementById('pn1_inp').value)     + '&' +
    'pn2='     + encodeURIComponent(document.getElementById('pn2_inp').value)     + '&' +
    'email='   + encodeURIComponent(document.getElementById('email_inp').value)   + '&' +
    'fb='      + encodeURIComponent(document.getElementById('fb_inp').value)      + '&' +
    'insta='   + encodeURIComponent(document.getElementById('insta_inp').value)   + '&' +
    'tw='      + encodeURIComponent(document.getElementById('tw_inp').value)      + '&' +
    'iframe='  + encodeURIComponent(document.getElementById('iframe_inp').value)  + '&' +
    'upd_contacts=1';

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_settings_crud.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    bootstrap.Modal.getInstance(document.getElementById('contacts-s'))?.hide();
    if (this.responseText == 1) {
      show_alert('success', 'Changes saved!');
      get_contacts();
    } else {
      show_alert('error', 'No changes made!');
    }
  };

  xhr.send(data_str);
}

// ─── Management Team ─────────────────────────────────────────────────

team_s_form.addEventListener('submit', function (e) {
  e.preventDefault();
  add_member();
});

function add_member() {
  let data = new FormData();
  data.append('name',       member_name_inp.value);
  data.append('picture',    member_picture_inp.files[0]);
  data.append('add_member', '');

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_settings_crud.php", true);

  xhr.onload = function () {
    bootstrap.Modal.getInstance(document.getElementById('team-s'))?.hide();
    if      (this.responseText === 'inv_img')    show_alert('error',   'Only JPG and PNG images are allowed!');
    else if (this.responseText === 'inv_size')   show_alert('error',   'Image should be less than 2MB!');
    else if (this.responseText === 'upd_failed') show_alert('error',   'Image upload failed. Server Down!');
    else {
      show_alert('success', 'New member added!');
      member_name_inp.value    = '';
      member_picture_inp.value = '';
      get_members();
    }
  };

  xhr.send(data);
}

function get_members() {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_settings_crud.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function () {
    document.getElementById('team-data').innerHTML = this.responseText;
  };
  xhr.send('get_members=1');
}

function rem_member(val) {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_settings_crud.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function () {
    if (this.responseText == 1) {
      show_alert('success', 'Member removed!');
      get_members();
    } else {
      show_alert('error', 'Server down!');
    }
  };
  xhr.send('rem_member=' + val);
}

// ─── Init ─────────────────────────────────────────────────────────────

window.onload = function () {
  get_general();
  get_contacts();
  get_members();
};