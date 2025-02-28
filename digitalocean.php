<?php

print("Checking to se if IP needs updating!\n");

/**
 * @param null $record
 *
 * @return array|bool
 */
function getRecords($record = null)
{
	$record = (!is_null($record))? $record: "";
	$apiUrl = API_URL . "domains/" . DOMAIN  . "/records/" . $record;

	$ch     = curl_init($apiUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_TIMEOUT);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, CURL_HEADERS);

	$data   = curl_exec($ch);
	$info   = curl_getinfo($ch);
	$respC  = $info["http_code"];

	curl_close($ch);

	if ($data === false || $respC !== 200) {
		return false;
	}
	print("Domain records fetched!\r\n");
	$data   = json_decode($data, false);
	$return = [];
	foreach ($data->domain_records as $record) {
		if (in_array($record->name, DOMAINNAME) && in_array($record->type, RTYPE)) {
			array_push($return, $record);
		}
	}

	if (!empty($return))
		return $return;

	return false;
}

/**
 * @param $data
 * @param $ip
 * @param $oldIp
 * @param $id
 *
 * @return bool
 */
function setRecord($data, $ip, $oldIp, $id)
{
	$upatedString = str_replace($oldIp, $ip, $data);
	print("Updating record to: {$upatedString}\n");
	$data = json_encode([
		"data" => $upatedString
	]);

	$apiUrl = API_URL . "domains/" . DOMAIN  . "/records/" . $id;
	$curl_header = CURL_HEADERS;
	array_push($curl_header, "Content-Length: " . strlen($data));

	$ch     = curl_init($apiUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_TIMEOUT);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_header);

	$data   = curl_exec($ch);
	curl_close($ch);
	return ($data !== false);
}

try {

	$currentIp  = gethostbyname('your_domain_test_url'); //domaintest.example.org
	print("Current IP: {$currentIp}\n");
	$newIp      = file_get_contents("https://api.ipify.org");
	print("New IP: {$newIp}\n");

	if ($currentIp != $newIp) {
		print("IP needs to be updated.\nTrying to update now!\n");

		define("API_KEY",       "your_api_key");
		define("API_URL",       "https://api.digitalocean.com/v2/");
		define("DOMAIN",        "your_domain"); //example.org
		define("RTYPE",         ["A", "TXT"]);
		define("DOMAINNAME",    ["@", "domaintest"]);
		define("CURL_TIMEOUT",  15);
		define("CURL_HEADERS",  [
			"Content-Type:application/json",
			"Authorization: Bearer " . API_KEY
		]);

		$records    = getRecords();
		if ($records === false) {
			throw new Exception("Unable to find requested record in DigitalOcean account!");
		}

		foreach ($records as $record) {
			if (strpos($record->data, $currentIp) !== false) {
				print("Matched found in records: {$record->data}\n");
				if (setRecord($record->data, $newIp, $currentIp, $record->id) === false) {
					throw new Exception("Unable to update IP address");
				}
				print("IP address successfully update!\r\n");
			}
		}
	} else {
		print("IP's already matches!\r\n");
	}

} catch (Exception $e) {
	echo "Error: {$e->getMessage()}\r\n";
	exit(1);
}
exit(0);
