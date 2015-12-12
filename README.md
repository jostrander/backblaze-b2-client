# BackBlaze B2 API Client for PHP 5.3+

A PHP Library to access [BackBlaze's B2 API](https://www.backblaze.com/b2/cloud-storage.html)

License: [MIT](LICENSE)

Requirements:
  * PHP 5.3+,
  * PHP [cURL extension](http://php.net/manual/en/curl.installation.php) with SSL enabled (it's usually built-in).

## Setup
```
"require": {
  "jostrander/backblaze-b2-client": "0.1-alpha"
}
```

Then:

```
<?php
use BackBlazeB2\Client as B2Client;

$client = new B2Client($accountId, $applicationKey);
```

## Under Development

As BackBlaze's B2 is still in beta, this project can only be considered alpha and shouldn't be used in production without improvements and proper tests.

Pull requests are welcome.