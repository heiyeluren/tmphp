<?php

/**
 * Http Simple Client
 *
 *  Version 0.9, 6th April 2003 - Simon Willison ( http://simon.incutio.com/ )
 *  Manual: http://scripts.incutio.com/httpclient/
 *  Examples: http://scripts.incutio.com/httpclient/examples.php
 */

/*
*****************************************
Grabbing an HTML page (static method)
*****************************************
$pageContents = HttpClient::quickGet('http://example.com/');

*****************************************
Posting a form and grabbing the response (static method)
*****************************************
$pageContents = HttpClient::quickPost('http://example.com/someForm', array(
    'name' => 'Some Name',
    'email' => 'email@example.com'
));

The static methods are easy to use, but seriously limit the functionality of the class as you cannot access returned headers or use facilities such as cookies or authentication.

*****************************************
A simple GET request using the class
*****************************************
$client = new HttpClient('example.com');
if (!$client->get('/')) {
    die('An error occurred: '.$client->getError());
}
$pageContents = $client->getContent();A GET request with debugging turned on
$client = new HttpClient('example.com');
$client->setDebug(true);
if (!$client->get('/')) {
    die('An error occurred: '.$client->getError());
}
$pageContents = $client->getContent();

*****************************************
A GET request demonstrating automatic redirection
*****************************************
$client = new HttpClient('www.amazon.com');
$client->setDebug(true);
if (!$client->get('/')) {
    die('An error occurred: '.$client->getError());
}
$pageContents = $client->getContent();

*****************************************
Check to see if a page exists
*****************************************
$client = new HttpClient('example.com');
$client->setDebug(true);
if (!$client->get('/thispagedoesnotexist')) {
    die('An error occurred: '.$client->getError());
}
if ($client->getStatus() == '404') {
    echo 'Page does not exist!';
}
$pageContents = $client->getContent();

*****************************************
Fake the User Agent string
*****************************************
$client = new HttpClient('example.com');
$client->setDebug(true);
$client->setUserAgent('Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.3a) Gecko/20021207');
if (!$client->get('/')) {
    die('An error occurred: '.$client->getError());
}
$pageContents = $client->getContent();

*****************************************
Log in to a site via a login form, then request a page
*****************************************
In this example, it is assumed that correctly logging in will result in the server sending a sesssion cookie of some sort. This cookie is resent by the HttpClient class automatically, so a request to a page that requires users to be logged in will now work.

$client = new HttpClient('example.com');
$client->post('/login.php', array(
    'username' => 'Simon',
    'password' => 'ducks'
));
if (!$client->get('/private.php')) {
    die('An error occurred: '.$client->getError());
}
$pageContents = $client->getContent();

*****************************************
Using HTTP authorisation
*****************************************
Note that the class uses the American spelling 'authorization' to fit with the HTTP specification.

$client = new HttpClient('example.com');
$client->setAuthorization('Username', 'Password');
if (!$client->get('/')) {
    die('An error occurred: '.$client->getError());
}
$pageContents = $client->getContent();

*****************************************
Print out the headers from a response
*****************************************
$client = new HttpClient('example.com');
if (!$client->get('/')) {
    die('An error occurred: '.$client->getError());
}
print_r($client->getHeaders());

*****************************************
Setting the maximum number of redirects
*****************************************
$client = new HttpClient('www.amazon.com');
$client->setDebug(true);
$client->setMaxRedirects(3);
$client->get('/');

*/

class HttpSimple {
    // Request vars
    var $host;
    var $port;
    var $path;
    var $method;
    var $postdata = '';
    var $cookies = array();
    var $referer;
    var $accept = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*';
    var $accept_encoding = 'gzip';
    var $accept_language = 'en-us';
    var $user_agent = 'Incutio HttpClient v0.9';
    // Options
    var $timeout = 20;
    var $use_gzip = true;
    var $persist_cookies = true;  // If true, received cookies are placed in the $this->cookies array ready for the next request
                                  // Note: This currently ignores the cookie path (and time) completely. Time is not important, 
                                  //       but path could possibly lead to security problems.
    var $persist_referers = true; // For each request, sends path of last request as referer
    var $debug = false;
    var $handle_redirects = true; // Auaomtically redirect if Location or URI header is found
    var $max_redirects = 5;
    var $headers_only = false;    // If true, stops receiving once headers have been read.
    // Basic authorization variables
    var $username;
    var $password;
    // Response vars
    var $status;
    var $headers = array();
    var $content = '';
    var $errormsg;
    // Tracker variables
    var $redirect_count = 0;
    var $cookie_host = '';

    function HttpSimple($host, $port=80) {
        $this->host = $host;
        $this->port = $port;
    }

    function get($path, $data = false) {
        $this->path = $path;
        $this->method = 'GET';
        if ($data) {
            $this->path .= '?'.$this->buildQueryString($data);
        }
        return $this->doRequest();
    }

    function post($path, $data) {
        $this->path = $path;
        $this->method = 'POST';
        $this->postdata = $this->buildQueryString($data);
    	return $this->doRequest();
    }

    function buildQueryString($data) {
        $querystring = '';
        if (is_array($data)) {
            // Change data in to postable data
    		foreach ($data as $key => $val) {
    			if (is_array($val)) {
    				foreach ($val as $val2) {
    					$querystring .= urlencode($key).'='.urlencode($val2).'&';
    				}
    			} else {
    				$querystring .= urlencode($key).'='.urlencode($val).'&';
    			}
    		}
    		$querystring = substr($querystring, 0, -1); // Eliminate unnecessary &
    	} else {
    	    $querystring = $data;
    	}
    	return $querystring;
    }

