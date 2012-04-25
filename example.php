<?

header("Content-type: text/plain;charset=UTF-8");
require 'worketc.php';

$alias = "alias";
$email = "user@domain.tld";
$pass = "password";

// Start the library. (alias.worketc.com)
$worketc = new WorkETC(array('alias'=>$alias));

// If the client is not connected, try to connect with an email/password.
if(!$worketc->getClient())
    if(!$worketc->login($email, $pass))
        die("Bad Password");

// Get user id only.
$userID = $worketc->session("UserID");

// Get entire user object.
$user = $worketc->session("User");
echo "Logged in as {$user->Name}\n";

// Get Activities for user (note the spelling error, worketc isn't going to fix this).
$activities = $worketc->GetActvities()->Activity;
echo "Found ".count($activities)." activities.\n";

// Get Support Cases for User
// top - undocumented by worketc, but it means how many records to return. 0 = all.
$supportCases = $worketc->GetSupportCasesByOwner(array('EntityID'=>$userID, 'status'=>'Open', 'top'=>0))->SupportCase;
echo "Found ".count($supportCases)." support cases.\n";

// Get Projects for User
$projects = $worketc->GetProjectsByMember(array('EntityID'=>$userID, 'Membership'=>'Any'))->Project;
echo "Found ".count($projects)." projects.\n";