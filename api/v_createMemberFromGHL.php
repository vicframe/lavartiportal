<?php

require_once 'Pillars_API.php'; // your existing class

function create_member_from_ghl($ghlOrder) {
    $pillars = pillars_api(); // creates an instance using your env API key

    $contactId = $ghlOrder['contact_id'] ?? uniqid('ghl_');
    $email = $ghlOrder['email'] ?? "unknown_" . $contactId . "@example.com";
    $birthDate = $ghlOrder['date_of_birth'] ?? "1970-01-01T00:00:00.000Z";
    $language = $ghlOrder['language'] ?? "English";
    $customData = $ghlOrder['Dream Destination'] ?? "N/A";

    $phone = $ghlOrder['phone'] ?? "";
    $address = [
        "type" => "primary",
        "line1" => $ghlOrder['address1'] ?? "Unknown Address",
        "line2" => null,
        "line3" => null,
        "city" => $ghlOrder['city'] ?? "Unknown City",
        "stateCode" => strtoupper(substr($ghlOrder['state'] ?? "NA", 0, 2)),
        "zip" => $ghlOrder['postal_code'] ?? "00000",
        "countryCode" => strtoupper($ghlOrder['country'] ?? "US")
    ];

    $externalIds = [
        [
            "type" => "plan",
            "value" => $ghlOrder['order']['metadata']['items']['data'][0]['plan']['nickname'] ?? "Unknown Plan"
        ],
        [
            "type" => "product",
            "value" => $ghlOrder['order']['metadata']['items']['data'][0]['plan']['product'] ?? "Unknown Product"
        ]
    ];

    $memberData = [
        "id" => $contactId,
        "enrollerId" => "defaultEnroller",
        "firstName" => $ghlOrder['first_name'] ?? "First",
        "lastName" => $ghlOrder['last_name'] ?? "Last",
        "fullName" => $ghlOrder['full_name'] ?? ($ghlOrder['first_name'] . " " . $ghlOrder['last_name']),
        "signupDate" => $ghlOrder['date_created'] ?? date(DATE_ISO8601),
        "status" => "6C0583A469",
        "emailAddress" => $email,
        "birthDate" => $birthDate,
        "language" => $language,
        "customData" => $customData,
        "webAlias" => $contactId,
        "profileImage" => null,
        "phoneNumbers" => [[
            "type" => "mobile",
            "number" => $phone,
            "countryCode" => null,
            "line2" => null,
            "line3" => null
        ]],
        "addresses" => [$address],
        "externalIds" => $externalIds,
        "customerType" => "Customer",
        "highestRank" => null,
        "sku" => null,
        "displayName" => $ghlOrder['full_name'] ?? "User",
        "travelPortalAccessType" => null,
        "scopeLevel" => "Self"
    ];

    try {
        $response = $pillars->create_member($memberData);
        echo "✅ Created member in Pillars:\n";
        print_r($response);
    } catch (Exception $e) {
        echo "❌ Error creating member: " . $e->getMessage();
    }
}


$member_data = [
    "id" => "6639",
    "enrollerId" => "3020",
    "firstName" => "Jane",
    "lastName" => "Doe",
    "fullName" => "Jane Doe",
    "signupDate" => "2024-07-01T00:00:00Z",
    "emailAddress" => "jane.doe@example.com",
    "webAlias" => "6639",
    "profileImage" => null,
    "phoneNumbers" => [
        [ "type" => "mobile", "number" => "5551234567" ]
    ],
    "addresses" => [
        [
            "type" => "primary",
            "line1" => "123 Main St",
            "city" => "Denver",
            "stateCode" => "CO",
            "zip" => "80203",
            "countryCode" => "US"
        ]
    ],
    "language" => "English",
    "customData" => "joined from ghl portal",
    "memberType" => "Affiliate",
    "highestRank" => "Executive",
    "sku" => "PASS-US-GHL-SUB",
    "displayName" => "JaneD",
    "travelPortalAccessType" => "Passport",
    "externalIds" => [
        "Account Type" => "Affiliate",
        "Rank" => "Executive",
        "SKU" => "PASS-US-GHL-SUB",
        "Portal Access Type" => "Passport"
    ],
    "birthDate" => "1990-01-01T00:00:00Z"
];

$pillars = pillars_api();
$response = $pillars->create_member($member_data);
print_r($response);
