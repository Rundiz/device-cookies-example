# Device cookies example

This is an example source code for device cookies in **PHP** from the OWASP document "**[Slow Down Online Guessing Attacks with Device Cookies][1]**".

This source code is not a ready to work. You have to modify things and implement to match your code & DB.<br>
It is made for testing but you can use it for any purpose under MIT license.

Tested up to PHP 8.4.

## Installation
It is required at least PHP 7.0

Import the **.sql** file into your database, change configuration in **config.php** file.

## Begin testing
Browse to **form.php** page and enter email: **admin@localhost** and password: **pass**. It should be displaying that you had logged in successfully.

Try again with wrong password until you get lockout.<br>
For more test and information please continue reading on the OWASP page.

## Tools
### Generate random keys
For generate random secret keys online.<br>
[link 1][gk1], [link 2][gk2], [link 3][gk3]

### Check length
For checking that generated keys is in the length you want.<br>
[link 1][chlen1], [link 2][chlen2], [link 3][chlen3]


[1]: https://owasp.org/www-community/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies
[gk1]: http://www.unit-conversion.info/texttools/random-string-generator/
[gk2]: https://passwordsgenerator.net/
[gk3]: https://keygen.io/ 
[chlen1]: http://string-functions.com/length.aspx
[chlen2]: https://www.charactercountonline.com/
[chlen3]: https://codebeautify.org/calculate-string-length
