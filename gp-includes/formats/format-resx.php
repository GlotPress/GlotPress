<?php
/**
 * GlotPress Format .NET .resx class
 *
 * @since 1.0.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support .NET .resx file format.
 *
 * @since 1.0.0
 */
class GP_Format_ResX extends GP_Format {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $name = '.NET Resource (.resx)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $extension = 'resx';

	/**
	 * Alternate file extensions of the file format, used to autodetect formats and when detecting file types for import.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $alt_extensions = array( 'resx.xml' );

	/**
	 * Which plural rules to use for this format.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public $plurals_format = 'gettext';

	/**
	 * Storage for the export file contents while it is being generated.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $exported = '';

	/**
	 * Generates a string the contains the $entries to export in the .resx file format.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Project         $project         The project the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Locale          $locale          The locale object the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Translation_Set $translation_set The locale object the strings are being
	 *                                            exported for. not used in this format but part
	 *                                            of the scaffold of the parent object.
	 * @param GP_Translation     $entries         The entries to export.
	 *
	 * @return string The exported .resx string.
	 */
	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$this->exported = '';
		$this->line( '<?xml version="1.0" encoding="utf-8"?>' );
		$this->line( '<root>' );

		$this->add_schema_info();
		$this->add_schema_declaration();

		$this->res_header( 'resmimetype', 'text/microsoft-resx' );
		$this->res_header( 'version', '2.0' );
		$this->res_header( 'reader', 'System.Resources.ResXResourceReader, System.Windows.Forms, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089' );
		$this->res_header( 'writer', 'System.Resources.ResXResourceReader, System.Windows.Forms, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089' );
		$this->res_header( 'translation_revision_date', GP::$translation->last_modified( $translation_set ) . '+0000' );
		$this->res_header( 'plural_forms', "nplurals={$locale->nplurals}; plural={$locale->plural_expression};" );
		$this->res_header( 'generator', 'GlotPress/' . GP_VERSION );

		$language_code = $this->get_language_code( $locale );
		if ( false !== $language_code ) {
			$this->res_header( 'language', $language_code );
		}

