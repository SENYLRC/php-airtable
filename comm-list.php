<?php
#By Zachary Spalding @Southeastern New York Library Resources Council
#https://airtable.com/<YOUR BASE ID>/api/docs
#https://codepen.io/airtable/full/MeXqOg
#Mkae sure to put in your API key from Airtable along with your CSS for the DIV tags and table and base ID
$api_key = '<ADD YOUR API KEY>';
$base_id = '<YOUR BASE ID>';
$table_id = '<YOUR TABLE ID>';

$apiUrl = "https://api.airtable.com/v0/{$baseId}/{$tableId}";

// Set up cURL options
function fetchData($url, $apiKey, $params = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
        die();
    }

    curl_close($ch);

    return json_decode($response, true);
}

// Replace this function with your actual implementation to fetch organization details
function fetchOrganizationDetails($organizationId, $baseid, $apikey, $tableid)
{
    // Implement your logic to fetch organization details based on the provided ID
    // Make an additional API request to Airtable using $organizationId

    // For now, I'll just return a placeholder value

    $apiUrl = "https://api.airtable.com/v0/{$baseid}/{$tableid}/{$organizationId}";

    // Set up cURL options
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apikey,
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
        die();
    }

    curl_close($ch);

    $organizationDetails = json_decode($response, true);
    //for testing
    //print_r($organizationDetails);
    // echo     $organizationDetails['fields']['Organization'];
    // Return the organization details (modify as needed based on your Airtable structure)

    $org = $organizationDetails['fields']['Organization'];
    return $org;
}

// Initial request without offset
$params = [
    'fields' => ['Organization (from People)', 'Last Name (from People)', 'Full Name', 'Tags'], // Update with the correct field names
    'filterByFormula' => '',
    'sort' => [['field' => 'Last Name (from People)', 'direction' => 'asc']],
    'pageSize' => 100, // Maximum page size
];


// Set the operating year start date (July 1)
$operatingYearStart = date('Y') . '-07-01';

// Get the current date
$currentDate = date('Y-m-d');

// Check if the current date is on or after the operating year start
if (strtotime($currentDate) >= strtotime($operatingYearStart)) {
    // If on or after the operating year start, use the current year
    $operatingYear = date('Y');
} else {
    // If before the operating year start, decrement the year by 1
    $operatingYear = date('Y') - 1;
}

// Calculate the next year for display
$nextYear = $operatingYear + 1;

// Echo the operating year in the desired format
echo "The " . $operatingYear . '/' . $nextYear . " committee members are:<br><br><ul>";

//Storeing data in array so i can put the names in the correct part of the page
$chairArray = array();
$boardArray = array();
$nothingArray = array();

// Loop through records and print information
do {
    $data = fetchData($apiUrl, $apiKey, $params);
    //for testing
    //print_r($data);

    if (isset($data['records'])) {
        foreach ($data['records'] as $record) {
            $organizationId = $record['fields']['Organization (from People)'][0];
            $name = '<strong>' . htmlspecialchars($record['fields']['Full Name']) . '</strong>';
            if (isset($record['fields']['Tags'])) {
                // Get and print the value of the 'Tags' field
                $tags = $record['fields']['Tags'];
            } else {
                $tags = '';
            }

            // Fetch organization details based on the provided ID
            // The inital value is just an ID number we have to use this function to translate it to a name
            $organizationDetails = fetchOrganizationDetails($organizationId, $baseId, $apiKey, $tableId);
            //for testing
            //echo "my data" . $name . " " . $tags[0] . " " . $organizationDetails . "<br>";
            // Check the value of 'tags' and assign the name with organization to the corresponding array
            if ($tags[0] === 'chair') {
                $chairArray[] = $name . ', ' . $organizationDetails;
            } elseif ($tags[0] === 'Board Liaison') {
                $boardArray[] = $name . ', ' . $organizationDetails;
            } elseif ($tags[0] === 'Council Liaison') {
                $councilArray[] = $name;
            } else {
                $groupArray[] = $name . ', ' . $organizationDetails;
            }
        } //end foreach loop
    } //end if isset


    // Set the offset for the next request
    $params['offset'] = $data['offset'] ?? null;
} while (isset($data['offset']));

if (!isset($data['records'])) {
    echo 'No records found.';
}
echo '<ul>';
foreach ($chairArray as $chairName) {
    echo '<li style="list-style:square;">' . $chairName . ' (Chair)</li>';
}
foreach ($groupArray as $groupName) {
    echo '<li style="list-style:square;">' . $groupName . ' </li>';
}
echo '</ul><br>';

echo "<p>Southeastern Staff Liaison:</p><ul>";
foreach ($councilArray as $councilName) {
    echo '<li style="list-style:square;">' . $councilName . ' </li>';
}
echo '</ul><br>';
echo "<p>Southeastern Board Liaisons:</p><ul>";
foreach ($boardArray as $boardName) {
    echo '<li style="list-style:square;">' . $boardName;
}

echo '</ul>';
