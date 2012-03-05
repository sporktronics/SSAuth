# SSAuth

SSAuth (Stupid-Simple Authentication) is a nifty way to keep track of
user accounts, for example, on websites. It's written in PHP. SSAuth
does everything in a directory on the disk, using text files formatted
in JSON; as such, it does not require a database. Each user gets their
own subdirectory, so you can easily keep each user's stuff separate.

## How to Use
Include "ssauth.php" in your PHP code. Make an empty directory and be
sure it's writable to your app. The first time your app runs, it will
set the directory up as necessary.

## Examples
Set up the `SSAuth` object, pointing it at your users directory:

    $auth = new SSAuth('users/');

Register a new user:

    $auth->register('username','p4$$w0rd!','user@site.com');
    // Note that SSAuth does not verify the email address.

Log in:

    $auth->login('username','PASSWORD!'); // returns false
    $auth->login('username','p4$$w0rd!'); // returns true

Are we logged in?

    return $auth->isLoggedIn(); // true

Get the path to the user's subdirectory, so we can put stuff in it!

    $userDir = $auth->getDirForLoggedInUser();

Log out:

    $auth->logout();

Super easy? We think so! Take a look at the code to see all the
functions available. It's got nice comments, it won't bite!
