# Twilio OPT Test

This package is used to send OTP SMS via Twilio.

### Installation

Dillinger requires PHP version >=5.6

```sh
$ composer install
```

### Usage
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

require_once './src/TwilioAuthyApi/Authy.php'; // use to test package

use TwilioAuthy\Authy;

$api_key = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; //Twilio API Key
$email          = 'xxxxxxxxxx@domain.com';
$cellphone      = 00000000000; // Cellphone number
$country_code   = 1; // Country code for eg. (USA 1, England 44)

$authy_api = new Authy($api_key);

// Created user account at authy
$user = $authy_api->registerUser($email, $cellphone, $country_code); 
$authy_id = $user->id;

// Send SMS to the user
$sms = $authy_api->requestSms($authy_id);

// Response printed
echo json_encode($sms);
?>
```
