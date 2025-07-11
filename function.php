add_action('rest_api_init', function() {
    register_rest_route('custom/v1', '/formidable-nested-json/', [
        'methods'  => 'GET',
        'callback' => 'custom_formidable_nested_json_output',
    ]);
});

function custom_formidable_nested_json_output() {
    global $wpdb;

    $form_id = 3; // ✅ Main Form ID

    // Load Main Form Fields
    $fields_raw = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name FROM {$wpdb->prefix}frm_fields WHERE form_id = %d",
            $form_id
        )
    );

    $field_map = [];
    foreach ($fields_raw as $f) {
        $field_map[$f->id] = $f->name;
    }

    // ✅ Define Repeater Field IDs with Labels
    $custom_repeater_fields = [
        120 => 'Add Another Vehicle',
        181 => 'Another Driver'
    ];

    // ✅ Static Child Field ID Mappings for Repeaters
    $static_child_field_maps = [
        120 => [ // Add Another Vehicle
            124 => 'Year',
            221 => 'Vehicle Make',
            128 => 'Vehicle Model',
            130 => 'Vehicle Ownership'
        ],
        181 => [ // Another Driver
            184 => 'Gender',
            186 => 'Marital Status',
            187 => 'Birth Month',
            189 => 'Birth Day',
            191 => 'Birth Year',
            193 => 'Had an Accident',
            195 => 'Received a Ticket',
            222 => 'Received a DUI',
            199 => 'Relationship to You',
            197 => 'Legal First Name',
            200 => 'Legal Last Name'
        ]
    ];

    // Fetch Parent Entries
    $entries = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id = %d",
            $form_id
        )
    );

    $results = [];
    foreach ($entries as $entry) {
        $results[] = [
            'Entry ID' => $entry->id,
            'Fields'   => get_formidable_entry_data($entry->id, $field_map, $custom_repeater_fields, $static_child_field_maps, $wpdb),
        ];
    }

    return rest_ensure_response($results);
}

function get_formidable_entry_data($entry_id, $field_map, $custom_repeater_fields, $static_child_field_maps, $wpdb) {
    $meta = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT field_id, meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id = %d",
            $entry_id
        )
    );

    $fields = [];

    foreach ($meta as $m) {
        $field_id = $m->field_id;
        $value = maybe_unserialize($m->meta_value);

        // ✅ Handle Repeater Fields
        if (array_key_exists($field_id, $custom_repeater_fields) && is_array($value)) {
            $children = [];
            foreach ($value as $child_id) {
                $children[] = get_formidable_entry_data($child_id, $field_map, $custom_repeater_fields, $static_child_field_maps, $wpdb);
            }
            $fields[$custom_repeater_fields[$field_id]] = $children;
        }
        // ✅ Handle Static Child Field Mappings
        elseif (isset($static_child_field_maps)) {
            foreach ($static_child_field_maps as $repeater_id => $child_map) {
                if (array_key_exists($field_id, $child_map)) {
                    $label = $child_map[$field_id];
                    $fields[$label] = $value;
                    continue 2;
                }
            }
            // Default label if not matched
            $label = isset($field_map[$field_id]) ? $field_map[$field_id] : "Field {$field_id}";
            $fields[$label] = $value;
        }
        // ✅ Default Label Mapping
        else {
            $label = isset($field_map[$field_id]) ? $field_map[$field_id] : "Field {$field_id}";
            $fields[$label] = $value;
        }
    }

    return $fields;
}
