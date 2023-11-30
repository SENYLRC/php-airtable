<?php
#By Zachary Spalding @Southeastern New York Library Resources Council
#https://airtable.com/<YOUR BASE ID>/api/docs
#https://codepen.io/airtable/full/MeXqOg
#Mkae sure to put in your API key from Airtable along with your CSS for the DIV tags and table and base ID
    $api_key = '<ADD YOUR API KEY>';
    $base_id = '<YOUR BASE ID>';
    $table_id = '<YOUR TABLE ID>';
    $url_options = 'sort%5B0%5D%5Bfield%5D=+fldeb6MnkJPtDSBQl+&sort%5B0%5D%5Bdirection%5D=asc';

    //airtable filter OR({Status} = 'Active', {Status} = 'Under Contract')
    //https://codepen.io/airtable/full/MeXqOg
    //https://airtable.com/appcWjth99nPfXwk1/api/docs#curl/table:digital%20navigators
    // Define the Airtable API endpoint
    $endpoint = "https://api.airtable.com/v0/appcWjth99nPfXwk1/tblJ6WBHWBHjQAhfi?filterByFormula=OR(%7BStatus%7D+%3D+'Active'%2C+%7BStatus%7D+%3D+'Under+Contract')&sort%5B0%5D%5Bfield%5D=County+(from+SENY+Orgs)&sort%5B0%5D%5Bdirection%5D=asc&sort%5B1%5D%5Bfield%5D=Organization+(from+Library+or+Nonprofit)&sort%5B1%5D%5Bdirection%5D=asc";
    // Set up the HTTP headers with your API key
    $headers = [
        'Authorization: Bearer ' . $api_key,
    ];

    // Make a GET request to fetch records from Airtable
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    // Parse the JSON response
    $data = json_decode($response, true);

    if ($data === null) {
        echo "Failed to parse JSON response from Airtable API.";
    } else {

        //initialize variables
        $previousCounty = null;
        $previousOrg = null;
        $countycount = 0;
        $orgcount = 0;
        $rowopen = 0;
        $cellopen = 0;
        $count = 0;

        echo "<div class='SenyMemberDirTable'>";

        foreach ($data['records'] as $record) {
            $fields = $record['fields'];
            $first =  $fields['First Name'];
            $last =  $fields['Last Name'];
            $org =  $fields['Organization (from Library or Nonprofit)'];
            $url =  $fields['URL (from SENY Orgs)'];
            $street = $fields['Street (from SENY Orgs)'];
            $city = $fields['City (from SENY Orgs)'];
            $state = $fields['State (from SENY Orgs)'];
            $zip = $fields['Zip Code (from SENY Orgs)'];
            $county = $fields['County (from SENY Orgs)'];
            $phone = $fields['Phone Number'];
            $region = $county[0];
            $libname = $org[0];

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


            if ($org[0] != $previousOrg) {
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

                echo "<h2><a href='" . $url[0] . "'>" . $org[0] . "</a> </h2>";
                echo "<h3>" . $street[0] . ", " . $city[0] . ", " . $state[0] . ", " . $zip[0] . "</h3>";
                echo "<h3>Digital Navigator(s):</h3>";
                echo "<br>";
                $previousOrg = $org[0];
            }
            //list people
            echo "<div style='font-size:22px'>";
            echo "<p>{$first} {$last} $phone</p>";
            echo "</div>"; //end font style
            echo "<br>";
        } //end foreach loop
        echo "</div>";
    }