    function doRequest() {
        // Performs the actual HTTP request, returning true or false depending on outcome
		if (!$fp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout)) {
		    // Set error message
            switch($errno) {
				case -3:
					$this->errormsg = 'Socket creation failed (-3)';
				case -4:
					$this->errormsg = 'DNS lookup failure (-4)';
				case -5:
					$this->errormsg = 'Connection refused or timed out (-5)';
				default:
					$this->errormsg = 'Connection failed ('.$errno.')';
			    $this->errormsg .= ' '.$errstr;
			    $this->debug($this->errormsg);
			}
			return false;
        }
        socket_set_timeout($fp, $this->timeout);
        $request = $this->buildRequest();
        $this->debug('Request', $request);
        fwrite($fp, $request);
    	// Reset all the variables that should not persist between requests
    	$this->headers = array();
    	$this->content = '';
    	$this->errormsg = '';
    	// Set a couple of flags
    	$inHeaders = true;
    	$atStart = true;
    	// Now start reading back the response
    	while (!feof($fp)) {
    	    $line = fgets($fp, 4096);
    	    if ($atStart) {
    	        // Deal with first line of returned data
    	        $atStart = false;
    	        if (!preg_match('/HTTP\/(\\d\\.\\d)\\s*(\\d+)\\s*(.*)/', $line, $m)) {
    	            $this->errormsg = "Status code line invalid: ".htmlentities($line);
    	            $this->debug($this->errormsg);
    	            return false;
    	        }
    	        $http_version = $m[1]; // not used
    	        $this->status = $m[2];
    	        $status_string = $m[3]; // not used
    	        $this->debug(trim($line));
    	        continue;
    	    }
    	    if ($inHeaders) {
    	        if (trim($line) == '') {
    	            $inHeaders = false;
    	            $this->debug('Received Headers', $this->headers);
    	            if ($this->headers_only) {
    	                break; // Skip the rest of the input
    	            }
    	            continue;
    	        }
    	        if (!preg_match('/([^:]+):\\s*(.*)/', $line, $m)) {
    	            // Skip to the next header
    	            continue;
    	        }
    	        $key = strtolower(trim($m[1]));
    	        $val = trim($m[2]);
    	        // Deal with the possibility of multiple headers of same name
    	        if (isset($this->headers[$key])) {
    	            if (is_array($this->headers[$key])) {
    	                $this->headers[$key][] = $val;
    	            } else {
    	                $this->headers[$key] = array($this->headers[$key], $val);
    	            }
    	        } else {
    	            $this->headers[$key] = $val;
    	        }
    	        continue;
    	    }
    	    // We're not in the headers, so append the line to the contents
    	    $this->content .= $line;
        }
        fclose($fp);
        // If data is compressed, uncompress it
        if (isset($this->headers['content-encoding']) && $this->headers['content-encoding'] == 'gzip') {
            $this->debug('Content is gzip encoded, unzipping it');
            $this->content = substr($this->content, 10); // See http://www.php.net/manual/en/function.gzencode.php
            $this->content = gzinflate($this->content);
        }
        // If $persist_cookies, deal with any cookies
        if ($this->persist_cookies && isset($this->headers['set-cookie']) && $this->host == $this->cookie_host) {
            $cookies = $this->headers['set-cookie'];
            if (!is_array($cookies)) {
                $cookies = array($cookies);
            }
            foreach ($cookies as $cookie) {
                if (preg_match('/([^=]+)=([^;]+);/', $cookie, $m)) {
                    $this->cookies[$m[1]] = $m[2];
                }
            }
            // Record domain of cookies for security reasons
            $this->cookie_host = $this->host;
        }
        // If $persist_referers, set the referer ready for the next request
        if ($this->persist_referers) {
            $this->debug('Persisting referer: '.$this->getRequestURL());
            $this->referer = $this->getRequestURL();
        }
        // Finally, if handle_redirects and a redirect is sent, do that
        if ($this->handle_redirects) {
            if (++$this->redirect_count >= $this->max_redirects) {
                $this->errormsg = 'Number of redirects exceeded maximum ('.$this->max_redirects.')';
                $this->debug($this->errormsg);
                $this->redirect_count = 0;
                return false;
            }
            $location = isset($this->headers['location']) ? $this->headers['location'] : '';
            $uri = isset($this->headers['uri']) ? $this->headers['uri'] : '';
            if ($location || $uri) {
                $url = parse_url($location.$uri);
                // This will FAIL if redirect is to a different site
                return $this->get($url['path']);
            }
        }
        return true;
    }

    function buildRequest() {
        $headers = array();
        $headers[] = "{$this->method} {$this->path} HTTP/1.0"; // Using 1.1 leads to all manner of problems, such as "chunked" encoding
        $headers[] = "Host: {$this->host}";
        $headers[] = "User-Agent: {$this->user_agent}";
        $headers[] = "Accept: {$this->accept}";
        if ($this->use_gzip) {
            $headers[] = "Accept-encoding: {$this->accept_encoding}";
        }
        $headers[] = "Accept-language: {$this->accept_language}";
        if ($this->referer) {
            $headers[] = "Referer: {$this->referer}";
        }
    	// Cookies
    	if ($this->cookies) {
    	    $cookie = 'Cookie: ';
    	    foreach ($this->cookies as $key => $value) {
    	        $cookie .= "$key=$value; ";
    	    }
    	    $headers[] = $cookie;
    	}
    	// Basic authentication
    	if ($this->username && $this->password) {
    	    $headers[] = 'Authorization: BASIC '.base64_encode($this->username.':'.$this->password);
    	}
    	// If this is a POST, set the content type and length
    	if ($this->postdata) {
    	    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    	    $headers[] = 'Content-Length: '.strlen($this->postdata);
    	}
    	$request = implode("\r\n", $headers)."\r\n\r\n".$this->postdata;
    	return $request;
    }

    function getStatus() {
        return $this->status;
    }

    function getContent() {
        return $this->content;
    }

    function getHeaders() {
        return $this->headers;
    }

    function getHeader($header) {
        $header = strtolower($header);
        if (isset($this->headers[$header])) {
            return $this->headers[$header];
        } else {
            return false;
        }
    }

    function getError() {
        return $this->errormsg;
    }

    function getCookies() {
        return $this->cookies;
    }

    function getRequestURL() {
        $url = 'http://'.$this->host;
        if ($this->port != 80) {
            $url .= ':'.$this->port;
        }            
        $url .= $this->path;
        return $url;
    }

    // Setter methods
    function setUserAgent($string) {
        $this->user_agent = $string;
    }

    function setAuthorization($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    function setCookies($array) {
        $this->cookies = $array;
    }

    // Option setting methods
    function useGzip($boolean) {
        $this->use_gzip = $boolean;
    }

    function setPersistCookies($boolean) {
        $this->persist_cookies = $boolean;
    }

    function setPersistReferers($boolean) {
        $this->persist_referers = $boolean;
    }

    function setHandleRedirects($boolean) {
        $this->handle_redirects = $boolean;
    }

    function setMaxRedirects($num) {
        $this->max_redirects = $num;
    }

    function setHeadersOnly($boolean) {
        $this->headers_only = $boolean;
    }

    function setDebug($boolean) {
        $this->debug = $boolean;
    }

    // "Quick" static methods
    function quickGet($url) {
        $bits = parse_url($url);
        $host = $bits['host'];
        $port = isset($bits['port']) ? $bits['port'] : 80;
        $path = isset($bits['path']) ? $bits['path'] : '/';
        if (isset($bits['query'])) {
            $path .= '?'.$bits['query'];
        }
        $client = new HttpClient($host, $port);
        if (!$client->get($path)) {
            return false;
        } else {
            return $client->getContent();
        }
    }

    function quickPost($url, $data) {
        $bits = parse_url($url);
        $host = $bits['host'];
        $port = isset($bits['port']) ? $bits['port'] : 80;
        $path = isset($bits['path']) ? $bits['path'] : '/';
        $client = new HttpClient($host, $port);
        if (!$client->post($path, $data)) {
            return false;
        } else {
            return $client->getContent();
        }
    }

    function debug($msg, $object = false) {
        if ($this->debug) {
            print '<div style="border: 1px solid red; padding: 0.5em; margin: 0.5em;"><strong>HttpClient Debug:</strong> '.$msg;
            if ($object) {
                ob_start();
        	    print_r($object);
        	    $content = htmlentities(ob_get_contents());
        	    ob_end_clean();
        	    print '<pre>'.$content.'</pre>';
        	}
        	print '</div>';
        }
    }   
}





