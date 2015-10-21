<?php
gp_title( __('Profile &lt; GlotPress') );
gp_breadcrumb( array( __('Profile') ) );
gp_tmpl_header();

include_once( dirname( __FILE__ ) . '/profile-edit.php' );

gp_tmpl_footer();
