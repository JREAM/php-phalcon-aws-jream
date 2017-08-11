<?php
use \Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use \Phalcon\Http\Response\Cookies;
use \Phalcon\Crypt;

/**
 * ==============================================================
 * Services
 * =============================================================
 */
$di = new Phalcon\DI\FactoryDefault();


/**
 * ==============================================================
 * Set Encryption Token for all Cookies
 * =============================================================
 */
$di->set('crypt', function () use ($config) {
    $crypt = new Crypt();
    $crypt->setKey($config->cookie_hash);
    return $crypt;
});


/**
 * ==============================================================
 * Cookie Encryption is on by default,
 *          this just ensures it for my personal memory.
 * =============================================================
 */
$di->set('cookies', function () {
    $cookies = new Cookies();
    $cookies->useEncryption(true);
    return $cookies;
});


/**
 * ==============================================================
 * Session
 * =============================================================
 */
$di->setShared('session', function () {
    $session = new \Phalcon\Session\Adapter\Files();

    $session->start();
    return $session;
});


/**
 * ==============================================================
 * Session Flash Data
 * =============================================================
 */
$di->setShared('flash', function($mode = 'session') {

    $mode = strtolower(trim($mode));
    $validModes = ['session', 'direct'];
    if ( ! in_array($mode, $validModes ) ) {
        throw new \InvalidArgumentException('Flash Message Error, tried using $mode, must use: ' . implode(',', $mode));
    }

    // There is a Direct, and a Session
    $flash = new \Phalcon\Flash\Session([
        'error'     => 'alert alert-danger',
        'success'   => 'alert alert-success',
        'notice'    => 'alert alert-info',
        'warning'   => 'alert alert-warning',
    ]);
    return $flash;
});


/**
 * ==============================================================
 * Make Config and Api Accessible where we have DI
 * =============================================================
 */
$di->setShared('config', function() use ($config) {
    return $config;
});

$di->setShared('api', function () use ($api) {
    return $api;
});

/**
 * ==============================================================
 * Apply the Router
 * =============================================================
 */
$di->setShared('router', function() {
    return require CONFIG_DIR . '/routes.php';
});


/**
 * ==============================================================
 * The URL component is used to generate all kind of urls
 *  in the application
 * =============================================================
 */
$di->setShared('url', function () use ($config) {
    $url = new Phalcon\Mvc\Url();
    $url->setBaseUri(\BASE_URI);
    return $url;
});


/**
 * ==============================================================
 * Custom Dispatcher (Overrides the default)
 * =============================================================
 */
$di->setShared('dispatcher', function() use ($di) {

    $eventsManager = $di->getShared('eventsManager');
    $eventsManager->attach('dispatch', new Event\Dispatch());
    $eventsManager->attach('dispatch', new Component\Permission());

    // -----------------------------------
    // Return the new dispatcher with the
    // Events Manager Attached
    // -----------------------------------
    $dispatcher = new \Phalcon\Mvc\Dispatcher();
    $dispatcher->setEventsManager($eventsManager);
    return $dispatcher;
});


/**
 * ==============================================================
 * Register Component libraries
 * =============================================================
 */
$di->setShared('component', function() {
    $obj = new stdClass();
    $obj->cookies = new \Component\Cookies();
    $obj->helper  = new \Component\Helper();
    $obj->email   = new \Component\Email();
    return $obj;
});


/**
 * ==============================================================
 * View component
 * =============================================================
 */
$di->setShared('view', function () use ($config) {
    $view = new \Phalcon\Mvc\View();
    $view->setViewsDir($config->application->viewsDir);
    $view->registerEngines([
        '.volt' => function ($view, $di) use ($config) {

            // ------------------------------------------------
            // Volt Template Engine
            // ------------------------------------------------
            $volt = new VoltEngine($view, $di);

            $volt->setOptions([
                'compiledPath' => CACHE_DIR,
                'compiledSeparator' => '_',
                // ------------------------------------------------
                // For DEV, to prevent Caching annoyances
                // ------------------------------------------------
                'compileAlways' => true
            ]);

            // Use Cache for live site
            if (\STAGE == 'live') {
                $voltOptions['compileAlways'] = false;
            }

            return $volt;
        },
        // --------------------------------------------------------------------
        // The Default Templating (However, VOLT is cleaner)
        // --------------------------------------------------------------------
        '.phtml' => '\Phalcon\Mvc\View\Engine\Php'
    ]);

    // Used for global variables (See: events/afterExecuteRoute)
    $view->system = new \stdClass();

    return $view;
});


