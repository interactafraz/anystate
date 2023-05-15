# AnyState
AnyState is a lightweight tool to easily store, update and retrieve values (like texts, numbers or arrays) in a JSON file.

It was developed for automated processes where different devices, apps and operating systems need to exchange data with each other in a simple and fast way. This includes workflow tools like [n8n](https://github.com/n8n-io/n8n), [IFTTT](https://ifttt.com/) and [Zapier](https://zapier.com/) as well as [iOS Shortcuts](https://support.apple.com/guide/shortcuts/welcome/ios).

### Possible use cases
* Checkin at work using iOS shortcut, use AnyState to set `working` status to `true`, add timestamp to Google Sheet via n8n
* Get cards from Trello board via n8n, store their attributes with AnyState, use a custom dashboard in your office to display Trello board statistics
* Reduce API calls by caching data within AnyState
* Monitor and visualize real-time data from sensors or IoT devices (like temperature, humidity, motion detection)

## Features

* Self host-able
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

**iOS Shortcuts:** To generate this format from an iOS Shortcut, follow this guide:
1. Create a `dictionary` and add your keys/values as items
2. Add the dictionary to a `text` action
3. Use a `Get Contents of URL` action to send the data to your AnyState instance. Set it up like this:
* URL: [Url to your AnyState instance]
* Method: POST
* Request Body: Form
* Request Body field (key): data
* Request Body field (Text): Text from `text` action
	
### Read data
Apart from reading the whole JSON file you can also use parameters (one at a time) to get the data for specific keys. If you omit these parameters the script will output a plain preview of all keys and values (for better readability this doesn't output arrays).

* Get value of key with `index.php?state=currentTemp` (arrays not supported)
* Get last update timestamp for individual value `index.php?time=currentTemp` (arrays not supported)

## Roadmap

- [ ] UI improvement: Display arrays in preview without reducing readability

## Notes

* If you're planning to handle sensitive data with this tool on a publicly accessible server you should harden your system using at least htaccess restrictions.

## License

[MIT](https://github.com/interactafraz/anystate/blob/main/LICENSE.txt)