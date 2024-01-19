<?php
require '../utils.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//get account code and api key from env file
$filterByIp = false;
$env = parse_ini_file('../.env');
$account=$env['ACCOUNT_CODE'];
$apiKey =$env['API_KEY'];

$account_code = "/".$account; // Do not remove "/". You will find your Account Code in the section Admin > Account > Account Code
$api_key = $apiKey; // You will find your API Key in the section Admin > Security > API Key. If you do not see this section, it may be related to the role your user has. If you have the Administrator role, get in touch with support@nicepeopleatwork.com for more information.

date_default_timezone_set('Europe/Madrid');

$ttl_ms = 31536000000; // Expiration time in milliseconds. In this example, 31536000000ms = 1 year.
$expiration_time = round(microtime(true) * 1000) + $ttl_ms;
$dateToken = '&dateToken=' . $expiration_time;

$host = "https://api.npaw.com";
$call_type = '/data?'; // This example is specific for /data?. For other API call types like /rawdata?, other parameters must be used.

// API Call Parameters --- All of them are optional, except "metrics".
$metrics = "&metrics=views"; // Format: "&metrics=metric_code1,metric_code2,metric_code3..."

//define datetimes from 6 hours ago to now with granurality 6 hours
$baseTime = date("Y-m-d H:i:s");
$minus6HoursNowTime = date('Y-m-d H:i:s',strtotime('-6 hour',strtotime($baseTime)));
$fromDate = 'fromDate=' . urlencode($minus6HoursNowTime); // Custom date in format: "YYYY-MM-DD", "YYYY-MM-DD HH:MM:SS" or "lastxhours" "lastxdays" (these don't need toDate parameter).
$toDate = '&toDate=' . urlencode($baseTime); // Custom date in format: "YYYY-MM-DD", "YYYY-MM-DD HH:MM:SS".
$granularity = "&granularity=last6hours"; // None by default.

/* Available Granularities (Depending on the date range selected, they will be available or not. More details below).

second, 5second, 10second, 20second, 30second, minute, 5minute, 10minute, 20minute, 30minute, hour, day, week, month

Date Ranges ||<-->|| Minimum granularity available (same as in the UI):

lastminute, last5minutes, last10minutes ||<-->|| second
last20minutes, last30minutes, lasthour ||<-->|| 5second
last2hours, last3hours, last6hours, last24hours, today, yesterday ||<-->|| minute
thisweek, lastweek, last7days, previous7days, thismonth, lastmonth, previous4weeks, last30days, last60days, last90days ||<-->|| hour
last3months, lastquarter, thisquarter, previous12weeks, last2quarters, last6months, previous6months, last12months ||<-->|| day

NOTE: The Data API supports returning up to 3000 data points.

Filtering: Below you will find some examples of the different filterings that are allowed in the UI, and how to use them.
Remember to add the filter variables that you would like to use inside "pre_url" variable after "call_type" and before "dateToken". */

$view_type_filter = "&type=all"; // Always comma separated. Can be combined. Only live, only vod, only all, only two of them...

//get current ip for filter calls that do request
if($filterByIp) {
    $ip = getIP();
    $includeFilterIp = false;
    if ($ip !== false) {
        $dimension_filter_includeIp = '&filter=' . urlencode('[{"name":"ipDoingRequest","rules":{"ip":["' . $ip . '"]}}]');
    }
}
// Dimension Filtering Examples:
$dimension_filter_include = '&filter=' . urlencode('[{"name":"Argentina and Germany","rules":{"country":["Argentina","Germany"]}}]'); // Filter by Dimension (Include) --> Name = Whatever, Rules must contain the Dimension code and the values inside.
$dimension_filter_split = '&filter=' . urlencode('[{"name":"Argentina","rules":{"country":["Argentina"]},{"name":"Germany","rules":{"country":["Germany"]}}]'); // Filter by Dimension (Split) --> Name = Whatever, Rules must contain the Dimension code and the values inside.
$dimension_filter_exclude = '&filter=' . urlencode('[{"name":"NOT Argentina,Germany","rules":{"-country":["Argentina","Germany"]}}]'); // Filter by Dimension (Exclude) --> Name = Whatever, Rules must contain the Dimension code with a "-" before the code and the values inside.

// Metric Filtering Examples:
$metric_filter = '&filter=' . urlencode('[{"name":"Avg. Bitrate (Mbps) > 1","rules":{},"metric_rules":[{"metric":"bitrateMbps","operation":"gt","values":["1"]}]}]'); // Filter by Metric (Greater Than) --> Name = Whatever, Rules must contain the metric code and the operation type. Find a list below.
$metric_filter_between = '&filter=' . urlencode('[{"name":"Avg. Bitrate (Mbps) > 1","rules":{},"metric_rules":[{"name":"Avg. Bitrate (Mbps) >= && < 1 - 2","rules":{},"metric_rules":[{"metric":"bitrateMbps","operation":"bt","values":["1","2"]}]}]'); // Filter by Metric (Between) --> Name = Whatever, Rules must contain the metric code and the operation type. Find a list below.

/* Available operation types for Metric filtering:
Greater or equal than (>=) --> gte || Greater than (>) --> gt || Between (>= && <) --> bt || Lower or equal than (<=) --> lte || Lower than (<) --> lt || # Equal to (=) --> eq
Reminder! Not every metric allows to be filtered by it. */

// CSV Related Parameters
$csvFormat = "&csvFormat=true"; // This parameter allows you to format the data in CSV instead of the default JSON format.
$outFile = "&outFile=true"; // Allows you to decide whether downloading the CSV file when executing the API call or not.
$outFilename = "&outFilename=NAME_HERE"; // If previous parameters are true, allows you to setup the name that you want for the downloaded file.

/* Group by TOPS parameters -- Coming Soon...! #orderby #sortby #limit #offset

Building the pre-final URL: The parameters not fullfilled/used must be commented above and removed from this "pre_url" variable.
                            For the call to return desired results, ensure to include all relevant parameters in the pre-url.

                            Important! The first concatenated parameter after "call_type" must not contain "&" on its beginning. In this case, it should be "fromDate=" instead of "&fromDate=". */

$pre_url = $call_type . $fromDate . $toDate . $view_type_filter . $granularity . $dimension_filter_includeIp. $metrics . $dateToken;
// Token Generation

$token_encrypt = md5($account_code . $pre_url . $api_key);
$token = $token_encrypt;

// Building Final URL
$final_url = $host . $account_code . $pre_url . "&token=" . $token;

$response = file_get_contents($final_url);
http_response_code(200);
//OUTPUT JUST PLAYS
$responseFormatted = json_decode($response,true);
$numberPlays= $responseFormatted['data'][0]['metrics'][0]['values'][0]['data'][0]['value'];
$outPutResponse = array('totalPlays'=>$numberPlays);
echo json_encode($outPutResponse);