    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="google-site-verification" content="OwyLkMsH9jv5qjWXoHjuS21Vhrcuz1qy1GstT02l8Sg">
    <meta name="csrf" id="csrf" data-key="{% if jsGlobal['csrf']['tokenKey'] is defined %}{{ jsGloba['csrf']['tokenKey'] }}{% endif %}" data-token="{% if jsGlobal['csrf']['token'] is defined %}{{ jsGlobal['csrf']['token'] }}{% endif %}" content="">
    {{ get_title() }}

    <!-- Favorite Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('images/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ url('images/favicon/manifest.json') }}">
    <link rel="mask-icon" href="{{ url('images/favicon/safari-pinned-tab.svg') }}" color="#303030">
    <meta name="theme-color" content="#303030">

    <!-- Dependencies App -->
    <link rel="stylesheet" href="{{ url('vendor/fonts.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ url('vendor/sweetalert2.min.css') }}" type="text/css">

    <!-- App -->
    <link rel="stylesheet" href="{{ url('css/app.css') }}{{ cacheBust }}" type="text/css">

    <!-- JS Dependencies (Must Come First) -->
    <script src="{{ url('vendor/modernizr-custom.js') }} "></script>
    <script src="{{ url('vendor/jquery.min.js') }} "></script>
    <script src="{{ url('vendor/bottle.min.js') }} "></script>

    <!-- JS Apply to System App -->
    <script>
        var bottle = new Bottle();
        bottle.service('settings', function() {

        });
        {# Passed into the main JS files #}
        window.userId = '{{ jsGlobal['user_id'] }}';
        window.baseUrl = '{{ jsGlobal['base_url'] }}';

        // Wait for DOM to capture
        $(document).ready(function() {
            window.pageId = $('body').attr('id');
        });

        window.api = {
          stripe: '{{ api.stripe.publishableKey }}'
        };
        window.notifications = {
            'error': '{{ jsGlobal['notifications']['error'] }}',
            'success': '{{ jsGlobal['notifications']['success'] }}',
            'info': '{{ jsGlobal['notifications']['info'] }}',
            'warn': '{{ jsGlobal['notifications']['warn'] }}'
        };
        window.routes = {
            'prev': {
                'controller': {{ jsGlobal['routes']['prev']['controller'] }},
                'action': {{ jsGlobal['routes']['prev']['action'] }},
                'params': {{ jsGlobal['routes']['prev']['params'] }},
                'params_str': {{ jsGlobal['routes']['prev']['paramsStr'] }},
                'full': {{ jsGlobal['routes']['prev']['full'] }},
            },
            'current': {
                'controller': '{{ jsGlobal['routes']['current']['controller'] }}',
                'action': '{{ jsGlobal['routes']['current']['action'] }}',
                'params': {{ jsGlobal['routes']['current']['params'] }},
                'params_str': '{{ jsGlobal['routes']['current']['paramsStr'] }}',
                'full': '{{ jsGlobal['routes']['current']['full'] }}',
            }
        };
    </script>
