<?php
// bayawan-mini-hotel-system/includes/lang.php

// ─────────────────────────────────────────────────────────────────────
// Central translation file.
/* Usage anywhere:  <?php echo t('key'); ?> */
// Add new keys to BOTH 'en' and 'fil' arrays below.
// ─────────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) session_start();

$_SESSION['lang'] ??= 'en';

$GLOBALS['_LANG'] = [

    // ── Navbar ────────────────────────────────────────────────────────
    'nav_home'          => ['en' => 'Home',         'fil' => 'Tahanan'],
    'nav_rooms'         => ['en' => 'Rooms',         'fil' => 'Mga Silid'],
    'nav_facilities'    => ['en' => 'Facilities',    'fil' => 'Mga Pasilidad'],
    'nav_contact'       => ['en' => 'Contact us',    'fil' => 'Makipag-ugnayan'],
    'nav_about'         => ['en' => 'About',         'fil' => 'Tungkol Sa Amin'],
    'nav_profile'       => ['en' => 'Profile',       'fil' => 'Aking Profile'],
    'nav_bookings'      => ['en' => 'Bookings',      'fil' => 'Mga Booking'],
    'nav_logout'        => ['en' => 'Logout',        'fil' => 'Mag-logout'],
    'nav_login'         => ['en' => 'Login',         'fil' => 'Mag-login'],
    'nav_register'      => ['en' => 'Register',      'fil' => 'Mag-rehistro'],

    // ── Login Modal ───────────────────────────────────────────────────
    'login_title'       => ['en' => 'User Login',          'fil' => 'Login ng Gumagamit'],
    'login_email_mob'   => ['en' => 'Email / Mobile',      'fil' => 'Email / Mobile'],
    'login_password'    => ['en' => 'Password',            'fil' => 'Password'],
    'login_remember'    => ['en' => 'Remember me',         'fil' => 'Alalahanin ako'],
    'login_forgot'      => ['en' => 'Forgot Password?',    'fil' => 'Nakalimutan ang Password?'],
    'login_btn'         => ['en' => 'LOGIN',               'fil' => 'MAG-LOGIN'],
    'login_google'      => ['en' => 'Login with Google',   'fil' => 'Mag-login gamit ang Google'],
    'login_or'          => ['en' => 'or',                  'fil' => 'o'],

    // ── Register Modal ────────────────────────────────────────────────
    'reg_title'         => ['en' => 'User Registration',       'fil' => 'Pagpaparehistro'],
    'reg_fullname'      => ['en' => 'Full Name',               'fil' => 'Buong Pangalan'],
    'reg_email'         => ['en' => 'Email Address',           'fil' => 'Email Address'],
    'reg_send_code'     => ['en' => 'Send Verification Code',  'fil' => 'Magpadala ng Code'],
    'reg_google'        => ['en' => 'Sign up with Google',     'fil' => 'Mag-sign up gamit Google'],
    'reg_otp_label'     => ['en' => 'Verification Code',       'fil' => 'Code sa Pagpapatunay'],
    'reg_resend'        => ['en' => 'Resend',                  'fil' => 'Ipadala Ulit'],
    'reg_verify'        => ['en' => 'Verify Email',            'fil' => 'I-verify ang Email'],
    'reg_email_ok'      => ['en' => 'Email verified successfully!', 'fil' => 'Matagumpay na na-verify ang email!'],
    'reg_phone'         => ['en' => 'Phone Number',            'fil' => 'Numero ng Telepono'],
    'reg_pic'           => ['en' => 'Profile Picture (optional)', 'fil' => 'Larawan ng Profile (opsyonal)'],
    'reg_address'       => ['en' => 'Address',                 'fil' => 'Tirahan'],
    'reg_postal'        => ['en' => 'Postal Code',             'fil' => 'Postal Code'],
    'reg_dob'           => ['en' => 'Date of Birth',           'fil' => 'Petsa ng Kapanganakan'],
    'reg_cpassword'     => ['en' => 'Confirm Password',        'fil' => 'Kumpirmahin ang Password'],
    'reg_agree'         => ['en' => 'I agree to the',          'fil' => 'Sumasang-ayon ako sa'],
    'reg_terms'         => ['en' => 'Terms and Conditions',    'fil' => 'Mga Tuntunin at Kundisyon'],
    'reg_and'           => ['en' => 'and',                     'fil' => 'at'],
    'reg_privacy'       => ['en' => 'Privacy Policy',          'fil' => 'Patakaran sa Privacy'],
    'reg_complete_btn'  => ['en' => 'Complete Registration',   'fil' => 'Kumpletuhin ang Pagpaparehistro'],
    'reg_pass_rules'    => ['en' => 'Password must contain:',  'fil' => 'Ang password ay dapat naglalaman ng:'],
    'reg_rule_len'      => ['en' => 'At least 8 characters',   'fil' => 'Hindi bababa sa 8 na karakter'],
    'reg_rule_lower'    => ['en' => 'Lowercase letter',        'fil' => 'Maliit na letra'],
    'reg_rule_upper'    => ['en' => 'Uppercase letter',        'fil' => 'Malaking letra'],
    'reg_rule_number'   => ['en' => 'Number',                  'fil' => 'Numero'],
    'reg_rule_special'  => ['en' => 'Special character',       'fil' => 'Espesyal na karakter'],

    // ── Forgot Password Modal ─────────────────────────────────────────
    'forgot_title'      => ['en' => 'Forgot Password',         'fil' => 'Nakalimutang Password'],
    'forgot_note'       => ['en' => 'Note: A link will be sent to your email to reset your password!',
                            'fil' => 'Tandaan: Magpapadala ng link sa iyong email para i-reset ang password!'],
    'forgot_send'       => ['en' => 'SEND LINK',               'fil' => 'MAGPADALA NG LINK'],
    'forgot_cancel'     => ['en' => 'CANCEL',                  'fil' => 'KANSELAHIN'],

    // ── Home page ─────────────────────────────────────────────────────
    'home_check_title'  => ['en' => 'Check Booking Availability', 'fil' => 'Suriin ang Availability ng Booking'],
    'home_checkin'      => ['en' => 'Check-in',                'fil' => 'Petsa ng Pagdating'],
    'home_checkout'     => ['en' => 'Check-out',               'fil' => 'Petsa ng Pag-alis'],
    'home_adults'       => ['en' => 'Adults',                  'fil' => 'Mga Matatanda'],
    'home_children'     => ['en' => 'Children',                'fil' => 'Mga Bata'],
    'home_check_btn'    => ['en' => 'Check Now',               'fil' => 'Suriin Ngayon'],
    'home_rooms_title'  => ['en' => 'OUR ROOMS',               'fil' => 'AMING MGA SILID'],
    'home_more_rooms'   => ['en' => 'More Rooms >>>',          'fil' => 'Higit pang Silid >>>'],
    'home_facilities'   => ['en' => 'OUR FACILITIES',         'fil' => 'AMING MGA PASILIDAD'],
    'home_more_fac'     => ['en' => 'More Facilities >>>',     'fil' => 'Higit pang Pasilidad >>>'],
    'home_testimonials' => ['en' => 'TESTIMONIALS',            'fil' => 'MGA TESTIMONYA'],
    'home_no_reviews'   => ['en' => 'No reviews yet!',         'fil' => 'Wala pang mga review!'],
    'home_reach'        => ['en' => 'REACH US',                'fil' => 'MAKIPAG-UGNAYAN SA AMIN'],
    'home_call'         => ['en' => 'Call us',                 'fil' => 'Tawagan Kami'],
    'home_follow'       => ['en' => 'Follow us',               'fil' => 'Sundan Kami'],
    'home_no_social'    => ['en' => 'No social media links yet.', 'fil' => 'Wala pang social media links.'],
    'home_know_more'    => ['en' => 'Know More >>>',           'fil' => 'Alamin Pa >>>'],

    // ── Room card (shared) ─────────────────────────────────────────────
    'room_features'     => ['en' => 'Features',                'fil' => 'Mga Katangian'],
    'room_facilities'   => ['en' => 'Facilities',              'fil' => 'Mga Pasilidad'],
    'room_guests'       => ['en' => 'Guests',                  'fil' => 'Mga Bisita'],
    'room_rating'       => ['en' => 'Rating',                  'fil' => 'Rating'],
    'room_adults'       => ['en' => 'Adults',                  'fil' => 'Matatanda'],
    'room_children'     => ['en' => 'Children',                'fil' => 'Bata'],
    'room_book_now'     => ['en' => 'Book Now',                'fil' => 'Mag-book Na'],
    'room_details'      => ['en' => 'More details',            'fil' => 'Higit pang Detalye'],
    'room_per_night'    => ['en' => 'per night',               'fil' => 'bawat gabi'],
    'room_none'         => ['en' => 'None listed',             'fil' => 'Wala'],
    'room_closed'       => ['en' => 'Booking Closed',          'fil' => 'Sarado ang Booking'],
    'room_area'         => ['en' => 'Area',                    'fil' => 'Sukat'],

    // ── Rooms page ────────────────────────────────────────────────────
    'rooms_title'        => ['en' => 'OUR ROOMS',              'fil' => 'AMING MGA SILID'],
    'rooms_filters'      => ['en' => 'FILTERS',                'fil' => 'MGA FILTER'],
    'rooms_avail'        => ['en' => 'CHECK AVAILABILITY',     'fil' => 'SURIIN ANG AVAILABILITY'],
    'rooms_reset'        => ['en' => 'Reset',                  'fil' => 'I-reset'],
    'rooms_fac_filter'   => ['en' => 'FACILITIES',             'fil' => 'MGA PASILIDAD'],
    'rooms_guests_filter'=> ['en' => 'GUESTS',                 'fil' => 'MGA BISITA'],

    // ── Room Details page ─────────────────────────────────────────────
    'rd_avail_cal'      => ['en' => 'Availability Calendar',   'fil' => 'Kalendaryo ng Availability'],
    'rd_booked'         => ['en' => 'Booked / Unavailable',    'fil' => 'Naka-book / Hindi Available'],
    'rd_your_sel'       => ['en' => 'Your Selection',          'fil' => 'Iyong Pinili'],
    'rd_sel_range'      => ['en' => 'Selected Range',          'fil' => 'Napiling Saklaw'],
    'rd_available'      => ['en' => 'Available',               'fil' => 'Available'],
    'rd_book_dates'     => ['en' => 'Book These Dates',        'fil' => 'I-book ang Mga Petsang Ito'],
    'rd_description'    => ['en' => 'Description',             'fil' => 'Paglalarawan'],
    'rd_reviews'        => ['en' => 'Reviews & Ratings',       'fil' => 'Mga Review at Rating'],
    'rd_no_reviews'     => ['en' => 'No reviews yet!',         'fil' => 'Wala pang mga review!'],

    // ── About page ────────────────────────────────────────────────────
    'about_title'       => ['en' => 'ABOUT US',                'fil' => 'TUNGKOL SA AMIN'],
    'about_subtitle'    => ['en' => 'Learn more about Cebu Mini Hotel — your home away from home in the heart of Cebu City, Cebu.',
                            'fil' => 'Alamin ang higit pa tungkol sa Cebu Mini Hotel — ang inyong tahanan sa puso ng Cebu City, Cebu.'],
    'about_welcome'     => ['en' => 'Welcome to Cebu Mini Hotel', 'fil' => 'Maligayang Pagdating sa Cebu Mini Hotel'],
    'about_p1'          => ['en' => 'Cebu Mini Hotel is a cozy and affordable hotel located in Purok 8. Brgy, 39G N Escario St, Camputhaw, Cebu City, 6000 Cebu. We are committed to providing our guests with a warm, comfortable, and memorable stay in the heart of the city.',
                            'fil' => 'Ang Cebu Mini Hotel ay isang maaliwalas at abot-kayang hotel na matatagpuan sa Purok 8. Brgy, 39G N Escario St, Camputhaw, Cebu City, 6000 Cebu. Kami ay nakatuon sa pagbibigay ng mainit, komportable, at di-malilimutang pamamalagi sa puso ng lungsod.'],
    'about_p2'          => ['en' => 'Whether you are traveling for business or leisure, our hotel offers well-furnished rooms, modern amenities, and friendly service to ensure that your stay exceeds expectations. From our Standard Rooms to our premium Suite, every room is designed with your comfort in mind.',
                            'fil' => 'Anuman ang layunin ng inyong paglalakbay — negosyo man o libangan — ang aming hotel ay nag-aalok ng magandang mga silid, modernong pasilidad, at magiliw na serbisyo upang masigurado na ang inyong pamamalagi ay lampas sa inaasahan.'],
    'about_p3'          => ['en' => 'We take pride in our prime location, making it easy for guests to explore the beautiful city of Cebu and nearby attractions in Cebu. Come and experience the warmth of Filipino hospitality at Cebu Mini Hotel.',
                            'fil' => 'Ipinagmamalaki namin ang aming magandang lokasyon na nagbibigay-daan sa mga bisita na madaling tuklasin ang magandang lungsod ng Cebu at mga kalapit-lugar sa Cebu. Halika at maranasan ang init ng Pilipinong pagkamapagpatuloy sa Cebu Mini Hotel.'],
    'about_stat1_title' => ['en' => '4 ROOM TYPES',            'fil' => '4 URI NG SILID'],
    'about_stat1_sub'   => ['en' => 'Standard, Deluxe, Family & Suite', 'fil' => 'Standard, Deluxe, Pamilya at Suite'],
    'about_stat2_title' => ['en' => '500+ GUESTS',             'fil' => '500+ BISITA'],
    'about_stat2_sub'   => ['en' => 'Satisfied guests since opening', 'fil' => 'Mga nasisiyahang bisita mula nang pagbubukas'],
    'about_stat3_title' => ['en' => '7 FACILITIES',            'fil' => '7 PASILIDAD'],
    'about_stat3_sub'   => ['en' => 'Modern amenities for your comfort', 'fil' => 'Modernong pasilidad para sa inyong kaginhawaan'],
    'about_stat4_title' => ['en' => '24/7 SERVICE',            'fil' => '24/7 SERBISYO'],
    'about_stat4_sub'   => ['en' => 'Always here to assist you', 'fil' => 'Laging handang tumulong sa inyo'],
    'about_team'        => ['en' => 'MANAGEMENT TEAM',         'fil' => 'PANGKAT NG PAMAMAHALA'],

    // ── Contact page ──────────────────────────────────────────────────
    'contact_title'     => ['en' => 'CONTACT US',              'fil' => 'MAKIPAG-UGNAYAN SA AMIN'],
    'contact_subtitle'  => ['en' => "Have questions or need assistance? We'd love to hear from you. Reach out to us and our friendly staff will get back to you as soon as possible.",
                            'fil' => 'May mga katanungan o kailangan ng tulong? Nais naming marinig kayo. Makipag-ugnayan sa amin at ang aming magiliw na kawani ay makikipag-ugnayan sa inyo sa lalong madaling panahon.'],
    'contact_address'   => ['en' => 'Address',                 'fil' => 'Tirahan'],
    'contact_call'      => ['en' => 'Call us',                 'fil' => 'Tawagan Kami'],
    'contact_email_lbl' => ['en' => 'Email',                   'fil' => 'Email'],
    'contact_follow'    => ['en' => 'Follow us',               'fil' => 'Sundan Kami'],
    'contact_send_msg'  => ['en' => 'Send a message',          'fil' => 'Magpadala ng Mensahe'],
    'contact_name'      => ['en' => 'Name',                    'fil' => 'Pangalan'],
    'contact_subject'   => ['en' => 'Subject',                 'fil' => 'Paksa'],
    'contact_message'   => ['en' => 'Message',                 'fil' => 'Mensahe'],
    'contact_send_btn'  => ['en' => 'SEND',                    'fil' => 'IPADALA'],

    // ── Facilities page ───────────────────────────────────────────────
    'fac_title'         => ['en' => 'OUR FACILITIES',          'fil' => 'AMING MGA PASILIDAD'],
    'fac_subtitle'      => ['en' => 'At Cebu Mini Hotel, we are committed to providing you with a comfortable and memorable stay. Explore the amenities and services we offer to make your visit in Cebu City truly enjoyable.',
                            'fil' => 'Sa Cebu Mini Hotel, kami ay nakatuon sa pagbibigay sa inyo ng komportable at di-malilimutang pamamalagi. Tuklasin ang mga amenities at serbisyo na aming inaalok upang gawing tunay na kasiya-siya ang inyong pagbisita sa Cebu City.'],

    // ── Bookings page ─────────────────────────────────────────────────
    'bookings_title'    => ['en' => 'BOOKINGS',                'fil' => 'MGA BOOKING'],
    'bookings_policy_title'  => ['en' => 'Cancellation Policy','fil' => 'Patakaran sa Pagkansela'],
    'bookings_full_ref' => ['en' => 'Full Refund',             'fil' => 'Buong Refund'],
    'bookings_72h'      => ['en' => 'Cancel 72+ hours before check-in', 'fil' => 'Kanselahin 72+ oras bago mag-check-in'],
    'bookings_50pct'    => ['en' => '50% Penalty',             'fil' => '50% na Multa'],
    'bookings_24_72h'   => ['en' => 'Cancel 24–72 hours before check-in', 'fil' => 'Kanselahin 24–72 oras bago mag-check-in'],
    'bookings_1night'   => ['en' => '1st Night Forfeited',     'fil' => 'Mawawala ang 1st Night'],
    'bookings_lt24h'    => ['en' => 'Cancel less than 24 hours before check-in', 'fil' => 'Kanselahin wala pang 24 oras bago mag-check-in'],
    'bookings_checkin'  => ['en' => 'Check in',                'fil' => 'Petsa ng Pagdating'],
    'bookings_checkout' => ['en' => 'Check out',               'fil' => 'Petsa ng Pag-alis'],
    'bookings_paid'     => ['en' => 'Amount Paid',             'fil' => 'Halagang Binayad'],
    'bookings_order_id' => ['en' => 'Order ID',                'fil' => 'Order ID'],
    'bookings_date'     => ['en' => 'Date',                    'fil' => 'Petsa'],
    'bookings_dl_pdf'   => ['en' => 'Download PDF',            'fil' => 'I-download ang PDF'],
    'bookings_rate'     => ['en' => 'Rate & Review',           'fil' => 'I-rate at I-review'],
    'bookings_cancel'   => ['en' => 'Cancel',                  'fil' => 'Kanselahin'],
    'bookings_refund_proc'   => ['en' => 'Refund in process!', 'fil' => 'Nasa proseso ang Refund!'],
    'bookings_refund_lbl'    => ['en' => 'Refund',             'fil' => 'Refund'],
    'bookings_no_show'  => ['en' => 'No-Show',                 'fil' => 'Hindi Dumating'],
    'bookings_keep'     => ['en' => 'Keep Booking',            'fil' => 'Panatilihin ang Booking'],
    'bookings_confirm_cancel'=> ['en' => 'Confirm Cancellation','fil' => 'Kumpirmahin ang Pagkansela'],
    'bookings_cancel_title'  => ['en' => 'Cancel Booking',     'fil' => 'Kanselahin ang Booking'],
    'bookings_calc_refund'   => ['en' => 'Calculating refund...','fil' => 'Kinakalkula ang refund...'],
    'bookings_review_title'  => ['en' => 'Rate & Review',      'fil' => 'I-rate at I-review'],
    'bookings_excellent'     => ['en' => 'Excellent',          'fil' => 'Napakahusay'],
    'bookings_good'          => ['en' => 'Good',               'fil' => 'Magaling'],
    'bookings_ok'            => ['en' => 'Ok',                 'fil' => 'Ok'],
    'bookings_poor'          => ['en' => 'Poor',               'fil' => 'Mahina'],
    'bookings_bad'           => ['en' => 'Bad',                'fil' => 'Masama'],
    'bookings_review_lbl'    => ['en' => 'Review',             'fil' => 'Review'],
    'bookings_submit'        => ['en' => 'SUBMIT',             'fil' => 'ISUMITE'],

    // ── Profile page ──────────────────────────────────────────────────
    'profile_title'     => ['en' => 'PROFILE',                 'fil' => 'PROFILE'],
    'profile_basic'     => ['en' => 'Basic Information',       'fil' => 'Pangunahing Impormasyon'],
    'profile_name'      => ['en' => 'Name',                    'fil' => 'Pangalan'],
    'profile_email'     => ['en' => 'Email',                   'fil' => 'Email'],
    'profile_email_note'=> ['en' => 'Email cannot be changed.','fil' => 'Hindi mababago ang email.'],
    'profile_phone'     => ['en' => 'Phone Number',            'fil' => 'Numero ng Telepono'],
    'profile_dob'       => ['en' => 'Date of Birth',           'fil' => 'Petsa ng Kapanganakan'],
    'profile_pincode'   => ['en' => 'Pincode',                 'fil' => 'Postal Code'],
    'profile_address'   => ['en' => 'Address',                 'fil' => 'Tirahan'],
    'profile_save'      => ['en' => 'Save Changes',            'fil' => 'I-save ang Pagbabago'],
    'profile_pic'       => ['en' => 'Picture',                 'fil' => 'Larawan'],
    'profile_new_pic'   => ['en' => 'New Picture',             'fil' => 'Bagong Larawan'],
    'profile_change_pass'    => ['en' => 'Change Password',    'fil' => 'Baguhin ang Password'],
    'profile_curr_pass'      => ['en' => 'Current Password',   'fil' => 'Kasalukuyang Password'],
    'profile_new_pass'       => ['en' => 'New Password',       'fil' => 'Bagong Password'],
    'profile_confirm_pass'   => ['en' => 'Confirm New Password','fil' => 'Kumpirmahin ang Bagong Password'],
    'profile_google_note'    => ['en' => "Signed in with Google and don't have a password? Use Forgot Password from the login page to set one.",
                                 'fil' => 'Nag-sign in gamit ang Google at wala pang password? Gamitin ang Nakalimutang Password mula sa login page upang magtakda ng isa.'],
    'profile_forgot_link'    => ['en' => 'Use Forgot Password','fil' => 'Gamitin ang Nakalimutang Password'],

    // ── Cart page ─────────────────────────────────────────────────────
    'cart_title'        => ['en' => 'MY CART',                 'fil' => 'AKING CART'],
    'cart_empty'        => ['en' => 'Your cart is empty.',     'fil' => 'Walang laman ang inyong cart.'],
    'cart_browse'       => ['en' => 'Browse Rooms',            'fil' => 'Mag-browse ng Silid'],
    'cart_total'        => ['en' => 'Total',                   'fil' => 'Kabuuan'],
    'cart_total_note'   => ['en' => 'Includes all rooms in cart','fil' => 'Kasama ang lahat ng silid sa cart'],
    'cart_guest_info'   => ['en' => 'Guest Information',       'fil' => 'Impormasyon ng Bisita'],
    'cart_guest_note'   => ['en' => 'This information will be applied to all rooms in your cart.',
                            'fil' => 'Ang impormasyong ito ay ilalapat sa lahat ng silid sa inyong cart.'],
    'cart_proceed'      => ['en' => 'Proceed to Payment',      'fil' => 'Magpatuloy sa Pagbabayad'],
    'cart_closed'       => ['en' => 'Bookings Temporarily Closed','fil' => 'Pansamantalang Sarado ang Booking'],
    'cart_add_more'     => ['en' => 'Add more rooms',          'fil' => 'Magdagdag ng Higit pang Silid'],
    'cart_name'         => ['en' => 'Full Name',               'fil' => 'Buong Pangalan'],
    'cart_phone'        => ['en' => 'Phone Number',            'fil' => 'Numero ng Telepono'],
    'cart_address'      => ['en' => 'Address',                 'fil' => 'Tirahan'],

    // ── Confirm Booking page ──────────────────────────────────────────
    'cb_title'          => ['en' => 'CONFIRM BOOKING',         'fil' => 'KUMPIRMAHIN ANG BOOKING'],
    'cb_details'        => ['en' => 'BOOKING DETAILS',         'fil' => 'DETALYE NG BOOKING'],
    'cb_name'           => ['en' => 'Name',                    'fil' => 'Pangalan'],
    'cb_phone'          => ['en' => 'Phone Number',            'fil' => 'Numero ng Telepono'],
    'cb_address'        => ['en' => 'Address',                 'fil' => 'Tirahan'],
    'cb_checkin'        => ['en' => 'Check-in',                'fil' => 'Petsa ng Pagdating'],
    'cb_checkout'       => ['en' => 'Check-out',               'fil' => 'Petsa ng Pag-alis'],
    'cb_provide_dates'  => ['en' => 'Provide check-in & check-out date!','fil' => 'Ibigay ang petsa ng pagdating at pag-alis!'],
    'cb_pay_now'        => ['en' => 'Pay Now',                 'fil' => 'Bayaran Ngayon'],
    'cb_add_cart'       => ['en' => 'Add to Cart',             'fil' => 'Idagdag sa Cart'],
    'cb_view_cart'      => ['en' => 'View Cart',               'fil' => 'Tingnan ang Cart'],

    // ── Breadcrumbs / shared ──────────────────────────────────────────
    'bc_home'           => ['en' => 'HOME',                    'fil' => 'TAHANAN'],
    'bc_rooms'          => ['en' => 'ROOMS',                   'fil' => 'MGA SILID'],
    'bc_bookings'       => ['en' => 'BOOKINGS',                'fil' => 'MGA BOOKING'],
    'bc_profile'        => ['en' => 'PROFILE',                 'fil' => 'PROFILE'],
    'bc_cart'           => ['en' => 'CART',                    'fil' => 'CART'],
    'bc_confirm'        => ['en' => 'CONFIRM',                 'fil' => 'KUMPIRMAHIN'],

    // ── Footer ────────────────────────────────────────────────────────
    'footer_rights'     => ['en' => 'All Rights Reserved.',    'fil' => 'Lahat ng Karapatan ay Nakalaan.'],

    // ── Misc ──────────────────────────────────────────────────────────
    'close'             => ['en' => 'Close',                   'fil' => 'Isara'],
    'per_night'         => ['en' => 'per night',               'fil' => 'bawat gabi'],
];

/**
 * Returns the translated string for the given key.
 * Falls back to English if the key or language is not found.
 */
function t(string $key): string {
    $lang  = $_SESSION['lang'] ?? 'en';
    $table = $GLOBALS['_LANG'];
    return htmlspecialchars($table[$key][$lang] ?? $table[$key]['en'] ?? $key, ENT_QUOTES, 'UTF-8');
}