		foreach ( $entries as $entry ) {
			if ( empty( $entry->translations ) || ! array_filter( $entry->translations ) )
				continue;

			if ( empty( $entry->context ) ) {
				$entry->context = $entry->singular;
			}

			$this->line( '<data name="' . esc_attr( $entry->context ) . '" xml:space="preserve">', 1 );
			$this->line( '<value>' . $this->escape( $entry->translations[0] ) . '</value>', 2 );
			if ( isset( $entry->extracted_comments ) && $entry->extracted_comments ) {
				$this->line( '<comment>' . $this->escape( $entry->extracted_comments ) . '</comment>', 2 );
			}
			$this->line( '</data>', 1 );
		}
		$this->line( '</root>' );
		return $this->exported;
	}

	/**
	 * Reads a set of original strings from an .resx file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_name The name of the uploaded file.
	 *
	 * @return Translations|bool The extracted originals on success, false on failure.
	 */
	public function read_originals_from_file( $file_name ) {
		$errors = libxml_use_internal_errors( true );
		$data = simplexml_load_string( file_get_contents( $file_name ) );
		libxml_use_internal_errors( $errors );

		if ( ! is_object( $data ) ) {
			return false;
		}

		$entries = new Translations;
		foreach( $data->data as $string ) {
			$entry = new Translation_Entry();

			if ( isset( $string['type'] ) && gp_in( 'System.Resources.ResXFileRef', (string)$string['type'] ) ) {
				continue;
			}

			$entry->context = (string)$string['name'];
			$entry->singular = $this->unescape( (string)$string->value );

			if ( isset( $string->comment ) && $string->comment ) {
				$entry->extracted_comments = (string)$string->comment;
			}

			$entry->translations = array();
			$entries->add_entry( $entry );
		}

		return $entries;
	}

	/**
	 * Adds a number of tab characters to the beginning of a line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string       The string to indent.
	 * @param int    $prepend_tabs The number of tabs to add to the string.
	 *
	 * @return string The updated string.
	 */
	private function line( $string, $prepend_tabs = 0 ) {
		$this->exported .= str_repeat( "\t", $prepend_tabs ) . "$string\n";
	}

	/**
	 * Creates a resouce header block for the output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  The name of the resource.
	 * @param string $value The value of the resource.
	 *
	 * @return string The formatted resource block.
	 */
	private function res_header( $name, $value ) {
		$this->line( '<resheader name="'.$name.'">', 1 );
		$this->line( '<value>'.$value.'</value>', 2 );
		$this->line( '</resheader>', 1 );
	}

	/**
	 * Placeholder, .resx files do not need to be unescaped as they only replace some hard coded items with html entities.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string The string to unescape.
	 *
	 * @return string Returns the unescaped string.
	 */
	private function unescape( $string ) {
		return $string;
	}

	/**
	 * Escapes a string with html entities for some characters.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string The string to escape.
	 *
	 * @return string Returns the escaped string.
	 */
	private function escape( $string ) {
		$string = str_replace( array( '&', '<' ), array( '&amp;', '&lt;' ), $string );
		return $string;
	}

	/**
	 * Cteate the .resx schema info.  .resx file have a standard scheam block.
	 *
	 * @since 1.0.0
	 *
	 * @return string Returns the schema block.
	 */
	private function add_schema_info() {
		$this->line('<!--', 1 );
		$this->line('Microsoft ResX Schema', 2 );
		$this->line('', 0 );
		$this->line('Version 2.0', 2 );
		$this->line('', 0 );
		$this->line('The primary goals of this format is to allow a simple XML format', 2 );
		$this->line('that is mostly human readable. The generation and parsing of the', 2 );
		$this->line('various data types are done through the TypeConverter classes', 2 );
		$this->line('associated with the data types.', 2 );
		$this->line('', 0 );
		$this->line('Example:', 2 );
		$this->line('', 0 );
		$this->line('... ado.net/XML headers & schema ...', 2 );
		$this->line('<resheader name="resmimetype">text/microsoft-resx</resheader>', 2 );
		$this->line('<resheader name="version">2.0</resheader>', 2 );
		$this->line('<resheader name="reader">System.Resources.ResXResourceReader, System.Windows.Forms, ...</resheader>', 2 );
		$this->line('<resheader name="writer">System.Resources.ResXResourceWriter, System.Windows.Forms, ...</resheader>', 2 );
		$this->line('<data name="Name1"><value>this is my long string</value><comment>this is a comment</comment></data>', 2 );
		$this->line('<data name="Color1" type="System.Drawing.Color, System.Drawing">Blue</data>', 2 );
		$this->line('<data name="Bitmap1" mimetype="application/x-microsoft.net.object.binary.base64">', 2 );
		$this->line('<value>[base64 mime encoded serialized .NET Framework object]</value>', 3 );
		$this->line('</data>', 2 );
		$this->line('<data name="Icon1" type="System.Drawing.Icon, System.Drawing" mimetype="application/x-microsoft.net.object.bytearray.base64">', 2 );
		$this->line('<value>[base64 mime encoded string representing a byte array form of the .NET Framework object]</value>', 3 );
		$this->line('<comment>This is a comment</comment>', 3 );
		$this->line('</data>', 2 );
		$this->line('', 0 );
		$this->line('There are any number of "resheader" rows that contain simple', 2 );
		$this->line('name/value pairs.', 2 );
		$this->line('', 0 );
		$this->line('Each data row contains a name, and value. The row also contains a', 2 );
		$this->line('type or mimetype. Type corresponds to a .NET class that support', 2 );
		$this->line('text/value conversion through the TypeConverter architecture.', 2 );
		$this->line('Classes that don\'t support this are serialized and stored with the', 2 );
		$this->line('mimetype set.', 2 );
		$this->line('', 0 );
		$this->line('The mimetype is used for serialized objects, and tells the', 2 );
		$this->line('ResXResourceReader how to depersist the object. This is currently not', 2 );
		$this->line('extensible. For a given mimetype the value must be set accordingly:', 2 );
		$this->line('', 0 );
		$this->line('Note - application/x-microsoft.net.object.binary.base64 is the format', 2 );
		$this->line('that the ResXResourceWriter will generate, however the reader can', 2 );
		$this->line('read any of the formats listed below.', 2 );
		$this->line('', 0 );
		$this->line('mimetype: application/x-microsoft.net.object.binary.base64', 2 );
		$this->line('value   : The object must be serialized with', 2 );
		$this->line(': System.Runtime.Serialization.Formatters.Binary.BinaryFormatter', 4 );
		$this->line(': and then encoded with base64 encoding.', 4 );
		$this->line('', 0 );
		$this->line('mimetype: application/x-microsoft.net.object.soap.base64', 2 );
		$this->line('value   : The object must be serialized with', 2 );
		$this->line(': System.Runtime.Serialization.Formatters.Soap.SoapFormatter', 4 );
		$this->line(': and then encoded with base64 encoding.', 4 );
		$this->line('', 0 );
		$this->line('mimetype: application/x-microsoft.net.object.bytearray.base64', 2 );
		$this->line('value   : The object must be serialized into a byte array', 2 );
		$this->line(': using a System.ComponentModel.TypeConverter', 4 );
		$this->line(': and then encoded with base64 encoding.', 4 );
		$this->line('-->', 1 );
	}

	/**
	 * Cteate the .resx schema declaration.  .resx file have a standard scheam declaration.
	 *
	 * @since 1.0.0
	 *
	 * @return string Returns the schema declaration.
	 */
	private function add_schema_declaration() {
		$this->line( '<xsd:schema id="root" xmlns="" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">', 1 );
		$this->line( '<xsd:import namespace="http://www.w3.org/XML/1998/namespace" />', 2 );
		$this->line( '<xsd:element name="root" msdata:IsDataSet="true">', 2 );
		$this->line( '<xsd:complexType>', 3 );
		$this->line( '<xsd:choice maxOccurs="unbounded">', 4 );

		$this->line( '<xsd:element name="metadata">', 5 );
		$this->line( '<xsd:complexType>', 6 );
		$this->line( '<xsd:sequence>', 7 );
		$this->line( '<xsd:element name="value" type="xsd:string" minOccurs="0" />', 8 );
		$this->line( '</xsd:sequence>', 7 );
		$this->line( '<xsd:attribute name="name" use="required" type="xsd:string" />', 7 );
		$this->line( '<xsd:attribute name="type" type="xsd:string" />', 7 );
		$this->line( '<xsd:attribute name="mimetype" type="xsd:string" />', 7 );
		$this->line( '<xsd:attribute ref="xml:space" />', 7 );
		$this->line( '</xsd:complexType>', 6 );
		$this->line( '</xsd:element>', 5 );

		$this->line( '<xsd:element name="assembly">', 5 );
		$this->line( '<xsd:complexType>', 6 );
		$this->line( '<xsd:attribute name="alias" type="xsd:string" />', 7 );
		$this->line( '<xsd:attribute name="name" type="xsd:string" />', 7 );
		$this->line( '</xsd:complexType>', 6 );
		$this->line( '</xsd:element>', 5 );

		$this->line( '<xsd:element name="data">', 5 );
		$this->line( '<xsd:complexType>', 6 );
		$this->line( '<xsd:sequence>', 7 );
		$this->line( '<xsd:element name="value" type="xsd:string" minOccurs="0" msdata:Ordinal="1" />', 8 );
		$this->line( '<xsd:element name="comment" type="xsd:string" minOccurs="0" msdata:Ordinal="2" />', 8 );
		$this->line( '</xsd:sequence>', 7 );
		$this->line( '<xsd:attribute name="name" type="xsd:string" use="required" msdata:Ordinal="1" />', 7 );
		$this->line( '<xsd:attribute name="type" type="xsd:string" msdata:Ordinal="3" />', 7 );
		$this->line( '<xsd:attribute name="mimetype" type="xsd:string" msdata:Ordinal="4" />', 7 );
		$this->line( '<xsd:attribute ref="xml:space" />', 7 );
		$this->line( '</xsd:complexType>', 6 );
		$this->line( '</xsd:element>', 5 );

		$this->line( '<xsd:element name="resheader">', 5 );
		$this->line( '<xsd:complexType>', 6 );
		$this->line( '<xsd:sequence>', 7 );
		$this->line( '<xsd:element name="value" type="xsd:string" minOccurs="0" msdata:Ordinal="1" />', 8 );
		$this->line( '</xsd:sequence>', 7 );
		$this->line( '<xsd:attribute name="name" type="xsd:string" use="required" />', 7 );
		$this->line( '</xsd:complexType>', 6 );
		$this->line( '</xsd:element>', 5 );

		$this->line( '</xsd:choice>', 4 );
		$this->line( '</xsd:complexType>', 3 );
		$this->line( '</xsd:element>', 2 );
		$this->line( '</xsd:schema>', 1 );
	}

}

GP::$formats['resx'] = new GP_Format_ResX;
