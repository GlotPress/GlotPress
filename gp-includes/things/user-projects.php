<?php
class GP_User_Projects extends GP_Thing {

	protected $table_basename = 'user_projects';
	protected $field_names = array( 'user_id', 'project_id', 'type' );


	public function __construct( $fields = array() ) {
		parent::__construct( $fields );
	}

}
GP::$user_projects = new GP_User_Projects();