/**************************************************************************************************
* Class: Advanced HTTP Client
***************************************************************************************************
* Version       : 1.1
* Released      : 06-20-2002
* Last Modified : 06-10-2003
* Author        : GuinuX <guinux@cosmoplazza.com>
* Ref			: http://freshmeat.net/projects/advanced_http_client/
*
***************************************************************************************************
* Description:  
***************************************************************************************************
*   A HTTP client class
*   Supports : 
*           - GET, HEAD and POST methods 
*           - Http cookies 
*           - multipart/form-data AND application/x-www-form-urlencoded
*           - Chunked Transfer-Encoding 
*           - HTTP 1.0 and 1.1 protocols 
*           - Keep-Alive Connections 
*           - Proxy
*           - Basic WWW-Authentification and Proxy-Authentification 
*
***************************************************************************************************
* TODO :
***************************************************************************************************
*           - Read trailing headers for Chunked Transfer-Encoding 
***************************************************************************************************
* usage
***************************************************************************************************
* See example scripts.
*
***************************************************************************************************
* License
***************************************************************************************************
* GNU Lesser General Public License (LGPL)
* http://www.opensource.org/licenses/lgpl-license.html
*
* For any suggestions or bug report please contact me : guinux@cosmoplazza.com
***************************************************************************************************

 *********************************************************************
 * Demonstrates the use of the get() method
 *********************************************************************
    require_once( 'http.inc.php' );
    header( 'Content-Type: text/xml' );
    
    // Grab a RDF file from phpdeveloper.org and display it
    $http_client = new http( HTTP_V11, false);
    $http_client->host = 'www.phpdeveloper.org';
    
    if ( $http_client->get( '/phpdev.rdf' ) == HTTP_STATUS_OK)
        print( $http_client->get_response_body() );
    else
        print( 'Server returned ' .  $http_client->status );

    unset( $http_client );

 *********************************************************************
 * MultiPart Post data
 *********************************************************************
    require_once( 'http.inc.php' );    
    $fields =   array(  'user' => 'GuinuX',
                        'password' => 'mypass',
                        'lang' => 'US'
                );    
    $files = array();
    $files[] =  array(  'name' => 'myfile1',
                    'content-type' => 'text/plain',
                    'filename' => 'test1.txt',
                    'data' => 'Hello from File 1 !!!'
        );      
    $files[] =  array(  'name' => 'myfile2',
                        'content-type' => 'text/plain',
                        'filename' => 'test2.txt',
                        'data' => "bla bla bla\nbla bla"
        );          
    $http_client = new http( HTTP_V11, false );
    $http_client->host = 'www.myhost.com';
    if ($http_client->multipart_post( '/upload.pl', $fields, $files ) == HTTP_STATUS_OK )
           print($http_client->get_response_body());
        else
           print('Server returned status code : ' . $http_client->status);
    unset( $http_client );


 *********************************************************************
 * Post data
 *********************************************************************

	require_once( 'http.inc.php' );
	header( 'Content-Type: text/plain' );
	$form = array(
					'value' => 1,
					'date' => '05/20/02',
					'date_fmt' => 'us',
					'result' => 1,
					'lang' => 'eng',
					'exch' => 'USD',
					'Currency' => 'EUR',
					'format' => 'HTML',
					'script' => '../convert/fxdaily',
					'dest' => 'Get Table'
				);

		$http_client = new http( HTTP_V11, true );
		$http_client->host = 'www.oanda.com';
		$status = $http_client->post( '/convert/fxdaily', $form, 'http://www.oanda.com/convert/fxdaily' );
		if ( $status == HTTP_STATUS_OK ) {
			print( $http_client->get_response_body() );
		} else {
			print( "An error occured while requesting your file !\n" );
			print_r( $http_client );
		}
		$http_client->disconnect();
		unset( $http_client );

 *********************************************************************
 * Demonstrates the use of requests via proxy
 *********************************************************************

    header('Content-Type: text/plain');
    require_once( 'http.inc.php' );

    $http_client = new http( HTTP_V11, false );
    $http_client->host = 'www.yahoo.com';
    $http_client->use_proxy( 'ns.crs.org.ni', 3128 );
    if ($http_client->get( '/' ) == HTTP_STATUS_OK)
        print_r( $http_client );
    else
        print('Server returned ' . $http_client->status );
    unset( $http_client );


*/
if ( !defined('HTTP_CRLF') ) define( 'HTTP_CRLF', chr(13) . chr(10));
define( 'HTTP_V10', '1.0');
define( 'HTTP_V11', '1.1');
define( 'HTTP_STATUS_CONTINUE',                 100 );
define( 'HTTP_STATUS_SWITCHING_PROTOCOLS',      101 );
define( 'HTTP_STATUS_OK',                       200 );
define( 'HTTP_STATUS_CREATED',                  201 );
define( 'HTTP_STATUS_ACCEPTED',                 202 );
define( 'HTTP_STATUS_NON_AUTHORITATIVE',        203 );
define( 'HTTP_STATUS_NO_CONTENT',               204 );
define( 'HTTP_STATUS_RESET_CONTENT',            205 );
define( 'HTTP_STATUS_PARTIAL_CONTENT',          206 );
define( 'HTTP_STATUS_MULTIPLE_CHOICES',         300 );
define( 'HTTP_STATUS_MOVED_PERMANENTLY',        301 );
define( 'HTTP_STATUS_FOUND',                    302 );
define( 'HTTP_STATUS_SEE_OTHER',                303 );
define( 'HTTP_STATUS_NOT_MODIFIED',             304 );
define( 'HTTP_STATUS_USE_PROXY',                305 );
define( 'HTTP_STATUS_TEMPORARY_REDIRECT',       307 );
define( 'HTTP_STATUS_BAD_REQUEST',              400 );
define( 'HTTP_STATUS_UNAUTHORIZED',             401 );
define( 'HTTP_STATUS_FORBIDDEN',                403 );
define( 'HTTP_STATUS_NOT_FOUND',                404 );
define( 'HTTP_STATUS_METHOD_NOT_ALLOWED',       405 );
define( 'HTTP_STATUS_NOT_ACCEPTABLE',           406 );
define( 'HTTP_STATUS_PROXY_AUTH_REQUIRED',      407 );
define( 'HTTP_STATUS_REQUEST_TIMEOUT',          408 );
define( 'HTTP_STATUS_CONFLICT',                 409 );
define( 'HTTP_STATUS_GONE',                     410 );
define( 'HTTP_STATUS_REQUEST_TOO_LARGE',        413 );
define( 'HTTP_STATUS_URI_TOO_LONG',             414 );
define( 'HTTP_STATUS_SERVER_ERROR',             500 );
define( 'HTTP_STATUS_NOT_IMPLEMENTED',          501 );
define( 'HTTP_STATUS_BAD_GATEWAY',              502 );
define( 'HTTP_STATUS_SERVICE_UNAVAILABLE',      503 );
define( 'HTTP_STATUS_VERSION_NOT_SUPPORTED',    505 );


