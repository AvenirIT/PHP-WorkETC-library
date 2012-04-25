PHP-WorkETC
===========

A PHP Library for work etc.

Installing & Using
------------------

1.  Download the library and include it.
    
    `<? require 'worketc.php'; ?>`
    
2.  Create an instance. You'll probably want to provide your worketc site alias.

    `<? $worketc = new WorkETC(array('alias'=>'yourcompany')); ?>`
    
3.  Call the login method. It will return a boolean.
    
    `<? $worketc->login($user, $pass);`
    
4.  Call one a method from WorkETC. They provide documentation [here](http://admin.worketc.com/xml). Look closely at their docs. Some methods are spelled wrong, and must be called that way:
    
    `<? print_r($worketc->GetActvities()); ?>`
    
    If you need to provide some input, do it like this:
    
        <? print_r($worketc->GetSupportCasesByOwner(array(
            'EntityID' => 1,        // 1 is usually the first user
            'status' => 'Open',     // check the docs for other statuses
            'top' => 0              // Not documented well. It means how many to return. 0 is all.
        ))); ?>
    
5.  All requests to WorkETC are either a login with user/pass or else have an HTTP header called VeetroSession. If you need access to this key you can read it like this:
    
    `<?= $worketc->session('VeetroSession'); ?>`
    
    And write it like this:
    
    `<? $worketc->session('VeetroSession', $newVeetroSession); ?>`
    
6.  You can check your login status by requesting the SoapClient from PHP-WorkETC:
    
    `<? if($worketc->getClient()) { ... } ?>`

Options
-------

Any of these options can be provied to the constructor or the login function.

*   `alias` - Which worketc site to connect to. `alias.worketc.com`.
*   `debug` - Set this to either `WorkETC::DEBUG` or `WorkETC::ERROR`. Defaults to `WorkETC::ERROR`.
*   `session_prefix` - String to prefix session variables with. Default is `WorkETC_`.
*   `header_method` - How to provide the VeetroSession header. If you are windows, change this to `WorkETC::HEADER_INI`. Default is `WorkETC::HEADER_STREAM`.



Methods
-------

These are the extra methods (beyond worketc docs) that are provided in the PHP-WorkETC Class. Optional values are preset.

    // Creates a worketc soapclient.
    $worketc = new WorkETC($options=array());
    
    // Logs into the website with the specified credentials and saves the VeetroSession key.
    $worketc->login($user, $pass, $options=array());
    
    // Retrieves a session variable that is used by PHP-WorkETC.
    $worketc->session($varname, $value=false);
    
    // Gets the URL requests are being made to.
    $worketc->getUrl();
    
    // Gets the php SoapClient
    $worketc->getClient();

Examples
--------

See examples.php for a working example. Just provide your alias, username, and password.