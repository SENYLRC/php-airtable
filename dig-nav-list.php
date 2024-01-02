
#By Zachary Spalding @Southeastern New York Library Resources Council
#https://airtable.com/<YOUR BASE ID>/api/docs
#https://codepen.io/airtable/full/MeXqOg

// Airtable API key and URL
$apiKey =  '<ADD YOUR API KEY>';
$baseId = '<YOUR BASE ID>';
$tableId = '<YOUR TABLE ID>';
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
    'fields' => ['Organization (from Library or Nonprofit)', 'URL (from SENY Orgs)', 'Street (from SENY Orgs)', 'City (from SENY Orgs)', 'State (from SENY Orgs)', 'Zip Code (from SENY Orgs)', 'County (from SENY Orgs)', 'Phone Number', 'First Name', 'Last Name'], // Update with the correct field names
    'filterByFormula' => '',
    'filterByFormula' => 'OR(FIND("Active", {Status}), FIND("Contract", {Status}))',
    'sort' => [
        ['field' => 'County (from SENY Orgs)', 'direction' => 'asc'],
        ['field' => 'Organization (from Library or Nonprofit)', 'direction' => 'asc'],
    ],
    'pageSize' => 100, // Maximum page size
];

$previousCounty = null;
$previousOrg = null;
$countycount = 0;
$orgcount = 0;
$rowopen = 0;
$cellopen = 0;
$count = 0;

echo "<div class='SenyMemberDirTable'>";

// Loop through records and print information
do {
    $data = fetchData($apiUrl, $apiKey, $params);
    //for testing
    //print_r($data);

    if (isset($data['records'])) {
        foreach ($data['records'] as $record) {
            $organization = $record['fields']['Organization (from Library or Nonprofit)'];
            $county = $record['fields']['County (from SENY Orgs)'];
            $url = $record['fields']['URL (from SENY Orgs)'];
            $street = $record['fields']['Street (from SENY Orgs)'];
            $city = $record['fields']['City (from SENY Orgs)'];
            $state = $record['fields']['State (from SENY Orgs)'];
            $zip = $record['fields']['Zip Code (from SENY Orgs)'];
            $phone = $record['fields']['Phone Number'];
            $first =  $record['fields']['First Name'];
            $last =  $record['fields']['Last Name'];

            //check if new county
            if ($county[0] != $previousCounty) {

                //is previous cell and row open
                if ($cellopen == 1) {

                    echo "</div></div>"; //close cell and row
                    $cellopen = 0;
                    $rowopen = 0;
                    $count = 0;
                }

                echo "<div class='SenyMemberDirTableRow'>";
                echo "<hr>";

                echo "<div class='HVconnCountyTableCell'>";
                echo "<h2>" . $county[0] . " County</h2>";
                echo "</div>";  //end county cell
                $previousCounty = $county[0];
                echo "<br><hr>";
            }


            if ($organization != $previousOrg) {
                //new library should be closing last cell
                if ($cellopen == 1) {
                    echo "</div>";
                    $cellopen = 0;
                }
                if ($count++ % 2 == 0) {
                    echo "</div>"; #end the SenyMemberDirTableRow  
                    $rowopen = 0;
                }
                if ($rowopen == 0) {
                    echo "<div class='SenyMemberDirTableRow'>";
                    $rowopen = 1;
                }
                if ($cellopen == 0) {
                    echo "<div class='SenyMemberDirTableCell'>";
                    $cellopen = 1; //opening the cell
                }

                echo "<h2><a href='" . $url[0] . "'>" . $organization[0] . "</a> </h2>";
                echo "<h3>" . $street[0] . ", " . $city[0] . ", " . $state[0] . ", " . $zip[0] . "</h3>";
                echo "<h3>Digital Navigator(s):</h3>";
                echo "<br>";
                $previousOrg = $organization;
            }
            //list people
            echo "<div style='font-size:22px'>";
            echo "<p>{$first} {$last} $phone</p>";
            echo "</div>"; //end font style
            echo "<br>";
        } //end foreach loop
    }

    // Set the offset for the next request
    $params['offset'] = $data['offset'] ?? null;
} while (isset($data['offset']));

if (!isset($data['records'])) {
    echo 'No records found.';
}