/******************************************************************************************
* class http
******************************************************************************************/
class HttpAdvanced {
	var $_socket;
	var $host;
	var $port;
	var $http_version;
	var $user_agent;
	var $errstr;
	var $connected;
	var $uri;
	var $_proxy_host;
	var $_proxy_port;
	var $_proxy_login;
	var $_proxy_pwd;
	var $_use_proxy;
	var $_auth_login;
	var $_auth_pwd; 
	var $_response; 
	var $_request;
	var $_keep_alive;       

	function HttpAdvanced( $http_version = HTTP_V10, $keep_alive = false, $auth = false ) {
		$this->http_version = $http_version;
		$this->connected    = false;
		$this->user_agent   = 'CosmoHttp/1.1 (compatible; MSIE 5.5; Linux)';
		$this->host         = '';
		$this->port         = 80;
		$this->errstr       = '';

		$this->_keep_alive  = $keep_alive;
		$this->_proxy_host  = '';
		$this->_proxy_port  = -1;
		$this->_proxy_login = '';
		$this->_proxy_pwd   = '';
		$this->_auth_login  = '';
		$this->_auth_pwd    = '';
		$this->_use_proxy   = false;
		$this->_response    = new http_response_message();
		$this->_request     = new http_request_message();
		
	// Basic Authentification added by Mate Jovic, 2002-18-06, jovic@matoma.de
		if( is_array($auth) && count($auth) == 2 ){
			$this->_auth_login  = $auth[0];
			$this->_auth_pwd    = $auth[1];
		}
	} // End of Constuctor

	function use_proxy( $host, $port, $proxy_login = null, $proxy_pwd = null ) {
	// Proxy auth not yet supported
		$this->http_version = HTTP_V10;
		$this->_keep_alive  = false;
		$this->_proxy_host  = $host;
		$this->_proxy_port  = $port;
		$this->_proxy_login = $proxy_login;
		$this->_proxy_pwd   = $proxy_pwd;
		$this->_use_proxy   = true;
	}

	function set_request_header( $name, $value ) {
		$this->_request->set_header( $name, $value );
	}

	function get_response_body() {
		return $this->_response->body;
	}
	
	function get_response() {
		return $this->_response;
	}
	
