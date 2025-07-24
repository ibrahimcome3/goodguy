<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">


<!-- ===============================================-->
<!--    Document Title-->
<!-- ===============================================-->
<title>Phoenix</title>


<!-- ===============================================-->
<!--    Favicons-->
<!-- ===============================================-->
<link rel="apple-touch-icon" sizes="180x180" href="phoenix-v1.20.1/public/assets/img/favicons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="phoenix-v1.20.1/public/assets/img/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="phoenix-v1.20.1/public/assets/img/favicons/favicon-16x16.png">
<link rel="shortcut icon" type="image/x-icon" href="phoenix-v1.20.1/public/assets/img/favicons/favicon.ico">
<link rel="manifest" href="phoenix-v1.20.1/public/assets/img/favicons/manifest.json">
<meta name="msapplication-TileImage" content="phoenix-v1.20.1/public/assets/img/favicons/mstile-150x150.png">
<meta name="theme-color" content="#ffffff">
<script src="phoenix-v1.20.1/public/vendors/simplebar/simplebar.min.js"></script>
<script src="phoenix-v1.20.1/public/assets/js/config.js"></script>


<!-- ===============================================-->
<!--    Stylesheets-->
<!-- ===============================================-->
<link href="phoenix-v1.20.1/public/vendors/dropzone/dropzone.css" rel="stylesheet">
<link href="phoenix-v1.20.1/public/vendors/choices/choices.min.css" rel="stylesheet">
<link href="phoenix-v1.20.1/public/vendors/flatpickr/flatpickr.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700;800;900&amp;display=swap"
    rel="stylesheet">
<link href="phoenix-v1.20.1/public/vendors/simplebar/simplebar.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
<link href="phoenix-v1.20.1/public/assets/css/theme-rtl.min.css" type="text/css" rel="stylesheet" id="style-rtl">
<link href="phoenix-v1.20.1/public/assets/css/theme.min.css" type="text/css" rel="stylesheet" id="style-default">
<link href="phoenix-v1.20.1/public/assets/css/user-rtl.min.css" type="text/css" rel="stylesheet" id="user-style-rtl">
<link href="phoenix-v1.20.1/public/assets/css/user.min.css" type="text/css" rel="stylesheet" id="user-style-default">
<script>
    var phoenixIsRTL = window.config.config.phoenixIsRTL;
    if (phoenixIsRTL) {
        var linkDefault = document.getElementById('style-default');
        var userLinkDefault = document.getElementById('user-style-default');
        linkDefault.setAttribute('disabled', true);
        userLinkDefault.setAttribute('disabled', true);
        document.querySelector('html').setAttribute('dir', 'rtl');
    } else {
        var linkRTL = document.getElementById('style-rtl');
        var userLinkRTL = document.getElementById('user-style-rtl');
        linkRTL.setAttribute('disabled', true);
        userLinkRTL.setAttribute('disabled', true);
    }
</script>