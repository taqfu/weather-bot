<?php
if (!file_exists(getcwd() . "/weather-bot/weather-bot-cfg.php")){
    exit("Copy weather-bot-cfg.example.php to weather-bot-cfg.php and fill it out please.\n");
}

require("weather-bot-cfg.php");
if (ACCUWEATHER_URL==NULL || MAX_HIGH_TEMP==NULL || MAX_LOW_TEMP==NULL){
    exit("Please fill out your weather-bot-cfg.php first\n");
}
$highest_temp_today = false;
$already_messaged=false;
$ch = curl_init(ACCUWEATHER_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$weather_report = curl_exec($ch);
curl_close($ch);
$report_arr = explode("\n", $weather_report);
$span_arr = explode("</span>", $report_arr[301]);
$rel_str = substr($span_arr[1], -3);
$temp_now = substr($rel_str, 0, 1) == ">" ?  substr($rel_str, 1) : $rel_str;
$date = date("m-d-Y");
$filename = getcwd() . "/weather-bot/history/" . $date . ".json";
if (!file_exists($filename)){
    update_history($filename, [time()=>$temp_now]);
    $highest_temp_today=true;
} else {
    $temp_history = json_decode(file_get_contents($filename), true);

    foreach ($temp_history as $time=>$temp){
        echo date("H:i:s", $time) . " - " . $temp . "\n";
	if ($temp>=MAX_HIGH_TEMP){
		$already_messaged=true;
	}
        if ($temp_now>$temp){
            $highest_temp_today = true;
        }
    }
    $temp_history[time()]=$temp_now;

    if ($temp!=$temp_now){
        update_history($filename, $temp_history);
    }
}

if ($temp_now>=MAX_HIGH_TEMP && $highest_temp_today && !$already_messaged){

	send_alert("It is now " . $temp_now . " degrees.");
}
function send_alert ( $msg){
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