	function head( $uri ) {
		$this->uri = $uri;

		if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
			if ( !$this->_connect() ) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return -1;
			}
		}
		$http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );
		
		if ($this->_use_proxy) {
			$this->_request->set_header( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
			if ( $this->_proxy_login != '' ) $this->_request->set_header( 'Proxy-Authorization', "Basic " . base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header( 'Host', $this->host );
			$this->_request->set_header( 'Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
		}

		if ( $this->_auth_login != '' ) $this->_request->set_header( 'Authorization', "Basic " . base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );            
		$this->_request->set_header( 'User-Agent', $this->user_agent );
		$this->_request->set_header( 'Accept', '*/*' );
		$this->_request->set_header( 'Cookie', $http_cookie );
		
		$cmd =  "HEAD $uri HTTP/" . $this->http_version . HTTP_CRLF . 
				$this->_request->serialize_headers() .
				HTTP_CRLF;
		fwrite( $this->_socket, $cmd );
		
		$this->_request->add_debug_info( $cmd );
		$this->_get_response( false );

		if ($this->_socket && !$this->_keep_alive) $this->disconnect();
		if ( $this->_response->get_header( 'Connection' ) != null ) {
			if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
				$this->_keep_alive = false;
				$this->disconnect();
			}
		}
		
		if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
			$location = $this->_parse_location( $this->_response->get_header( 'Location' ) );
			$this->disconnect();
			$this->use_proxy( $location['host'], $location['port'] );
			$this->head( $this->uri );
		}
		
		return $this->_response->get_header( 'Status' );
	} // End of function head()

	
	function get( $uri, $follow_redirects = true, $referer = '' ) {
		$this->uri = $uri;
		
		if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
			if ( !$this->_connect() ) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return -1;
			}
		}
		
		if ($this->_use_proxy) {
			$this->_request->set_header( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
			if ( $this->_proxy_login != '' ) $this->_request->set_header( 'Proxy-Authorization', "Basic " . base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header( 'Host', $this->host );
			$this->_request->set_header( 'Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
			$this->_request->set_header( 'Pragma', 'no-cache' );
			$this->_request->set_header( 'Cache-Control', 'no-cache' );
		}
		
		if ( $this->_auth_login != '' ) $this->_request->set_header( 'Authorization', "Basic " . base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );
		$http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );
		$this->_request->set_header( 'User-Agent', $this->user_agent );
		$this->_request->set_header( 'Accept', '*/*' );
		$this->_request->set_header( 'Referer', $referer );
		$this->_request->set_header( 'Cookie', $http_cookie );
		
		$cmd =  "GET $uri HTTP/" . $this->http_version . HTTP_CRLF . 
				$this->_request->serialize_headers() .
				HTTP_CRLF;
		fwrite( $this->_socket, $cmd );

		$this->_request->add_debug_info( $cmd );
		$this->_get_response();

		if ($this->_socket && !$this->_keep_alive) $this->disconnect();
		if (  $this->_response->get_header( 'Connection' ) != null ) {
			if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
				$this->_keep_alive = false;
				$this->disconnect();
			}
		}
		if ( $follow_redirects && ($this->_response->get_status() == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status() == HTTP_STATUS_FOUND || $this->_response->get_status() == HTTP_STATUS_SEE_OTHER ) ) {
			if ( $this->_response->get_header( 'Location' ) != null  ) {
				$this->_redirect( $this->_response->get_header( 'Location' ) );
			}
		}
		
		if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
			$location = $this->_parse_location( $this->_response->get_header( 'Location' ) );
			$this->disconnect();
			$this->use_proxy( $location['host'], $location['port'] );
			$this->get( $this->uri, $referer );
		}

		return $this->_response->get_status();
	} // End of function get()



	function multipart_post( $uri, &$form_fields, $form_files = null, $follow_redirects = true, $referer = '' ) {
		$this->uri = $uri;
		
		if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
			if ( !$this->_connect() ) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return -1;
			}
		}
		$boundary = uniqid('------------------');
		$http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );
		$body = $this->_merge_multipart_form_data( $boundary, $form_fields, $form_files );
		$this->_request->body =  $body . HTTP_CRLF;
		$content_length = strlen( $body ); 


		if ($this->_use_proxy) {
			$this->_request->set_header( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
			if ( $this->_proxy_login != '' ) $this->_request->set_header( 'Proxy-Authorization', "Basic " . base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header( 'Host', $this->host );
			$this->_request->set_header( 'Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
			$this->_request->set_header( 'Pragma', 'no-cache' );
			$this->_request->set_header( 'Cache-Control', 'no-cache' );
		}

		if ( $this->_auth_login != '' ) $this->_request->set_header( 'Authorization', "Basic " . base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );
		$this->_request->set_header( 'Accept', '*/*' );
		$this->_request->set_header( 'Content-Type', 'multipart/form-data; boundary=' . $boundary );
		$this->_request->set_header( 'User-Agent', $this->user_agent );
		$this->_request->set_header( 'Content-Length', $content_length );
		$this->_request->set_header( 'Cookie', $http_cookie );
		$this->_request->set_header( 'Referer', $referer );
						
		$req_header = "POST $uri HTTP/" . $this->http_version . HTTP_CRLF . 
					$this->_request->serialize_headers() .
					HTTP_CRLF;

		fwrite( $this->_socket, $req_header );
		usleep(10);
		fwrite( $this->_socket, $this->_request->body );
		
		$this->_request->add_debug_info( $req_header );
		$this->_get_response();
		
		if ($this->_socket && !$this->_keep_alive) $this->disconnect();
		if ( $this->_response->get_header( 'Connection' ) != null ) {
			if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
				$this->_keep_alive = false;
				$this->disconnect();
			}
		}
		
		if ( $follow_redirects && ($this->_response->get_status() == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status() == HTTP_STATUS_FOUND || $this->_response->get_status() == HTTP_STATUS_SEE_OTHER ) ) {
			if ( $this->_response->get_header( 'Location') != null ) {
				$this->_redirect( $this->_response->get_header( 'Location') );
			}
		}
		
		if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
			$location = $this->_parse_location( $this->_response->get_header( 'Location') );
			$this->disconnect();
			$this->use_proxy( $location['host'], $location['port'] );
			$this->multipart_post( $this->uri, $form_fields, $form_files, $referer );
		}

		return $this->_response->get_status();
	} // End of function multipart_post()



	function post( $uri, &$form_data, $follow_redirects = true, $referer = '' ) {
		$this->uri = $uri;

		if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
			if ( !$this->_connect() ) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return -1;
			}
		}
		$http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );
		$body = substr( $this->_merge_form_data( $form_data ), 1 );
		$this->_request->body =  $body . HTTP_CRLF . HTTP_CRLF;
		$content_length = strlen( $body ); 

		if ($this->_use_proxy) {
			$this->_request->set_header( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
			if ( $this->_proxy_login != '' ) $this->_request->set_header( 'Proxy-Authorization', "Basic " . base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header( 'Host', $this->host );
			$this->_request->set_header( 'Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
			$this->_request->set_header( 'Pragma', 'no-cache' );
			$this->_request->set_header( 'Cache-Control', 'no-cache' );
		}
		
		if ( $this->_auth_login != '' ) $this->_request->set_header( 'Authorization', "Basic " . base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );            
		$this->_request->set_header( 'Accept', '*/*' );
		$this->_request->set_header( 'Content-Type', 'application/x-www-form-urlencoded' );
		$this->_request->set_header( 'User-Agent', $this->user_agent );
		$this->_request->set_header( 'Content-Length', $content_length );
		$this->_request->set_header( 'Cookie', $http_cookie );
		$this->_request->set_header( 'Referer', $referer );
						
		$req_header = "POST $uri HTTP/" . $this->http_version . HTTP_CRLF . 
					$this->_request->serialize_headers() .
					HTTP_CRLF;

		fwrite( $this->_socket, $req_header );
		usleep( 10 );
		fwrite( $this->_socket, $this->_request->body );
		
		$this->_request->add_debug_info( $req_header );
		$this->_get_response();

		if ($this->_socket && !$this->_keep_alive) $this->disconnect();
		if ( $this->_response->get_header( 'Connection' ) != null ) {
			if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
				$this->_keep_alive = false;
				$this->disconnect();
			}
		}
		
		if ( $follow_redirects && ($this->_response->get_status() == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status() == HTTP_STATUS_FOUND || $this->_response->get_status() == HTTP_STATUS_SEE_OTHER ) ) {
			if ( $this->_response->get_header( 'Location' ) != null ) {
				$this->_redirect( $this->_response->get_header( 'Location' ) );
			}
		}
		
		if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
			$location = $this->_parse_location( $this->_response->get_header( 'Location' ) );
			$this->disconnect();
			$this->use_proxy( $location['host'], $location['port'] );
			$this->post( $this->uri, $form_data, $referer );
		}

		return $this->_response->get_status();
	} // End of function post()
	


	function post_xml( $uri, $xml_data, $follow_redirects = true, $referer = '' ) {
		$this->uri = $uri;

		if ( ($this->_keep_alive && !$this->connected) || !$this->_keep_alive ) {
			if ( !$this->_connect() ) {
				$this->errstr = 'Could not connect to ' . $this->host;
				return -1;
			}
		}
		$http_cookie = $this->_response->cookies->get( $this->host, $this->_current_directory( $uri ) );
		$body = $xml_data;
		$this->_request->body =  $body . HTTP_CRLF . HTTP_CRLF;
		$content_length = strlen( $body ); 

		if ($this->_use_proxy) {
			$this->_request->set_header( 'Host', $this->host . ':' . $this->port );
			$this->_request->set_header( 'Proxy-Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
			if ( $this->_proxy_login != '' ) $this->_request->set_header( 'Proxy-Authorization', "Basic " . base64_encode( $this->_proxy_login . ":" . $this->_proxy_pwd ) );
			$uri = 'http://' . $this->host . ':' . $this->port . $uri;
		} else {
			$this->_request->set_header( 'Host', $this->host );
			$this->_request->set_header( 'Connection', ($this->_keep_alive?'Keep-Alive':'Close') );
			$this->_request->set_header( 'Pragma', 'no-cache' );
			$this->_request->set_header( 'Cache-Control', 'no-cache' );
		}
		
		if ( $this->_auth_login != '' ) $this->_request->set_header( 'Authorization', "Basic " . base64_encode( $this->_auth_login . ":" . $this->_auth_pwd ) );            
		$this->_request->set_header( 'Accept', '*/*' );
		$this->_request->set_header( 'Content-Type', 'text/xml; charset=utf-8' );
		$this->_request->set_header( 'User-Agent', $this->user_agent );
		$this->_request->set_header( 'Content-Length', $content_length );
		$this->_request->set_header( 'Cookie', $http_cookie );
		$this->_request->set_header( 'Referer', $referer );
						
		$req_header = "POST $uri HTTP/" . $this->http_version . HTTP_CRLF . 
					$this->_request->serialize_headers() .
					HTTP_CRLF;

		fwrite( $this->_socket, $req_header );
		usleep( 10 );
		fwrite( $this->_socket, $this->_request->body );
		
		$this->_request->add_debug_info( $req_header );
		$this->_get_response();

		if ($this->_socket && !$this->_keep_alive) $this->disconnect();
		if ( $this->_response->get_header( 'Connection' ) != null ) {
			if ( $this->_keep_alive && strtolower( $this->_response->get_header( 'Connection' ) ) == 'close' ) {
				$this->_keep_alive = false;
				$this->disconnect();
			}
		}
		
		if ( $follow_redirects && ($this->_response->get_status() == HTTP_STATUS_MOVED_PERMANENTLY || $this->_response->get_status() == HTTP_STATUS_FOUND || $this->_response->get_status() == HTTP_STATUS_SEE_OTHER ) ) {
			if ( $this->_response->get_header( 'Location' ) != null ) {
				$this->_redirect( $this->_response->get_header( 'Location' ) );
			}
		}
		
		if ( $this->_response->get_status() == HTTP_STATUS_USE_PROXY ) {
			$location = $this->_parse_location( $this->_response->get_header( 'Location' ) );
			$this->disconnect();
			$this->use_proxy( $location['host'], $location['port'] );
			$this->post( $this->uri, $form_data, $referer );
		}

		return $this->_response->get_status();
	} // End of function post_xml()
	
	
	function disconnect() {
		if ($this->_socket && $this->connected) {
			 fclose($this->_socket);
			$this->connected = false;
		 }
	} // End of function disconnect()


	/********************************************************************************
	 * Private functions 
	 ********************************************************************************/
	 
	function _connect( ) {
		if ( $this->host == '' ) user_error( 'Class HTTP->_connect() : host property not set !' , E_ERROR );
		if (!$this->_use_proxy)
			$this->_socket = fsockopen( $this->host, $this->port, $errno, $errstr, 10 );
		else
			$this->_socket = fsockopen( $this->_proxy_host, $this->_proxy_port, $errno, $errstr, 10 );
		$this->errstr  = $errstr;
		$this->connected = ($this->_socket == true);
		return $this->connected;
	} // End of function connect()


	function _merge_multipart_form_data( $boundary, &$form_fields, &$form_files ) {
		$boundary = '--' . $boundary;
		$multipart_body = '';
		foreach ( $form_fields as $name => $data) {
			$multipart_body .= $boundary . HTTP_CRLF;
			$multipart_body .= 'Content-Disposition: form-data; name="' . $name . '"' . HTTP_CRLF;
			$multipart_body .=  HTTP_CRLF;
			$multipart_body .= $data . HTTP_CRLF;
		}
		if ( isset($form_files) ) {
			foreach ( $form_files as $data) {
				$multipart_body .= $boundary . HTTP_CRLF;
				$multipart_body .= 'Content-Disposition: form-data; name="' . $data['name'] . '"; filename="' . $data['filename'] . '"' . HTTP_CRLF;
				if ($data['content-type']!='') 
					$multipart_body .= 'Content-Type: ' . $data['content-type'] . HTTP_CRLF;
				else
					$multipart_body .= 'Content-Type: application/octet-stream' . HTTP_CRLF;
				$multipart_body .=  HTTP_CRLF;
				$multipart_body .= $data['data'] . HTTP_CRLF;
			}           
		}
		$multipart_body .= $boundary . '--' . HTTP_CRLF;
		return $multipart_body;
	} // End of function _merge_multipart_form_data()
	

	function _merge_form_data( &$param_array,  $param_name = '' ) {
		$params = '';
		$format = ($param_name !=''?'&'.$param_name.'[%s]=%s':'&%s=%s');
		foreach ( $param_array as $key=>$value ) {
			if ( !is_array( $value ) )
				$params .= sprintf( $format, $key, urlencode( $value ) );
			else
				$params .= $this->_merge_form_data( $param_array[$key],  $key );
		}
		return $params;
	} // End of function _merge_form_data()

	function _current_directory( $uri ) {
		$tmp = split( '/', $uri );
		array_pop($tmp);
		$current_dir = implode( '/', $tmp ) . '/';
		return ($current_dir!=''?$current_dir:'/');
	} // End of function _current_directory()       
	
	
	function _get_response( $get_body = true ) {
		$this->_response->reset();
		$this->_request->reset();
		$header = '';
		$body = '';
		$continue   = true;
		
		while ($continue) {
			$header = '';

			// Read the Response Headers
			while ( (($line = fgets( $this->_socket, 4096 )) != HTTP_CRLF || $header == '') && !feof( $this->_socket ) ) { 
				if ($line != HTTP_CRLF) $header .= $line; 
			}
			$this->_response->deserialize_headers( $header );
			$this->_response->parse_cookies( $this->host );
			
			$this->_response->add_debug_info( $header );
			$continue = ($this->_response->get_status() == HTTP_STATUS_CONTINUE);
			if ($continue) fwrite( $this->_socket, HTTP_CRLF );
		}

		if ( !$get_body ) return;

		// Read the Response Body
		if ( strtolower( $this->_response->get_header( 'Transfer-Encoding' ) ) != 'chunked' && !$this->_keep_alive ) {
			while ( !feof( $this->_socket ) ) { 
				$body .= fread( $this->_socket, 4096 ); 
			}
		} else {
			if ( $this->_response->get_header( 'Content-Length' ) != null ) {
				$content_length = (integer)$this->_response->get_header( 'Content-Length' );
				$body = fread( $this->_socket, $content_length ); 
			} else {
				if ( $this->_response->get_header( 'Transfer-Encoding' ) != null ) {
					if ( strtolower( $this->_response->get_header( 'Transfer-Encoding' ) ) == 'chunked' ) {
						$chunk_size = (integer)hexdec(fgets( $this->_socket, 4096 ) ); 
						while($chunk_size > 0) {
							$body .= fread( $this->_socket, $chunk_size ); 
							fread( $this->_socket, strlen(HTTP_CRLF) ); 
							$chunk_size = (integer)hexdec(fgets( $this->_socket, 4096 ) ); 
						}
						// TODO : Read trailing http headers
					}
				} 
			}
		}
		$this->_response->body = $body;
	} // End of function _get_response()


	function _parse_location( $redirect_uri ) {
		$parsed_url     = parse_url( $redirect_uri );
		$scheme         = (isset($parsed_url['scheme'])?$parsed_url['scheme']:'');
		$port           = (isset($parsed_url['port'])?$parsed_url['port']:$this->port);
		$host           = (isset($parsed_url['host'])?$parsed_url['host']:$this->host);
		$request_file   = (isset($parsed_url['path'])?$parsed_url['path']:'');
		$query_string   = (isset($parsed_url['query'])?$parsed_url['query']:'');
		if ( substr( $request_file, 0, 1 ) != '/' )
			$request_file = $this->_current_directory( $this->uri ) . $request_file;
		
		return array(   'scheme' => $scheme,
						'port' => $port,
						'host' => $host,
						'request_file' => $request_file,
						'query_string' => $query_string
		);

	} // End of function _parse_location()
	
	
	function _redirect( $uri ) {
		$location = $this->_parse_location( $uri );
		if ( $location['host'] != $this->host || $location['port'] != $this->port ) {
			$this->host = $location['host'];
			$this->port = $location['port'];
			if ( !$this->_use_proxy) $this->disconnect();
		}
		usleep( 100 );
		$this->get( $location['request_file'] . '?' . $location['query_string'] );
	} // End of function _redirect()

} // End of class http



