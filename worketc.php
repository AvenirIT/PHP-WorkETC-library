<?
/**
 * PHP WorkETC Client Library
 *
 * This file contains a PHP library for WorkETC (worketc.com) API.
 * @author Matt Zandstra <zandstra.matt@gmail.com>
 * @version 0.1
 * @package php-worketc
 */

class WorkETC
{
    // Debug levels for error method.
    const DEBUG = 0;
    const ERROR = 1;
    const FATAL = 2;
    
    // Methods of sending HTTP header
    const HEADER_STREAM = 0;
    const HEADER_INI = 1;
    
    // Options for this library.
    private $options = array(
        'alias' => false,
        'debug' => self::ERROR,
        'session_prefix' => 'WorkETC_',
        'url' => 'https://%alias%.worketc.com/xml?wsdl',
        'header_method' => self::HEADER_STREAM,
    );
    
    // Client is the native PHP SoapClient.
    private $client = false;
    
    // Soap options array.
    private $soap_options = array(
        'soap_version' => SOAP_1_2,
        'encoding' => 'UTF-8',
        'exceptions' => TRUE,
        'trace' => TRUE
    );
    
    // Vars from API are CamelCase.
    private $VeetroSession = false;
    
    public function __construct($options=array())
    {
        // Import the supplied options.
        $this->options = array_merge($this->options, $options);
        
        // Start the session if it isn't yet.
        if(session_id() == "")
        {
            session_start();
            $this->error("Started Session.");
        }
        
        // Get the VeetroSession from $_SESSION.
        if($this->VeetroSession == false && $this->session('VeetroSession'))
        {
            $this->VeetroSession = $this->session('VeetroSession');
            $this->error("Found session key: {$this->VeetroSession}.");
        }
        
        // Connect with veetro session key if available
        if($this->VeetroSession != false)
            $this->connect();
    }
    
    public function login($email, $pass, $options=array())
    {
        // Import the supplied options.
        $this->options = array_merge($this->options, $options);
        
        // Debug
        $this->error("Logging in as {$email} to {$this->options['alias']}.");
        
        // Authenticating and save session key
        $authClient = new SoapClient($this->getUrl(), $this->soap_options);
        $response = $authClient->AuthenticateWebSafe(array('email'=>$email, 'pass'=>$pass));
        
        // Bad login case
        if($response->AuthenticateWebSafeResult->Code == "Not_Found")
            return false;
        
        // Good login case
        else if($response->AuthenticateWebSafeResult->Code == "Success")
        {
            // Save the session key in class and session
            $this->VeetroSession = $response->AuthenticateWebSafeResult->SessionKey;
            $this->User = $response;
            $this->session('VeetroSession', $this->VeetroSession);
            $this->session('UserID', (string)$response->AuthenticateWebSafeResult->User->EntityID);
            $this->session('User', $response->AuthenticateWebSafeResult->User);
            $this->error("Saving new session key {$this->VeetroSession}.");
            
            // Connect to api with VeetroHeader.
            $this->connect();
            
            return true;
        }
        
        // Unknown login case.
        else
        {
            $this->error("Unknown auth response code: {$response->AuthenticateWebSafeResult->Code}", self::ERROR);
            die(print_r($response, true));
        }
    }
    
    // Connects to the service providing a header.
    private function connect()
    {
        $this->error("Connecting with VeetroSession: {$this->VeetroSession}.");
        
        if($this->options['header_method'] === self::HEADER_STREAM)
        {
            // Add the VeetroSession HTTP Header via stream context (better).
            // http://stackoverflow.com/questions/3541690/modifying-php-soap-code-to-add-http-header-in-all-requests
            $this->soap_options['stream_context'] = stream_context_create(array(
                'http' => array(
                    'header' => "VeetroSession: {$this->VeetroSession}"
                )
            ));
        }   
        else if($this->options['header_method'] === self::HEADER_INI)
        {
            // Add the VeetroSession HTTP Header via ini_set for windows (specifically tested on xampp).
            // http://stackoverflow.com/questions/6179138/soapclient-set-custom-http-header
            ini_set('user_agent', 'PHP-SOAP/'.PHP_VERSION."\r\nVeetroSession: {$this->VeetroSession}");
        }
        else
            $this->error("Unknown header method.", self::FATAL);
        
        // Try to connect.
        try {
            $this->client = new SoapClient($this->getUrl(), $this->soap_options);
        }
        catch (SoapFault $fault) {
            die($fault->getMessage());
        }
    }
    
    // Directly calls worketc methods.
    public function __call($name, $arguments)
    {
        if($this->client == false)
        {
            $this->error("Please authenticate first.", self::ERROR);
            return false;
        }
        
        try {
            if(count($arguments)==1)
                return $this->client->$name($arguments[0])->{"{$name}Result"};
            else if(count($arguments)==0)
                return $this->client->$name()->{"{$name}Result"};
            else
                return false;
        }
        catch (SoapFault $fault)
        {
            $this->error($fault->getMessage(), self::ERROR);
            $this->error(print_r($fault,1));
            die();
        }
    }
    
    // Some debug and error help.
    private function error($message, $level = self::DEBUG)
    {
        if($level >= $this->options['debug'])
            echo "{$message}\n";
        
        if($level >= self::FATAL)
            die();
    }
    
    // Additional get methods.
    public function getClient() { return $this->client; }
    public function getUrl() { return str_replace("%alias%", $this->options['alias'], $this->options['url']); }
    
    // Either gets or sets a session var with the prefix.
    public function session($var, $value=false) {
        if($value!==false)
            $_SESSION["{$this->options['session_prefix']}{$var}"] = $value;
        else if(isset($_SESSION["{$this->options['session_prefix']}{$var}"]))
            return $_SESSION["{$this->options['session_prefix']}{$var}"];
        else
            return false;
    }
	
	// Helpful function to convert timezone.
	static function date($target="CST", $time=false, $format="Y-m-d\TH:i:s", $from="UTC") {
		$time = new DateTime($time, new DateTimeZone($from));
		$time->setTimezone(new DateTimeZone($target));
		return $time->format($format);
	}
}
