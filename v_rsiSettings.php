// RSI default settings
$rsi_settings = [
    'integration_name' => 'rsi',
    'settings_json' => json_encode([
        'secret_key' => 'placeholder',
        'base_url' => 'https://middleware.accessrsi.com/api/members/createupdate/',
        'sso_urls' => [
            'Passport Lite' => 'https://passportlite.thedash.life/index.php',
            'Passport (Dashlife)' => 'https://sso.thedash.life/index.php',
            'Passport Travel Agent' => 'https://travelagent.thedash.life/index.php'
        ]
    ]),
    'is_active' => false
];

db_insert('integration_settings', $rsi_settings);
   


//maybe add this 
db_insert('integration_settings', $pillars_settings);

// Add this right after
db_insert('integration_settings', $rsi_settings);