/******************************************************************************************
* class http_header
******************************************************************************************/
class http_header {
	var $_headers;
	var $_debug;

	function http_header() {
		$this->_headers = Array();
		$this->_debug   = '';
	} // End Of function http_header()
	
	function get_header( $header_name ) {
		$header_name = $this->_format_header_name( $header_name );
		if (isset($this->_headers[$header_name]))
			return $this->_headers[$header_name];
		else
			return null;
	} // End of function get()
	
	function set_header( $header_name, $value ) {
		if ($value != '') {
			$header_name = $this->_format_header_name( $header_name );
			$this->_headers[$header_name] = $value;
		}
	} // End of function set()
	
	function reset() {
		if ( count( $this->_headers ) > 0 ) $this->_headers = array();
		$this->_debug   .= "\n--------------- RESETED ---------------\n";
	} // End of function clear()

	function serialize_headers() {
		$str = '';
		foreach ( $this->_headers as $name=>$value) {
			$str .= "$name: $value" . HTTP_CRLF;
		}
		return $str;
	} // End of function serialize_headers()
	
	function _format_header_name( $header_name ) {
		$formatted = str_replace( '-', ' ', strtolower( $header_name ) );
		$formatted = ucwords( $formatted );
		$formatted = str_replace( ' ', '-', $formatted );
		return $formatted;
	}
	
