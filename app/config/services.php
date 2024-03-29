<?php

use Phalcon\Crypt;
use Phalcon\Http\Response\Cookies;
use Phalcon\Security;
use Phalcon\Flash\Session as Flash;
use Phalcon\Session\Adapter\Files as SessionFiles;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\View;
use Phalcon\Db\Adapter\Pdo\Mysql as MySQL;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Filter;
use Phalcon\Di;
use Phalcon\DiInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * ==============================================================
 * Validate Correct Mode
 * =============================================================
 */
if (APPLICATION_ENV === APP_PRODUCTION && (strpos($api->stripe->publishableKey, 'test') !== false)) {
  throw new Exception('In PRODUCTION < Stripe > is in the wrong MODE.');
}

if (APPLICATION_ENV === APP_PRODUCTION && $api->paypal->testMode) {
  throw new Exception('In PRODUCTION < Paypal > is in the wrong MODE.');
}

/**
 * ==============================================================
 * Services
 * =============================================================
 */
$di = new \Phalcon\DI\FactoryDefault();


$eventsManager = new EventsManager;
$di->setShared('eventsManager', $eventsManager);


/**
 * ==============================================================
 * Set the Security for Usage
 *
 * @important This comes before the Session
 * =============================================================
 */
$di->setShared('logger', function () use ($config) {

  $log = new Logger('error_log');
  $log->pushHandler(new StreamHandler($config->application->logsDir . '/error.log', Logger::WARNING));

  return $log;
});


/**
 * ==============================================================
 * Set the Security for Usage
 *
 * @important This comes before the Session
 * =============================================================
 */
$di->setShared('security', function () {
  $security = new Security();
  $security->setWorkFactor(12);

  return $security;
});


/**
 * ==============================================================
 * Set Encryption Token for all Cookies
 * =============================================================
 */
