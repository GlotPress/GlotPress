<?php
/**
 * Base capabilities class
 */
class GP_Caps {

	private $object_types = array( 'a' => 'admin', 'p' => 'project', 't' => 'translation-set', 'g' => 'glossary' );
	private $actions = array( 'edit', 'view', 'write', 'delete' );
	private $prefix = 'gp_perm_';

	function __construct() {
		$this->object_types = apply_filters( 'gp_caps_object_types', $this->object_types );
		$this->actions = apply_filters( 'gp_caps_actions', $this->actions );
		$this->prefix = apply_filters( 'gp_caps_prefix', $this->prefix );
	}

	public function add_cap( $action = 'read', $object_type = null, $object = null ) {
		return $this->add_cap_user( null, $action, $object_type, $object );
	}
	
	public function add_cap_user( $user = null, $action = 'read', $object_type = 'admin', $object = null ) {
		$user = $this->get_user( $user );
		
		if( false === $user ) {
			return false;
		}
		echo $action;
		$cap_name = $this->get_cap_name( $action, $object_type, $object );
		
		if( $this->can( $user, 'admin' ) ) {
			$user->add_cap( $cap_name );
			return true;
		}
		
		return false;
	}
	
	public function delete_cap( $action = 'read', $object_type = null, $object = null ) {
		return $this->delete_cap_user( null, $action, $object_type, $object );
	}
	
	public function delete_cap_user( $user = null, $action = 'read', $object_type = 'admin', $object = null ) {
		$user = $this->get_user( $user );
		
		if( false === $user ) {
			return false;
		}
		
		$cap_name = $this->get_cap_name( $action, $object_type, $object );
		$admin_cap_name = $this->get_ap_name( $action, 'admin' );
		
		if( false !== $admin_cap_name && user_can( $user, $admin_cap_name ) ) {
			return $user->remove_cap( $cap_name );
		}
		
		return false;
	}
	
	public function can( $action = 'read', $object_type = 'admin', $object = null ) {
		return $this->can_user( null, $action, $object_type, $object );
	}
	
	public function can_user( $user = null, $action = 'read', $object_type = 'admin', $object = null ) {
		$user = $this->get_user( $user );
		
		if( false === $user ) {
			return false;
		}
		
		$cap_name = $this->get_cap_name( $action, $object_type, $object );
		$admin_cap_name = $this->get_cap_name( $action, 'admin' );

		if( false !== $cap_name && user_can( $user, $cap_name ) ) {
			return true;
		}
		
		if( false !== $admin_cap_name && user_can( $user, $admin_cap_name ) ) {
			return true;
		}

		if( user_can( $user, 'manage_options' ) ) {
			return true;
		}
		
		return false;
	}
	
	public function get_user_list( $action = 'read', $object_type, $object = null ) {
		GLOBAL $wpdb;

		$users = array();
		
		$cap_name = $this->get_cap_name( $action, $object_type, $object );
		
		if( false !== $cap_name ) {
			$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM {$wpdb->usermeta} WHERE meta_key = wp_capabilities AND meta_value LIKE %s', '%' . $cap_name . '%' ) );
			
			foreach( $results as $usermeta ) {
				if( is_array( $usermeta->meta_value ) ) {
					if( in_array( $cap_name, $usermeta->meta_value ) ) {
						$users[$usermeta->user_id] = $this->get_user( $usermeta->user_id );
					}					
				}
			}
		}
		
		return $users;
	}
	
	private function get_cap_name( $action, $object_type, $object = null ) {
		
		switch( $object_type ) {
			case 'a':
			case 'admin':
				$cap_name = $this->prefix . 'admin';
				
				break;
			default:
				$cap_name = $this->prefix;

				if( array_key_exists( $object_type, $this->object_types ) ) {
					$cap_name .= $object_type;
				} else {
					$key = array_search( $object_type, $this->object_types );
					
					if( $key !== false ) {
						$cap_name .= $key;
					} else {
						$cap_name = false;
					}
				}
				
				if( false !== $cap_name  ) {
					if( is_object( $object ) > 0 ) {
						$cap_name .= '_' . $object->id;
					} else {
						$cap_name .= '_' . $object;
					}
					
					if( '' != $action ) {
						if( in_array( $action, $this->actions ) ) {
							$cap_name .= '_' . $action;
						}
					}
				}
		}
		
		return apply_filters( 'gp_cap_name', $cap_name, $action, $object_type, $object );
	}
	
	private function get_user( $user = null ) {
		if( null == $user ) {
			$user_obj = wp_get_current_user();
		}
		
		if( is_int( $user ) ) {
			$user_obj = get_user_by( 'id', $user );
		}
		
		if( !is_object( $user_obj ) || ( is_object( $user_object ) && !( $user_obj instanceof WP_User ) ) ) {
			return false;
		}
		
		return $user_obj;
	}
}
GP::$caps = new GP_Caps();