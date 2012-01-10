<?php
 
define('MODIFY_ONE_LINE_QUESTIONS', FALSE);

function xmldb_qtype_pycode_upgrade($oldversion) {
    global $CFG, $DB;
 
    $result = TRUE;
    $dbman = $DB->get_manager();
 
    if ($oldversion < 2010121022) {

        // Define table question_pycode_testcases to be created
        $table = new xmldb_table('question_pycode_testcases');

        // Adding fields to table question_pycode_testcases
        $table->add_field('id', XMLDB_TYPE_INTEGER, null, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('expression', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('result', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table question_pycode_testcases
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'questions', array('id'));

        // Conditionally launch create table for question_pycode_testcases
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // pycode savepoint reached
        upgrade_plugin_savepoint(true, 2010121022, 'qtype', 'pycode');
    }
    
    if ($oldversion < 2010121023) {

        // Define field useasexample to be added to question_pycode_testcases
        $table = new xmldb_table('question_pycode_testcases');
        $field = new xmldb_field('useasexample', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, '0', 'result');

        // Conditionally launch add field useasexample
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // pycode savepoint reached
        upgrade_plugin_savepoint(true, 2010121023, 'qtype', 'pycode');
    }
    
    if ($oldversion < 2010121024) {

        // Define field hidden to be added to question_pycode_testcases
        $table = new xmldb_table('question_pycode_testcases');
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null, '0', 'result');

        // Conditionally launch add field useasexample
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // pycode savepoint reached
        upgrade_plugin_savepoint(true, 2010121024, 'qtype', 'pycode');
    }
    
    if ($oldversion < 2011121545) {
        
        $table = new xmldb_table('question_pycode_testcases');
        
        // Rename expression to shellinput
        $field = new xmldb_field('expression', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'shellinput');
        }
 
        // Rename result to output
        $field = new xmldb_field('result', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'output');
        }

        // Add field stdin
        $field = new xmldb_field('stdin', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // pycode savepoint reached
        upgrade_plugin_savepoint(true, 2011121545, 'qtype', 'pycode');
    }
    
    // Version of 19 December 2011 modifies all existing questions to use 
    // 'print f(x)' type tests rather than the shell-input version with just f(x)
    
    if ($oldversion < 2011121914 && MODIFY_ONE_LINE_QUESTIONS) {
        $rs = $DB->get_recordset_sql('
                SELECT * from {question_pycode_testcases}');
        foreach ($rs as $record) {
            $testInput = trim($record->shellinput);
            if ($testInput &&
                strstr($testInput, "\n") === FALSE &&
                strstr($testInput, 'print ') !== 0 ) {
                // Single line input not starting with print: a candidate for update
                    $record->shellinput = "print " . $testInput;
                    $matches = array();
                    if (preg_match("|'(.*)'|", $record->output, $matches)) {
                        $record->output = $matches[1];
                    }
                    $DB->update_record('question_pycode_testcases', $record);
            }
        }
        $rs->close();
        // pycode savepoint reached
        upgrade_plugin_savepoint(true, 2011121914, 'qtype', 'pycode');
    }
    
 
    return $result;
}
?>