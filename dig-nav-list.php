
#By Zachary Spalding @Southeastern New York Library Resources Council
#https://airtable.com/<YOUR BASE ID>/api/docs
#https://codepen.io/airtable/full/MeXqOg

// Airtable API key and URL
$apiKey =  '<ADD YOUR API KEY>';
$baseId = '<YOUR BASE ID>';
$tableId = '<YOUR TABLE ID>';
$apiUrl = "https://api.airtable.com/v0/{$baseId}/{$tableId}";

// --- Helpers ---
function fetchData($url, $apiKey, $params = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) { echo 'Error: ' . curl_error($ch); exit; }
    curl_close($ch);
    return json_decode($response, true);
}

// Normalize field that may be scalar or array (take first if array)
function field_first($fields, $name) {
    if (!isset($fields[$name])) return '';
    $v = $fields[$name];
    if (is_array($v)) return isset($v[0]) ? trim((string)$v[0]) : '';
    return trim((string)$v);
}

$params = [
    'fields' => [
        'Organization','URL','Street','City','State','Zip Code','County',
        'Phone Number','First Name','Last Name','Status'
    ],
    // ✅ Your updated filter
    'filterByFormula' => 'OR(FIND("Active", {Status}), FIND("Cohort ONE", {Status}), FIND("Cohort ONE Audit", {Status}))',
    'pageSize' => 100,
    // (Don’t rely on API sort; we’ll sort locally.)
];

// Fetch all pages
$all = [];
do {
    $data = fetchData($apiUrl, $apiKey, $params);
    if (isset($data['records'])) {
        foreach ($data['records'] as $r) {
            $f = $r['fields'];
            $row = [
                'county' => field_first($f, 'County'),
                'org'    => field_first($f, 'Organization'),
                'url'    => field_first($f, 'URL'),
                'street' => field_first($f, 'Street'),
                'city'   => field_first($f, 'City'),
                'state'  => field_first($f, 'State'),
                'zip'    => field_first($f, 'Zip Code'),
                'phone'  => field_first($f, 'Phone Number'),
                'first'  => field_first($f, 'First Name'),
                'last'   => field_first($f, 'Last Name'),
            ];
            if ($row['county'] !== '' && $row['org'] !== '') {
                $all[] = $row;
            }
        }
    }
    $params['offset'] = $data['offset'] ?? null;
} while (isset($data['offset']));

if (empty($all)) { echo 'No records found.'; exit; }

// Group by County → Organization, aggregate people
$byCountyOrg = [];
foreach ($all as $row) {
    $county = $row['county'];
    $org    = $row['org'];

    if (!isset($byCountyOrg[$county])) $byCountyOrg[$county] = [];
    if (!isset($byCountyOrg[$county][$org])) {
        $byCountyOrg[$county][$org] = [
            'org'    => $org,
            'url'    => $row['url'],
            'street' => $row['street'],
            'city'   => $row['city'],
            'state'  => $row['state'],
            'zip'    => $row['zip'],
            'people' => [],
        ];
    }
    $byCountyOrg[$county][$org]['people'][] = [
        'first' => $row['first'],
        'last'  => $row['last'],
        'phone' => $row['phone'],
    ];
}

// Sort counties and orgs alphabetically (natural, case-insensitive)
uksort($byCountyOrg, fn($a, $b) => strnatcasecmp($a, $b));
foreach ($byCountyOrg as $county => &$orgs) {
    uksort($orgs, fn($a, $b) => strnatcasecmp($a, $b));
}
unset($orgs);

// --- Render ---
echo "<div class='SenyMemberDirTable'>";

foreach ($byCountyOrg as $countyName => $orgs) {
    // County header row
    echo "<div class='SenyMemberDirTableRow'>";
    echo "<hr>";
    echo "<div class='HVconnCountyTableCell'><h2>" . htmlspecialchars($countyName) . " County</h2></div>";
    echo "<br><hr>";
    echo "</div>";

    // Two-up grid for orgs
    $i = 0;
    $rowOpen = false;
    foreach ($orgs as $orgName => $info) {
        if ($i % 2 === 0) {
            if ($rowOpen) echo "</div>"; // close previous org row
            echo "<div class='SenyMemberDirTableRow'>";
            $rowOpen = true;
        }

        echo "<div class='SenyMemberDirTableCell'>";
        $orgText = htmlspecialchars($orgName);
        $orgLink = $info['url'] ? "<a href='" . htmlspecialchars($info['url']) . "'>{$orgText}</a>" : $orgText;
        echo "<h2>{$orgLink}</h2>";

        $addressParts = array_filter([
            $info['street'],
            $info['city'],
            $info['state'],
            $info['zip']
        ], fn($v) => $v !== '');
        if (!empty($addressParts)) {
            echo "<h3>" . htmlspecialchars(implode(', ', $addressParts)) . "</h3>";
        }

        echo "<h3>Digital Navigator(s):</h3><br>";
        echo "<div style='font-size:22px'>";
        foreach ($info['people'] as $p) {
            $name = trim($p['first'] . ' ' . $p['last']);
            echo "<p>" . htmlspecialchars($name) . ( $p['phone'] ? " " . htmlspecialchars($p['phone']) : "" ) . "</p>";
        }
        echo "</div><br>";

        echo "</div>"; // cell
        $i++;
    }
    if ($rowOpen) echo "</div>"; // close last open org row in this county
}

echo "</div>"; // SenyMemberDirTable
