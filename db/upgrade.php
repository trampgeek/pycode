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
    
    if ($oldversion < 2012062901) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('question_pycode_testcases');
        $displayField = new xmldb_field('display', XMLDB_TYPE_CHAR, 40, null, TRUE, null, 'SHOW');
        $dbman->add_field($table, $displayField);
        $hideRestIfFail = new xmldb_field('hiderestiffail', XMLDB_TYPE_INTEGER, 1, TRUE, TRUE, null, 0);
        $dbman->add_field($table, $hideRestIfFail);
        $DB->set_field_select('question_pycode_testcases', 'display','HIDE', 'hidden');
        $hiddenField = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, null, null, null, null, null);
        $dbman->drop_field($table, $hiddenField);
        upgrade_plugin_savepoint(true, 2012062901, 'qtype', 'pycode');
    }
    
    
    return $result;
}
?>