/**
 * ==============================================================
 * Database Connection
 * =============================================================
 */
$di->set('db', function () use ($di, $config) {
    $eventsManager = $di->getShared('eventsManager');
    $eventsManager->attach('db', new Event\Database());

    $database = new Phalcon\Db\Adapter\Pdo\Mysql((array) $config->database);
    $database->setEventsManager($eventsManager);

    return $database;
});


/**
 * ==============================================================
 * Redis (For Caching)
 * =============================================================
 */
$redis = new \Redis();
$redis->connect("localhost", 6379);


/**
 * ==============================================================
 * Model Manager
 * =============================================================
 */
$di->set('modelsManager', function() {
    \Phalcon\Mvc\Model::setup(['ignoreUnknownColumns' => true]);
    return new \Phalcon\Mvc\Model\Manager();
});


/**
 * ==============================================================
 * Model Meta Data (Uses Redis)
 * =============================================================
 */
$di->set('modelsMetadata', function() use ($redis) {
    return new \Phalcon\Mvc\Model\MetaData\Redis([
        "lifetime" => 3600,
        "redis"    => $redis
    ]);
});


/**
 * ==============================================================
 * ORM And Front-end Caching
 * =============================================================
 */
$di->set('modelsCache', function() use ($redis) {

    // Cache data for one day by default
    // It's cleared using fabfile for a deploy
    $frontCache = new \Phalcon\Cache\Frontend\Data([
        "lifetime" => 86400
    ]);

    // Redis connection settings
    $cache = new \Phalcon\Cache\Backend\Redis($frontCache, [
        "redis" => $redis
    ]);

    return $cache;
});


/**
 * ==============================================================
 * Set the Security for Usage
 * =============================================================
 */
$di->setShared('security', function(){
    $security = new \Phalcon\Security();
    $security->setWorkFactor(12);
    return $security;
});


/**
 * ==============================================================
 * Sentry Error Logging
 * =============================================================
 */
$di->setShared('sentry', function() use ($api) {
    return (new Raven_Client( getenv('GET_SENTRY') ))->install();
});


/**
 * ==============================================================
 * Local Error Logging
 * =============================================================
 */
if (STAGE != 'live') {
    // This is ONLY used locally
    $di->setShared('whoops', function() {
        $whoops = new \Whoops\Run;
        return $whoops;
    });

    $whoops = $di->get('whoops')->register();
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}


/**
 * ==============================================================
 * Email Transport to send Mail
 * =============================================================
 */
$di->setShared('email', function(array $data) use ($di, $api) {
    $to       = new \SendGrid\Email($data['to_name'], $data['to_email']);
    $from     = new \SendGrid\Email($data['from_name'], $data['from_email']);
    $content  = new \SendGrid\Content("text/html", $data['content']);

    $mail     = new \SendGrid\Mail($from, $data['subject'], $to, $content);

    $sg       = new \SendGrid(getenv('SENDGRID_KEY'));
    $response = $sg->client->mail()->send()->post($mail);

    // Catch a Non 200 Error
    if ( ! in_array($response->statusCode(), [200, 201, 202])) {
        $di->get('sentry')->captureMessage(
            sprintf("Headers: %s | ErrorCode: %s",
                $response->headers(),
                $response->statusCode()
            )
        );
    }

    return $response;
});


/**
 * ==============================================================
 * API: Facebook
 * =============================================================
 */
$di->setShared('facebook', function () use ($api) {
    return new \Facebook\Facebook([
        'app_id'                => getenv('FACEBOOK_APP_ID'),
        'app_secret'            => getenv('FACEBOOK_APP_SECRET'),
        'default_graph_version' => getenv('FACEBOOK_DEFAULT_GRAPH_VERSION')
    ]);
});


/**
 * ==============================================================
 * API: Google
 * =============================================================
 */
$di->setShared('google', function() use ($api) {

    $scope = $api->google->scope;

    $middleware = Google\Auth\ApplicationDefaultCredentials::getMiddleware($scope);

    $stack = GuzzleHttp\HandlerStack::create();
    $stack->push($middleware);

    return new GuzzleHttp\Client([
        'handler'   => $stack,
        'base_uri'  => 'https://www.googleapis.com',
        'auth'      => 'google_auth'  // authorize all requests
    ]);

});


/**
 * ==============================================================
 * API: MailChimp
 * Only Used to Subscribe (Should Change Someday)
 * =============================================================
 */
$di->setShared('mailchimp', function() use ($api) {
    return new \Mailchimp( getenv('MAILCHIMP_KEY') );
});


// End of File
// --------------------------------------------------------------------
