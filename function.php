add_action('rest_api_init', function() {
    register_rest_route('custom/v1', '/formidable-multistep-clean/', [
        'methods'  => 'GET',
        'callback' => 'my_custom_formidable_multistep_entries_clean',
    ]);
});

function my_custom_formidable_multistep_entries_clean() {
    global $wpdb;

    $form_id = 3;

    $fields_raw = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name, type FROM {$wpdb->prefix}frm_fields WHERE form_id = %d",
            $form_id
        )
    );
    $field_map = [];
    $repeater_fields = [];
    foreach ($fields_raw as $f) {
        $field_map[$f->id] = $f->name;
        if ($f->type === 'divider' || $f->type === 'end_divider') {
            continue;
        }
        if ($f->type === 'repeater') {
            $repeater_fields[$f->id] = true;
        }
    }

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
            'Fields'   => my_process_entry($entry->id, $field_map, $repeater_fields, $wpdb),
        ];
    }

    return rest_ensure_response($results);
}

function my_process_entry($entry_id, $field_map, $repeater_fields, $wpdb) {
    $meta = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT field_id, meta_value FROM {$wpdb->prefix}frm_item_metas WHERE item_id = %d",
            $entry_id
        )
    );

    $fields = [];

    foreach ($meta as $m) {
        $field_id = $m->field_id;
        $label = isset($field_map[$field_id]) ? $field_map[$field_id] : "Field {$field_id}";
        $value = maybe_unserialize($m->meta_value);

        // Check if this is a repeater field
        if (isset($repeater_fields[$field_id]) && is_array($value)) {
            $children = [];
            foreach ($value as $child_id) {
                $children[] = my_process_entry($child_id, $field_map, $repeater_fields, $wpdb);
            }
            $fields[$label] = $children;
        } else {
            $fields[$label] = $value;
        }
    }

    return $fields;
}
