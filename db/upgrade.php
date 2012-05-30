<?php

function xmldb_qtype_pycode_upgrade($oldversion) {
    global $CFG, $DB;
 
    $result = TRUE;
    $dbman = $DB->get_manager();
   
    // Rename shellinput to testcode for this new version
    if ($oldversion < 2012052501) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('question_pycode_testcases');
        $field = new xmldb_field('shellinput', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $dbman->rename_field($table, $field, 'testcode');
        upgrade_plugin_savepoint(true, 2012052501, 'qtype', 'pycode');
    }
    
    return $result;
}
?>