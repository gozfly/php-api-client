<?php
/**
 * gozfly-client
 * index.php
 *
 * PHP Version 5
 *
 * @category Production
 * @package  Default
 * @author   Jonathan Nu�ez <je.nunez@gozfly.com>
 * @date     8/17/17 22:47
 * @license  http://gozfly.com/license.txt gozfly-client License
 * @version  GIT: 1.0
 * @link     http://gozfly.com/projects/gozfly-client
 */

// add Composer autoloader
include_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

// import client class
use Gozfly\Client;
use Gozfly\Scope;

// import environment variables from the environment file
$dotenv = new Dotenv\Dotenv(dirname(__DIR__));
$dotenv->load();

// we need a session to keep intermediate results
// you can use your own session persistence management
// client doesn't depend on it
session_start();

// instantiate the Gozfly client
$client = new Client(
    getenv('GOZFLY_CLIENT_ID'),
    getenv('GOZFLY_CLIENT_SECRET')
);


if (isset($_GET['code'])) { // we are returning back from Gozfly with the code
    if (isset($_GET['state']) &&  // and state parameter in place
        isset($_SESSION['state']) && // and we have have stored state
        $_GET['state'] === $_SESSION['state'] // and it is our request
    ) {
        try {
            // you have to set initially used redirect url to be able
            // to retrieve access token
            $client->setRedirectUrl($_SESSION['redirect_url']);
            // retrieve access token using code provided by Gozfly
            $accessToken = $client->getAccessToken($_GET['code']);
            echo 'Access token:';
            pp($accessToken); // print the access token content
            echo 'Profile:';
            // perform api call to get profile information
            $profile = $client->get(
                'people/~:(id,email-address,first-name,last-name)'
            );
            pp($profile); // print profile information

            $share = $client->post(
                'people/~/shares',
                [
                    'comment' => 'Checkout this amazing PHP SDK for Gozfly!',
                    'content' => [
                        'title' => 'PHP Client for Gozfly API',
                        'description' => 'OAuth 2 flow, composer Package',
                        'submitted-url' => 'https://www.github.com/gozfly/gozfly-api-php-client',
                        'submitted-image-url' => 'https://www.github.com/fluidicon.png',
                    ],
                    'visibility' => [
                        'code' => 'anyone'
                    ]
                ]
            );
            pp($share);
        } catch (\Gozfly\Exception $exception) {
            // in case of failure, provide with details
            pp($exception);
            pp($_SESSION);
        }
        echo '<a href="/">Start over</a>';
    } else {
        // normally this shouldn't happen unless someone sits in the middle
        // and trying to override your state
        // or you are trying to change saved state during linking
        echo 'Invalid state!';
        pp($_GET);
        pp($_SESSION);
        echo '<a href="/">Start over</a>';
    }

} elseif (isset($_GET['error'])) {
    // if you cancel during linking
    // you will be redirected back with reason
    pp($_GET);
    echo '<a href="/">Start over</a>';
} else {
    // define desired list of scopes
    $scopes = Scope::getValues();
    $loginUrl = $client->getLoginUrl($scopes); // get url on Gozfly to start linking
    $_SESSION['state'] = $client->getState(); // save state for future validation
    $_SESSION['redirect_url'] = $client->getRedirectUrl(); // save redirect url for future validation
    echo 'LoginUrl: <a href="'.$loginUrl.'">' . $loginUrl. '</a>';
}

/**
 * Pretty print whatever passed in
 *
 * @param mixed $anything
 */
function pp($anything)
{
    echo '<pre>' . print_r($anything, true) . '</pre>';
}