	function add_debug_info( $data ) {
		$this->_debug .= $data;
	}

	function get_debug_info() {
		return $this->_debug;
	}

} // End Of Class http_header

/******************************************************************************************
* class http_response_header
******************************************************************************************/
class http_response_header extends http_header {
	var $cookies_headers;
	
	function http_response_header() {
		$this->cookies_headers = array();
		http_header::http_header();
	} // End of function http_response_header()
	
	function deserialize_headers( $flat_headers ) {
		$flat_headers = preg_replace( "/^" . HTTP_CRLF . "/", '', $flat_headers );
		$tmp_headers = split( HTTP_CRLF, $flat_headers );
		if (preg_match("'HTTP/(\d\.\d)\s+(\d+).*'i", $tmp_headers[0], $matches )) {
			$this->set_header( 'Protocol-Version', $matches[1] );
			$this->set_header( 'Status', $matches[2] );
		} 
		array_shift( $tmp_headers );
		foreach( $tmp_headers as $index=>$value ) {
			$pos = strpos( $value, ':' );
			if ( $pos ) {
				$key = substr( $value, 0, $pos );
				$value = trim( substr( $value, $pos +1) );
				if ( strtoupper($key) == 'SET-COOKIE' )
					$this->cookies_headers[] = $value;
				else
					$this->set_header( $key, $value );
			}
		}
	} // End of function deserialize_headers()
	
