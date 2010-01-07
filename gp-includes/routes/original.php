<?php

class GP_Route_Original extends GP_Route_Main {
    
    function set_priority( $original_id ) {
        $original = GP::$original->get( $original_id );
        if ( !$original ) gp_tmpl_404();
        $project = GP::$project->get( $original->project_id );
        if ( !$project ) gp_tmpl_404();
        $this->can_or_forbidden( 'write', 'project', $project->id );
        $original->priority = gp_post( 'priority' );
        if ( !$original->validate() ) {
            $this->die_with_error( 'Invalid priority value!' );
        }
        if ( !$original->save() ) {
            $this->die_with_error( 'Error in saving original!' );
        }
    }
}