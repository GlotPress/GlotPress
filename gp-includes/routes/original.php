<?php

class GP_Route_Original extends GP_Route_Main {

	public function set_priority( $original_id ) {
		$original = GP::$original->get( $original_id );

		if ( ! $original ) {
			return $this->die_with_404();
		}

		$project = GP::$project->get( $original->project_id );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		$this->can_or_forbidden( 'write', 'project', $project->id );
		$original->priority = gp_post( 'priority' );

		if ( ! $original->validate() ) {
			return $this->die_with_error( 'Invalid priority value!' );
		}

		if ( ! $original->save() ) {
			return $this->die_with_error( 'Error in saving original!' );
		}
	}

}
