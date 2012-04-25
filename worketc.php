<?

class WorkETC
{
    const DEBUG = 0;
    const ERROR = 1;
    
    private $client = false;
    private $debug = 0;
    private $soap_options = array(
        'soap_version' => SOAP_1_2,
        'encoding' => 'UTF-8',
        'exceptions' => TRUE,
        'trace' => TRUE
    );
    private $alias = false;
    private $session_prefix = "WorkETC";
    
    // Vars from API are CamelCase
    private $User = false;
    private $VeetroSession = false;
    
    public function __construct($alias, $debug = 1)
    {
        $this->debug = $debug;
        
        // Start the session if it isn't yet.
        if(session_id() == "")
        {
            session_start();
            $this->error("Started Session.");
        }
        
        // Get the VeetroSession from the end-users $_SESSION.
        if($this->VeetroSession == false && isset($_SESSION["{$this->session_prefix}VeetroSession"]))
        {
            $this->VeetroSession = $_SESSION["{$this->session_prefix}VeetroSession"];
            $this->error("Found session key in \$_SESSION: {$this->VeetroSession}.");
        }
        
        // Save the WorkETC Site
        $this->alias = $alias;
        
        // Connect with veetro session key if available
        if($this->VeetroSession != false)
            $this->connect();
    }
    
    public function login($email, $pass, $alias = false)
    {
        // Option to switch WorkETC site.
        if($alias) $this->alias = $alias;
        $this->error("Logging in as {$email} to {$this->alias}.");
        
        // Authenticating and save session key
        $authClient = new SoapClient("https://{$this->alias}.worketc.com/xml?wsdl", $this->soap_options);
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
            $_SESSION["{$this->session_prefix}VeetroSession"] = $this->VeetroSession;
            $_SESSION["{$this->session_prefix}UserID"] = (string)$response->AuthenticateWebSafeResult->User->EntityID;
            //die(var_dump($_SESSION["{$this->session_prefix}UserID"]));
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
    
    private function connect()
    {
        $this->error("Connecting with VeetroSession: {$this->VeetroSession}.");
        // Add the VeetroSession HTTP Header
        // http://stackoverflow.com/questions/3541690/modifying-php-soap-code-to-add-http-header-in-all-requests
        $this->soap_options['stream_context'] = stream_context_create(array(
            'http' => array(
                'header' => "VeetroSession: {$this->VeetroSession}"
            )
        ));
        
        // Try to connect.
        try {
            $this->client = new SoapClient("https://{$this->alias}.worketc.com/xml?wsdl", $this->soap_options);
        }
        catch (SoapFault $fault) {
            die($fault->getMessage());
        }
    }
    
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
    
    private function error($message, $level = self::DEBUG)
    {
        if($level >= $this->debug)
            echo "{$message}\n";
    }
    
    public function client() { return $this->client; }
    public function session($var) { return $_SESSION["{$this->session_prefix}{$var}"]; }
}