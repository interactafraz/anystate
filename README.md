# AnyState
AnyState is a lightweight tool to easily store and retrieve values (like texts, numbers or arrays) in a JSON file using your own web server.

It was developed for automated processes where different devices, apps and operating systems need to exchange data with each other in a simple and fast way. This includes workflow tools like [n8n](https://github.com/n8n-io/n8n) as well as iOS Shortcuts.

### Possible use cases
* Checkin at work using iOS shortcut, use AnyState to set `working` status to `true`, add timestamp to Google Sheet via n8n
* Get cards from Trello board via n8n, store their attributes with AnyState, use a custom dashboard in your office to display Trello board statistics

## Features
* Save keys and values (via POST method)
* Get single value for specific key (via GET parameter)
* Get timestamp for specific key (via GET parameter)
* Limit maximum storage time for keys (allows keys to expire)
* Preview keys and values in browser

## Prerequisites
* Common web server with PHP support

## Installation
* Copy files to web server

## Usage
### Save data
The script expects POST data in the following format

    {"currentTemp": "26","currentConditions": "rainy"}

### Read data
Apart from reading the whole JSON file you can also use parameters (one at a time) to get the data for specific keys. If you omit these parameters the script will output a plain preview of all keys and values (for better readability this doesn't output arrays).

* Get value of key with `index.php?state=currentTemp` (arrays not supported)
* Get last update timestamp for individual value `index.php?time=currentTemp` (arrays not supported)

## Notes

* If you're planning to handle sensitive data with this tool on a publicly accessible server you should harden your system using at least htaccess restrictions.

## License
tba