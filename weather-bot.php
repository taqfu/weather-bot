<?php
if (!file_exists("weather-bot-cfg.php")){
    exit("Copy weather-bot-cfg.example.php to weather-bot-cfg.php and fill it out please.\n");
}
include("weather-bot-cfg.php");

if (ACCUWEATHER_URL==NULL || MAX_HIGH_TEMP==NULL || MAX_LOW_TEMP==NULL){
    exit("Please fill out your weather-bot-cfg.php first\n");
}
$highest_temp_today = false;
$ch = curl_init(ACCUWEATHER_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$weather_report = curl_exec($ch);
curl_close($ch);
//$weather_report = curl-this-shit
$temp_now =  strstr(substr(strstr($weather_report, "local-temp"), 12, 20), "&", true);
echo "Temperature now is:" . $temp_now . "\n";

$date = date("m-d-Y");
$filename = "history/" . $date . ".json";
echo "Searching for " . $filename . "\n";
if (!file_exists($filename)){
    update_history($filename, [time()=>$temp_now]);
    $highest_temp_today=true;
} else {
    $temp_history = json_decode(file_get_contents($filename), true);

    foreach ($temp_history as $time=>$temp){
        echo date("H:i:s", $time) . " - " . $temp . "\n";
        if ($temp_now>$temp){
            $highest_temp_today = true;
        }
    }
    $temp_history[time()]=$temp_now;

    if ($temp!=$temp_now){
        update_history($filename, $temp_history);
    }
}

if ($temp_now>=MAX_HIGH_TEMP && $highest_temp_today){
    echo "ALERT";
}
function send_alert ($user, $msg){
  curl_setopt_array($ch = curl_init(), array(
  CURLOPT_URL => "https://api.pushover.net/1/messages.json",
  CURLOPT_POSTFIELDS => array(
    "token" => PUSHOVER_APP_TOKEN,
    "user" => PUSHOVER_USER_KEY,
    "message" => $msg,
  ),
  CURLOPT_SAFE_UPLOAD => true,
  CURLOPT_RETURNTRANSFER => true,
));
curl_exec($ch);
curl_close($ch);
}

function update_history($filename, $time_arr){
    $fp = fopen ($filename, "w");
    fwrite($fp, json_encode($time_arr));
    fclose($fp);

}


?>
