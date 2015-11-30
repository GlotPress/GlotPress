<?php
$meta = array(
	'meta' => array(
		'user_display_name' => $user->display_name,
		'user_registered'   => $user->user_registered,
	)
);

foreach ( $recent_projects as $project ) {
	$project->set_name = html_entity_decode( $project->set_name );
	unset($project->project_id);
	unset($project->set_id);
}

$arr = array_merge( $meta,
	compact( 'locales', 'recent_projects' )
);

switch ( gp_get('filter') ) {
	case 'meta':
		echo wp_json_encode( $meta );
		break;
	case 'recent_projects':
		echo wp_json_encode( compact('recent_projects') );
		break;
	default:
		echo wp_json_encode( $arr );
		break;
}
