
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="OwyLkMsH9jv5qjWXoHjuS21Vhrcuz1qy1GstT02l8Sg">
    <meta name="csrf" data-key="{% if tokenKey is defined %}{{ tokenKey }}{% endif %}" data-token="{% if token is defined %}{{ token }}{% endif %}" content="">
    {{ get_title() }}

    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('images/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ url('images/favicon/manifest.json') }}">
    <link rel="mask-icon" href="{{ url('images/favicon/safari-pinned-tab.svg') }}" color="#303030">
    <meta name="theme-color" content="#303030">

    <!-- Dependencies App -->
    <link rel="stylesheet" href="{{ url('vendor/fonts.css') }}" type="text/css">

    <!-- App -->
    <link rel="stylesheet" href="{{ url('css/app.css') }}" type="text/css">

    <!-- JS Dependencies (Must Come First) -->
    <script src="{{ url('vendor/modernizr-custom.js') }} "></script>
    <script src="{{ url('vendor/jquery.min.js') }} "></script>
