<?php

function google_translate_batch( $locale, $strings ) {
	if ( !$locale->google_code ) {
		return new WP_Error( 'google_translate', sprintf( "The locale %s isn't supported by Google Translate.", $locale->slug ) );
	}
	$url = 'http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&langpair=' . urlencode( 'en|'.$locale->google_code );
	foreach ( $strings as $string ) {
		$url .= '&q=' . urlencode( $string );
	}
	if ( count( $strings ) == 1 ) $url .= '&q=';
	$response = wp_remote_get( $url );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$json = json_decode( wp_remote_retrieve_body( $response ) );
	if ( !$json ) {
		return new WP_Error( 'google_translate', 'Error decoding JSON from Google Translate.' );
	}
	if ( $json->responseStatus != 200 ) {
		return new WP_Error( 'google_translate', sprintf( 'Error auto-translating: %1$s (%2$s)', $json->responseDetails, $json->responseStatus ) );
	}
	$translations = array();
	if ( !is_array( $json->responseData ) ) $json->responseData = array( $json->responseData );
	foreach( gp_array_zip( $strings, $json->responseData ) as $item ) {
		list( $string, $translation ) = $item;
		if ( $translation->responseStatus == 200 ) {
			$translations[] = google_translate_fix( $translation->responseData->translatedText );
		} else {
			$max_len = 20;
			$excerpt = strlen( $string ) > $max_len? substr( $string, 0, $max_len - 3 ).'...' : $string;
			$message = sprintf( 'Error auto-translating string "$1$s": %2$s (%3$s)', $excerpt, $translation->responseDetails, $translation->responseStatus );
			$translations[] = new WP_Error( 'google_translate', $message );
		}
	}
	return $translations;
}

function google_translate_fix( $string ) {
	$string = preg_replace_callback( '/% (s|d)/i', lambda( '$m', '"%".strtolower($m[1])' ), $string );
	$string = preg_replace_callback( '/% (\d+) \$ (s|d)/i', lambda( '$m', '"%".$m[1]."\\$".strtolower($m[2])' ), $string );
	return $string;
}