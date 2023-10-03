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
* Save keys and values (via POST or GET requests)
* Get single value for specific key (via GET parameter)
* Get timestamp for specific key (via GET parameter)
* Limit maximum storage time for keys (allows keys to expire)
* Preview keys and values in browser

## Prerequisites
* Common web server with PHP support

## Installation
* Copy files to web server

## Usage
### Save data - GET
For simple use cases you can set a key and its value with URL parameters:

    index.php?set=currentTemp&content=26

> Sanitization is not performed on GET requests. So special characters should be escaped before sending them to AnyState. Numbers will be converted to int/float variables automatically.

### Save data - POST
AnyState expects one *data* key with a *value* that contains your **JSON encoded** key-data pairs. 

#### Example: General usage
1. To set *currentTemp* to *26* and *currentConditions* to *rainy* you need to construct a **string** in the following format: `{"currentTemp": 26,"currentConditions": "rainy"}`
2. You should then add this string (encoded in JSON) to the *data* key. So the final POST body with a `application/x-www-form-urlencoded` header would look like this

    data={"currentTemp":26,"currentConditions":"rainy"}

> Sanitization is not performed on POST requests. So special characters should be escaped before sending them to AnyState.
	
#### Example: iOS Shortcuts
To generate this format from an iOS Shortcut, follow these instructions:
1. Create a `dictionary` and add your keys/values as items
2. Add the dictionary to a `text` action
3. Use a `Get Contents of URL` action to send the data to your AnyState instance. Set it up like this:
* URL: [Url to your AnyState instance]
* Method: POST
* Request Body: Form
* Request Body field (key): data
* Request Body field (Text): Text from `text` action

#### Example: PHP
To submit data with a PHP script use this example function ([Source](https://stackoverflow.com/questions/5647461/how-do-i-send-a-post-request-with-php/6609181#6609181)):

    function sendToAnyState($keyData,$valueData){
        $url = 'https://example.com/anystate/';
        $data = [$keyData => $valueData];

        // use key 'http' even if you send the request to https://...
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query( ['data' => json_encode($data, JSON_PRETTY_PRINT)] ),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        //if ($result === false) {
            /* Handle error */
        //}

        //var_dump($result);
	
    }

### Read data
Apart from reading the whole JSON file you can also use parameters (one at a time) to get the data for specific keys or filter all entries. If you omit these parameters the script will output a simple preview of all keys and values including their last updated timestamps. For better readability the preview doesn't output arrays.

* Get value of key in plain text format with `index.php?state=currentTemp` (arrays not supported)
* Get value of key in JSON format with `index.php?state=currentTemp&format=json`
* Get last update timestamp for individual value `index.php?time=currentTemp` (arrays not supported)
* Get only keys and values (without timestamps) as JSON `index.php?filter=values&format=json`

## Roadmap

- [ ] UI improvement: Display arrays in preview without reducing readability

## Notes

* If you're planning to handle sensitive data with this tool on a publicly accessible server you should harden your system using at least htaccess restrictions.

## License

[MIT](https://github.com/interactafraz/anystate/blob/main/LICENSE.txt)