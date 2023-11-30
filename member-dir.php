<?php
#By Zachary Spalding @Southeastern New York Library Resources Council
#https://airtable.com/appF5045dT9RSe7HP/api/docs
#https://codepen.io/airtable/full/MeXqOg
#Mkae sure to put in your API key from Airtable
// Airtable API key and URL
$apiKey = '<your api key>';
$baseId = 'appF5045dT9RSe7HP';
$tableId = 'tblG83rJka4zBkLla';
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

// Initial request without offset
$params = [
    'fields' => ['Organization', 'URL', 'Street', 'City', 'State', 'Zip Code', 'Membership Type', 'Phone', 'First Name', 'Last Name'], // Update with the correct field names
    'filterByFormula' => 'AND(OR({fldBgr1Y8E086bgMG}="Governing",{fldBgr1Y8E086bgMG}="HRVH"), NOT(FIND("Board",{fld4dThLfkTPRfNck})), {Organization}!="Southeastern New York Library Resources Council")',
    'sort' => [['field' => 'fld4dThLfkTPRfNck', 'direction' => 'asc']],
    'pageSize' => 100, // Maximum page size
];

echo "<div class='SenyMemberDirTable'>";
echo "<div class='SenyMemberDirTableRow'>";
$count = 1;
$rowcount = 1;
// Loop through records and print information
do {
    $data = fetchData($apiUrl, $apiKey, $params);
    //for testing
    //print_r($data);

    if (isset($data['records'])) {
        foreach ($data['records'] as $record) {
            $organization = $record['fields']['Organization'];
            $url = $record['fields']['URL'];
            $street = $record['fields']['Street'];
            $city = $record['fields']['City'];
            $state = $record['fields']['State'];
            $zip = $record['fields']['Zip Code'];
            $type = $record['fields']['Membership Type'];
            $phone = $record['fields']['Phone'][0];
            $first =  $record['fields']['First Name'][0];
            $last =  $record['fields']['Last Name'][0];


            echo "<div class='SenyMemberDirTableCell'>";
            if (strlen($url) > 1) {
                echo "<a href=\" " . $url . "  \" target=\"_blank\">" . $organization . "</a><br>";
            } else {
                echo $organization . "<br>";
            }
            echo $street . "<br>";
            echo $city . ", " . $state . "  " . $zip . "<br>";
            echo "Membership Level: " . $type . "<br>";
            echo "Contact: " . $first . " " . $last . "<br>";
            echo "Phone: " . $phone . "<br>";
            echo "</div>";  #End the cell
            if ($count++ % 2 == 0) {
                echo "</div>"; #end the SenyMemberDirTableRow
                if ($rowcount++ % 2 == 0) {
                    echo "<div class='SenyMemberDirTableRow'>"; #Start the next SenyMemberTableRow
                } else {
                    echo "<div class='SenyMemberDirTableRowGrey'>"; #Start the next SenyMemberDirTableRow
                }
            } # end the if seeing if this is the end of the row
            //echo "Organization: $organization, URL: $url, Street: $street, City: $city, State: $state, Zip Code: $zipCode, Membership Type: $membershipType<br>";


        }
    }

    // Set the offset for the next request
    $params['offset'] = $data['offset'] ?? null;
} while (isset($data['offset']));

if (!isset($data['records'])) {
    echo 'No records found.';
}