	function reset() {
		if ( count( $this->cookies_headers ) > 0 ) $this->cookies_headers = array();
		http_header::reset();
	}

} // End of class http_response_header


/******************************************************************************************
* class http_request_message
******************************************************************************************/
class http_request_message extends http_header {
	var $body;
	
	function http_request_message() {
		$this->body = '';
		http_header::http_header();
	} // End of function http_message()
	
	function reset() {
		$this->body = '';
		http_header::reset();
	}
}

/******************************************************************************************
* class http_response_message
******************************************************************************************/
class http_response_message extends http_response_header {
	var $body;
	var $cookies;
	
	function http_response_message() {
		$this->cookies = new http_cookie();
		$this->body = '';
		http_response_header::http_response_header();
	} // End of function http_response_message()
	
	function get_status() {
		if ( $this->get_header( 'Status' ) != null )
			return (integer)$this->get_header( 'Status' );
		else
			return -1;
	}
	
	function get_protocol_version() {
		if ( $this->get_header( 'Protocol-Version' ) != null )
			return $this->get_header( 'Protocol-Version' );
		else
			return HTTP_V10;
	}
	
	function get_content_type() {
		$this->get_header( 'Content-Type' );
	}
	
	function get_body() {
		return $this->body;
	}
	
	function reset() {
		$this->body = '';
		http_response_header::reset();
	}

	function parse_cookies( $host ) {
		for ( $i = 0; $i < count( $this->cookies_headers ); $i++ )
			$this->cookies->parse( $this->cookies_headers[$i], $host );
	}
}

/******************************************************************************************
* class http_cookie
******************************************************************************************/
class http_cookie {
	var $cookies;

	function http_cookie() {
		$this->cookies  = array();
	} // End of function http_cookies()
	
	function _now() {
		return strtotime( gmdate( "l, d-F-Y H:i:s", time() ) );
	} // End of function _now()
	
	function _timestamp( $date ) {
		if ( $date == '' ) return $this->_now()+3600;
		$time = strtotime( $date );
		return ($time>0?$time:$this->_now()+3600);
	} // End of function _timestamp()

	function get( $current_domain, $current_path ) {
		$cookie_str = '';
		$now = $this->_now();
		$new_cookies = array();

		foreach( $this->cookies as $cookie_name => $cookie_data ) {
			if ($cookie_data['expires'] > $now) {
				$new_cookies[$cookie_name] = $cookie_data;
				$domain = preg_quote( $cookie_data['domain'] );
				$path = preg_quote( $cookie_data['path']  );
				if ( preg_match( "'.*$domain$'i", $current_domain ) && preg_match( "'^$path.*'i", $current_path ) )
					$cookie_str .= $cookie_name . '=' . $cookie_data['value'] . '; ';
			}
		}
		$this->cookies = $new_cookies;
		return $cookie_str;
	} // End of function get()
	
	function set( $name, $value, $domain, $path, $expires ) {
		$this->cookies[$name] = array(  'value' => $value,
										'domain' => $domain,
										'path' => $path,
										'expires' => $this->_timestamp( $expires )
										);
	} // End of function set()
	
	function parse( $cookie_str, $host ) {
		$cookie_str = str_replace( '; ', ';', $cookie_str ) . ';';
		$data = split( ';', $cookie_str );
		$value_str = $data[0];

		$cookie_param = 'domain=';
		$start = strpos( $cookie_str, $cookie_param );
		if ( $start > 0 ) {
			$domain = substr( $cookie_str, $start + strlen( $cookie_param ) );
			$domain = substr( $domain, 0, strpos( $domain, ';' ) );
		} else
			$domain = $host;

		$cookie_param = 'expires=';
		$start = strpos( $cookie_str, $cookie_param );
		if ( $start > 0 ) {
			$expires = substr( $cookie_str, $start + strlen( $cookie_param ) );
			$expires = substr( $expires, 0, strpos( $expires, ';' ) );
		} else
			$expires = '';
		
		$cookie_param = 'path=';
		$start = strpos( $cookie_str, $cookie_param );
		if ( $start > 0 ) {
			$path = substr( $cookie_str, $start + strlen( $cookie_param ) );
			$path = substr( $path, 0, strpos( $path, ';' ) );
		} else
			$path = '/';
						
		$sep_pos = strpos( $value_str, '=');
		
		if ($sep_pos){
			$name = substr( $value_str, 0, $sep_pos );
			$value = substr( $value_str, $sep_pos+1 );
			$this->set( $name, $value, $domain, $path, $expires );
		}
	} // End of function parse()
	
} // End of class http_cookie   

