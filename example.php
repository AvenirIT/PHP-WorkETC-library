<?

header("Content-type: text/plain;charset=UTF-8");
require 'worketc.php';

// Start the library.
$worketc = new WorkETC('avenirit');

// If the client is not connected, try to connect with an email/password.
if(!$worketc->client())
    if(!$worketc->login("user@domain.tld","password"))
        die("Bad Password");

// Get the user id.
$userID = $worketc->session("UserID");

// Get Activities for user (note the spelling error, worketc isn't going to fix this).
$activities = $worketc->GetActvities();
echo "Found ".count($activities)." activities.\n";

// Get Support Cases for User
// top - undocumented by worketc, but it means how many records to return. 0 = all.
$supportCases = $worketc->GetSupportCasesByOwner(array('EntityID'=>$userID, 'status'=>'Open', 'top'=>0));
echo "Found ".count($activities)." support cases.\n";

// Get Projects for User
$projects = $worketc->GetProjectsByMember(array('EntityID'=>$userID, 'Membership'=>'Any'));
echo "Found ".count($activities)." projects.\n";
