<?php

$functions = array(
    'equella_list_courses_for_user' => array( 
        'classname'   => 'equella_external',  
        'methodname'  => 'list_courses_for_user',         
        'classpath'   => 'mod/equella/externallib.php', 
        'description' => 'List the courses for the given user.',    
        'type'        => 'read',                  
    ),
	'equella_list_sections_for_course' => array(
	        'classname'   => 'equella_external',  
	        'methodname'  => 'list_sections_for_course',         
	        'classpath'   => 'mod/equella/externallib.php', 
	        'description' => 'List the sections for the given course.',    
	        'type'        => 'read',                  
	),
	'equella_add_item_to_course' => array(
        'classname'   => 'equella_external',  
        'methodname'  => 'add_item_to_course',         
        'classpath'   => 'mod/equella/externallib.php', 
        'description' => 'Add an EQUELLA item to a given course by a given user.',    
        'type'        => 'write',                  
	),
	'equella_test_connection' => array(
		'classname'   => 'equella_external',
        'methodname'  => 'test_connection',         
        'classpath'   => 'mod/equella/externallib.php', 
        'description' => 'Tests the connection from EQUELLA to Moodle.  Returns success=>{param} if successful. (Where {param} is supplied when calling).',    
        'type'        => 'read',
	),
	'equella_find_usage_for_item' => array(
		'classname'   => 'equella_external',
        'methodname'  => 'find_usage_for_item',         
        'classpath'   => 'mod/equella/externallib.php', 
        'description' => 'List all the locations that the supplied item is used.',    
        'type'        => 'read',
	),
	'equella_find_all_usage' => array(
			'classname'   => 'equella_external',
	        'methodname'  => 'find_all_usage',         
	        'classpath'   => 'mod/equella/externallib.php', 
	        'description' => 'List all the locations that Equella content is used.',    
	        'type'        => 'read',
	),
'equella_unfiltered_usage_count' => array(
			'classname'   => 'equella_external',
	        'methodname'  => 'unfiltered_usage_count',         
	        'classpath'   => 'mod/equella/externallib.php', 
	        'description' => 'Get the number of results that would be returned by equella_find_all_usages without a course ID and folder ID value, and with an unlimited count',    
	        'type'        => 'read',
),
	'equella_get_course_code' => array(
		'classname'   => 'equella_external',
        'methodname'  => 'get_course_code',         
        'classpath'   => 'mod/equella/externallib.php', 
        'description' => 'Returns the course code for the supplied course id',    
        'type'        => 'read',
	),
	'equella_edit_item' => array(
        'classname'   => 'equella_external',  
        'methodname'  => 'edit_item',         
        'classpath'   => 'mod/equella/externallib.php', 
        'description' => 'Modify an item in moodle',    
        'type'        => 'write',                  
	),
	'equella_move_item' => array(
	        'classname'   => 'equella_external',  
	        'methodname'  => 'move_item',         
	        'classpath'   => 'mod/equella/externallib.php', 
	        'description' => 'Move an item in moodle',    
	        'type'        => 'write',                  
	),
	'equella_delete_item' => array(
        'classname'   => 'equella_external',  
        'methodname'  => 'delete_item',         
        'classpath'   => 'mod/equella/externallib.php', 
        'description' => 'Deletes an item in moodle',    
        'type'        => 'write',                  
	)
);

$services = array(
      'equellaservice' => array(                                                
            'functions' => array ('equella_list_courses_for_user', 'equella_list_sections_for_course', 
		        'equella_add_item_to_course', 'equella_test_connection', 
		         'equella_find_usage_for_item', 'equella_find_all_usage', 'equella_unfiltered_usage_count', 
		         'equella_get_course_code', 'equella_edit_item', 'equella_delete_item', 'equella_move_item'), 
     	 	'requiredcapability' => 'moodle/course:manageactivities',            
	        'restrictedusers' => 1,
          	'enabled' => 1,
       )
  );

?>