$di->set('crypt', function () use ($config) {
  $crypt = new Crypt();
  $crypt->setKey($config->get('cookie_hash'));

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
$di->setShared('session', function () use ($di) {
    // Start a new Session for every user.
  $session = new SessionFiles();
  if (!$session->isStarted()) {
    $session->start();
  }

  return $session;
});

/**
 * ==============================================================
 * Session Flash Data
 * =============================================================
 */
$di->setShared('flash', function (string $mode = 'session') {

  $mode = strtolower(trim($mode));
  $validModes = ['session', 'direct'];
  if (!in_array($mode, $validModes, true)) {
    throw new \InvalidArgumentException('Flash Message Error, tried using $mode, must use: ' .
      implode(',', $validModes));
  }

    // There is a Direct, and a Session
  $flash = new Flash([
    'error' => 'alert alert-danger',
    'success' => 'alert alert-success',
    'notice' => 'alert alert-info',
    'warning' => 'alert alert-warning',
  ]);

  return $flash;
});


/**
 * ==============================================================
 * Make Config/Api Accessible where we have DI
 * =============================================================
 */
$di->setShared('config', function () use ($config) {
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
$di->setShared('router', function () use ($config) {
  return require $config->application->configDir . 'routes.php';
});


/**
 * ==============================================================
 * The URL component is used to generate all kind of urls
 *  in the application
 * =============================================================
 */
$di->setShared('url', function () use ($config) {
  $url = new \Phalcon\Mvc\Url();
  $url->setBaseUri($config->get('baseUri'));

  return $url;
});


/**
 * ==============================================================
 * Custom Dispatcher (Overrides the default)
 * =============================================================
 */
$di->setShared('dispatcher', function () use ($di, $eventsManager) {

  $eventsManager->attach('dispatch', new Plugins\PermissionPlugin());
  $eventsManager->attach('dispatch', new Middleware\Dispatch());

  $dispatcher = new Dispatcher();
  $dispatcher->setEventsManager($eventsManager);

  return $dispatcher;
});


/**
 * ==============================================================
 * Register Component libraries
 * =============================================================
 */
$di->setShared('component', function () {
  $obj = new \stdClass();
  $obj->email = new EmailComponent();

  return $obj;
});


/**
 * ==============================================================
 * HashID's (Encode/Decode, primarily for JS resp/req)
 * =============================================================
 */
$di->setShared('hashids', function () use ($config) {
    // @sample
    // encode(1); encode([1,2,3]) encodeHex('507f1f77bcf86cd799439011')
    // decode(value), decode(hex_value)

    // Passing a unique string makes items unique
  $hashids = new Hashids\Hashids(
    $config->get('hashids_hash'),
    6,
    'abcdefghijklmnopqrstuvwxyz'
  );

  return $hashids;
});

/**
 * ==============================================================
 * View component
 * =============================================================
 */
$di->setShared('view', function () use ($config, $di) {
  $view = new View();
  $view->setViewsDir($config->application->viewsDir);
  $view->registerEngines([
    '.volt' => function (View $view, DiInterface $di) use ($config) {

            // APP_TEST is set from the TEST environment
      $path = APPLICATION_ENV === APP_TEST ? DOCROOT . 'tests/_cache/' : $config->application->cacheDir;

            // ------------------------------------------------
            // Volt Template Engine
            // ------------------------------------------------
      $volt = new VoltEngine($view, $di);

      $volt->setOptions([
        'compiledPath' => $path,
        'compiledSeparator' => '_',
        'compileAlways' => APPLICATION_ENV !== APP_PRODUCTION,
      ]);

      $compiler = $volt->getCompiler();

            // @Functions
            // @example {{ function(item) }}
      $compiler->addFunction('strtotime', 'strtotime');
      $compiler->addFunction('sprintf', 'sprintf');
      $compiler->addFunction('str_replace', 'str_replace');
      $compiler->addFunction('is_a', 'is_a');
      $compiler->addFunction('pageid', function ($str, $expr) {
        return str_replace('-page', '', $str);
      });

            // @Markdown
            // @example {{ item|filter }}
      $compiler->addFilter('markdown', function ($resolvedArgs, $exprArgs) {
        return '\\Plugins\\VoltFilters::markdown(' . $resolvedArgs . ');';
      });

      return $volt;
    },
        // --------------------------------------------------------------------
        // The Default Templating (However, VOLT is cleaner)
        // --------------------------------------------------------------------
    '.phtml' => '\Phalcon\Mvc\View\Engine\Php',
  ]);

    // Used for global variables (See: middleware/afterExecuteRoute)
  $view->setVar('version', \Phalcon\Version::get());

  return $view;
});


/**
 * ==============================================================
 * Database Connection
 * =============================================================
 */
$di->set('db', function () use ($di, $config, $eventsManager) {
  $eventsManager->attach('db', new Middleware\Database());

  $database = new MySQL((array)$config->get('database'));
  $database->setEventsManager($eventsManager);

  return $database;
});


/**
 * ==============================================================
 * Redis (For Caching)
 * =============================================================
 */
$redis = new \Redis();
$redis->connect(
  $config->memory->redis->host,
  $config->memory->redis->port
);
$redis->select($config->memory->redis->db);  // Use Database 10

/**
 * ==============================================================
 * Markdown Parser
 * =============================================================
 */
$di->setShared('markdown', function () {
    // $example: $parsedown->parse('#markdown here');
  return new \Parsedown();
});


$di->setShared('filter', function () {
  $filter = new Filter();
  $filter->add('slug', function (string $value) {
    return new Phalcon\Utils\Slug($value);
  });

  return $filter;
});

/**
 * ==============================================================
 * Model Manager
 * =============================================================
 */
$di->set('modelsManager', function () {
  \Phalcon\Mvc\Model::setup(['ignoreUnknownColumns' => true]);
  return new \Phalcon\Mvc\Model\Manager();
});


/**
 * ==============================================================
 * Model Meta Data (Uses Redis)
 * =============================================================
 */
$di->set('modelsMetadata', function () use ($redis) {
  return new \Phalcon\Mvc\Model\MetaData\Redis([
    "lifetime" => 3600,
    "redis" => $redis,
  ]);
});


/**
 * ==============================================================
 * ORM And Front-end Caching
 * =============================================================
 */
$di->set('modelsCache', function () use ($redis) {

    // Cache data for one day by default
    // It's cleared using fabfile for a deploy
  $frontCache = new \Phalcon\Cache\Frontend\Data([
    "lifetime" => 86400,
  ]);

    // Redis connection settings
  $cache = new \Phalcon\Cache\Backend\Redis($frontCache, [
    "redis" => $redis,
  ]);

  return $cache;
});


/**
 * ==============================================================
 * Sentry Error Logging
 * =============================================================
 */
$di->setShared('sentry', function () use ($api) {
  return (new Raven_Client(getenv('GET_SENTRY')))->install();
});

/**
 * ==============================================================
 * Local Error Logging
 * =============================================================
 */
if (\APPLICATION_ENV !== \APP_PRODUCTION) {
    // This is ONLY used locally

  $whoops = new \Whoops\Run();

    // This is so it is accessible in the global Middleware Dispatcher
  $di->setShared('whoops', function () use ($whoops) {
    return $whoops;
  });

    // The default page handler
  $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);

    // Push another handler if it is an AJAX call for JSON responses.
  if (\Whoops\Util\Misc::isAjaxRequest()) {
    $jsonHandler = new \Whoops\Handler\JsonResponseHandler();
    $jsonHandler->setJsonApi(true);
    $whoops->pushHandler($jsonHandler);
  }

  $whoops->register();

}


/**
 * ==============================================================
 * Email Transport to send Mail
 * =============================================================
 */
$di->setShared('s3', function () {
  return new Aws\S3\S3Client([
    'version' => getenv('AWS_S3_VERSION'),
    'region' => getenv('AWS_S3_REGION'),
    'credentials' => [
      'key' => getenv('AWS_S3_ACCESS_KEY'),
      'secret' => getenv('AWS_S3_ACCESS_SECRET_KEY='),
    ],
  ]);
});


/**
 * ==============================================================
 * Faker, do NOT put this in the dev-dependencies so accidents don't
 * happen. Regardless, Ill attempt to protect it anyways.
 * =============================================================
 */
$di->set('faker', function () {
  if (\APPLICATION_ENV !== \APP_PRODUCTION) {
    return \Faker\Factory::create();
  }

  return false;
});

$di->set('fakerData', function () {
    // Allows me to get data and have it empty if I like with one rule check
  $faker = false;
  if (\APPLICATION_ENV !== \APP_PRODUCTION) {
    $faker = \Faker\Factory::create();
  }

  return (object)[
    'NOTE' => 'This is ALL completely FAKE data for TESTING.',
    'alias' => $faker ? sprintf('faker%s%s%s', str_replace('.', '', $faker->title), $faker->firstName, $faker->lastName) : null,
    'email' => $faker ? 'faker_' . $faker->safeEmail : null,
    'password' => $faker ? $faker->password : null,
    'firstName' => $faker ? $faker->firstName : null,
    'lastName' => $faker ? $faker->lastName : null,
    'question' => $faker ? $faker->sentence(random_int(15, 35)) : null,
    'questionReply' => $faker ? $faker->sentence(random_int(5, 15)) : null,
    'address' => $faker ? $faker->streetAddress : null,
    'city' => $faker ? $faker->city : null,
    'zip' => $faker ? $faker->postcode : null,
    'country' => $faker ? $faker->country : null,
    'phone' => $faker ? $faker->phoneNumber : null,
    'ccType' => $faker ? $faker->creditCardType : null,
    'ccNumber' => $faker ? $faker->creditCardNumber : null,
    'ccNumberStripe' => $faker ? '4242424242424242' : null,
    'ccExpMonth' => $faker ? 12 : null,
    'ccExpYear' => $faker ? date('Y', strtotime('+2 years')) : null,
  ];
});

/**
 * ==============================================================
 * Email Transport to send Mail
 * =============================================================
 */
$di->setShared('email', function (array $data) use ($di) {

    // For Debugging
  if (\APPLICATION_ENV !== \APP_PRODUCTION && getenv('DEBUG_EMAIL')) {
    $transport = (new Swift_SmtpTransport('localhost', 1025));
    $mailer = new Swift_Mailer($transport);

        // Create a message
    $message = (new Swift_Message($data['subject']))->setFrom([$data['from_email'] => $data['from_name']])->setTo([$data['to_email'] => $data['to_name']])->setBody($data['content']);

    return $mailer->send($message);
  }

  $to = new \SendGrid\Email($data['to_name'], $data['to_email']);
  $from = new \SendGrid\Email($data['from_name'], $data['from_email']);
  $content = new \SendGrid\Content("text/html", $data['content']);

  $mail = new \SendGrid\Mail($from, $data['subject'], $to, $content);

  $sg = new \SendGrid(getenv('SENDGRID_KEY'));
  $response = $sg->client->mail()->send()->post($mail);

    // Catch a Non 200 Error
  if (!in_array($response->statusCode(), [200, 201, 202], true)) {
    $di->get('sentry')->captureMessage(sprintf("Headers: %s | ErrorCode: %s", $response->headers(), $response->statusCode()));
  }

  return $response;
});

/**
 * ==============================================================
 * SparkPost for Email
 * =============================================================
 */
$di->setShared('sparkpost', function (array $data) use ($di) {
  $httpClient = new Http\Adapter\Guzzle6\Client\GuzzleAdapter(new GuzzleHttp\Client());
  $sparky = new SparkPost\SparkPost($httpClient, ['key' => getenv('SPARKPOST')]);
  $sparky->setOptions(['async' => false]);

  $promise = $sparky->transmissions->post([
    'content' => [
      'from' => [
        'name' => 'JREAM',
        'email' => 'notify@jream.com',
      ],
      'subject' => $data['subject'],
      'html' => '<html><body><h1>Congratulations, {{name}}!</h1><p>You just sent your very first mailing!</p></body></html>',
      'text' => 'Congratulations, {{name}}!! You just sent your very first mailing!',
    ],
    'substitution_data' => ['name' => $data['name']],
    'recipients' => [
      [
        'address' => [
          'name' => $data['name'],
          'email' => $data['email'],
        ],
      ],
    ],
  ]);

  $promise = $sparky->transmissions->get();

  try {
    $response = $promise->wait();
    return [
      'code' => $response->getStatusCode(),
      'body' => $response->getBody()
    ];
  } catch (\Exception $e) {
    return [
      'code' => $e->getCode(),
      'msg' => $e->getMessage()
    ];
  }
});

/**
 * ==============================================================
 * API: Hybrid Auth (Social Login)
 *
 * @important Always place all calls within a try/catch
 * =============================================================
 */
$di->setShared('hybridAuth', function () use ($api) {

    // Make Absolute URL Paths
  foreach ($api->social_auth->providers as $provider => $data) {
    if (property_exists($data, 'callback')) {
      $callback = \Library\Url::get($api->social_auth->providers->{$provider}->callback);
      $api->social_auth->providers->{$provider}->callback = $callback;
    }
  }

  return new \Hybridauth\Hybridauth(objectToArray($api->social_auth));
});


/**
 * ==============================================================
 * API: Stripe
 * =============================================================
 */
\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET'));

/**
 * ==============================================================
 * API: Paypal
 * =============================================================
 */
$di->setShared('paypal', function () use ($api) {
    // Paypal Express
    // @source  https://omnipay.thephpleague.com/gateways/configuring/
  $paypal = Omnipay\Omnipay::create('PayPal_Express');
  $paypal->setUsername(getenv($api->paypal->username));
  $paypal->setPassword(getenv($api->paypal->password));
  $paypal->setSignature(getenv($api->paypal->signature));

  if ($api->paypal->testMode) {
    $paypal->setTestMode(true);
  }

  return $paypal;
});


/**
 * ==============================================================
 * PHP Console for Debugging (Requires Chrome Extension)
 *
 * @note:   I want this instantiated so it can go on/off without
 *          adding many if statements. Done with the disable() call
 * =============================================================
 */
if (APPLICATION_ENV !== APP_PRODUCTION) {
    // Storage: Logs Debugging as it may become convoluted with Phalcons custom $_SESSION.
    // @important: This must come before the getInstance()
  $storage = new PhpConsole\Storage\File($config->application->logsDir . 'phpconsole.data');
  PhpConsole\Connector::setPostponeStorage($storage);

    // Register PhpConsole as PC::debug($foo), PC::tag($bar), PC::debug('msg')
  $connector = PhpConsole\Connector::getInstance();

    // Shorter Logging for Paths
  $connector->setSourcesBasePath(DOCROOT);

    // This will disable the PHP Console Calls regardless of where it is placed.
  if (!getenv('DEBUG_CONSOLE')) {
    $connector->disable();
  }
  PhpConsole\Helper::register();
}

/**
 * ==============================================================
 *  Set this so that it can be retrieved anywhere!
 * ==============================================================
 */
Di::setDefault($di);
