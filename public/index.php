<?php
/** Testing wp api oauth2 plugin
 *
 * @package test
 */

namespace Youdaman\Oauth2Test;

require '../vendor/autoload.php';

// phpinfo();
// exit;

session_start();

$client_id = 'erzelqpu26lg';
$client_secret = 'Ald5C3xay0KjOd1cuMlEvKFw7LC8ALQas28hu5wvNhClE9li';
$redirect_uri = 'https://empty.wp';
// $redirect_uri = 'https://203b-115-70-254-53.ngrok-free.app';

$client = new \GuzzleHttp\Client( array( 'verify' => 'S:\Local Sites\empty\app\cacert.pem' ) );
// $client = new \GuzzleHttp\Client( array( 'verify' => 'S:\Local Sites\empty\app\public\wp-includes\certificates\ca-bundle.crt' ) );

$provider = new \League\OAuth2\Client\Provider\GenericProvider(
	array(
		'clientId'                => $client_id,
		'clientSecret'            => $client_secret,
		'redirectUri'             => $redirect_uri,
		'urlAuthorize'            => 'https://test.wp/wp-json/oauth2/authorize',
		'urlAccessToken'          => 'https://test.wp/wp-json/oauth2/access_token',
		'urlResourceOwnerDetails' => 'https://test.wp/wp-json/wp/v2/posts',
	),
	array(
		'httpClient'              => $client,
	)
);

// If we don't have an authorization code then get one
if ( !isset( $_GET['code'] ) ) {

	$options = array(
		'scope' => array( 'openid email profile offline_access accounting.transactions accounting.settings' ),
	);

	// Fetch the authorization URL from the provider; this returns the
	// urlAuthorize option and generates and applies any necessary parameters (e.g. state).
	$authorization_url = $provider->getAuthorizationUrl( $options );

	// Get the state generated for you and store it to the session.
	$_SESSION['oauth2state'] = $provider->getState();

	// Redirect the user to the authorization URL.
	header( 'Location: ' . $authorization_url );
	exit();

	// Check given state against previously stored one to mitigate CSRF attack
} elseif ( empty( $_GET['state'] ) || ( $_GET['state'] !== $_SESSION['oauth2state'] ) ) {
	unset( $_SESSION['oauth2state'] );
	exit( 'Invalid state' );

	// Redirect back from Xero with code in query string param
} else {

	try {
		// Try to get an access token using the authorization code grant.
		$access_token = $provider->getAccessToken('authorization_code', array(
			'code' => $_GET['code'],
		));

		// We have an access token, which we may use in authenticated requests
		// Retrieve the array of connected orgs and their tenant ids.
		$options['headers']['Accept'] = 'application/json';
		// $connectionsResponse = $provider->getAuthenticatedRequest(
		$posts_response = $provider->getAuthenticatedRequest(
			'GET',
			'https://test.wp/wp-json/wp/v2/posts',
			$access_token->getToken(),
			$options
		);

		// $xeroTenantIdArray = $provider->getParsedResponse($connectionsResponse);
		$posts_array = $provider->getParsedResponse( $posts_response );

		echo "<h1>Congrats</h1>";
		echo "access token: " . $access_token->getToken() . "<hr>";
		echo "refresh token: " . $access_token->getRefreshToken() . "<hr>";
		// echo "xero tenant id: " . $xeroTenantIdArray[0]['tenantId'] . "<hr>";
		echo "posts: <pre>" . $posts_array . "</pre><hr>";

		// The provider provides a way to get an authenticated API request for
		// the service, using the access token;
		// the xero-tentant-id header is required
		// the accept header can be either 'application/json' or 'application/xml'
		// $options['headers']['xero-tenant-id'] = $xeroTenantIdArray[0]['tenantId'];
		// $options['headers']['Accept'] = 'application/json';

		// $request = $provider->getAuthenticatedRequest(
		// 	'GET',
		// 	'https://api.xero.com/api.xro/2.0/Organisation',
		// 	$access_Token,
		// 	$options
		// );

		// echo 'Organisation details:<br><textarea width: "300px"  height: 150px; row="50" cols="40">';
		// var_export($provider->getParsedResponse($request));
		// echo '</textarea>';
	} catch ( \League\OAuth2\Client\Provider\Exception\IdentityProviderException $e ) {
		// Failed to get the access token or user details.
		exit( $e->getMessage() );
	}
}

?>

<html>
<head>
	<title>php oauth2 example</title>
	<style>
		textarea { border:1px solid #999999;  width:75%; height: 75%;  margin:5px 0; padding:3px;  }
	</style>
</head>
<body>
<h3>Success!</h3>
</body>
</html>
