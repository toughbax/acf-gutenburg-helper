<?php
/**
 * Registers acf/gutenberg blocks by scanning a certain directory, then
 * reading the block's file headers for information.
 */

if ( !class_exists("RegisterBlocks") ) {

	class RegisterBlocks {

		static $block_directory;
		static $text_domain;

		function __construct() {

			/**
			 * Set Class static constants
			 */
			self::$block_directory 	= get_template_directory() . '/templates/blocks/';
			self::$text_domain 		= 'theme_text_domain';

			/**
			 * On acf/init run RegisterBlocks
			 */
			if ( function_exists('acf_register_block') ) {
				add_action( 'acf/init', array('RegisterBlocks','RegisterBlocks' ) );
			}

	    }
	    /**
	     * Scans the block directory for ACF / Gutenberg blocks for registration
	     *
	     * @param 	string $block_directory path to the block template files
	     * @return 	array $blocks a minimal array for gutenberg block registration
	     */
	    public static function BlockList( $block_directory ) {
		    
		    $dir = new DirectoryIterator( $block_directory );

	    	foreach ($dir as $block) {

				if ( !$block->isDot() ) {

					// Get the complete file path for the block
					$file_path = $block_directory . '/' . $block;

					// Map file headers into an array
					$file_headers = get_file_data( $file_path, array(
							'name' 			=> 'Name',
							'description' 	=> 'Description',
							'category' 		=> 'Category',
							'icon' 			=> 'Icon',
							'keywords' 		=> 'Keywords',
							'alignment' 	=> 'Alignment',
							'post_types'	=> 'Post Types',
						)
					);
					
					// Collate info into array
					$blockinfo = array(
			            'name' 			=> self::ConvertBlockTitleIntoBlockName( $file_headers['name'] ),
			            'description' 	=> $file_headers['description'] ? $file_headers['description'] : '',
			            'category' 		=> $file_headers['category'] ? $file_headers['category'] : 'layout',
			            'title' 		=> $file_headers['name'],
			            'file_name' 	=> $block->getFilename(),
			            'icon' 			=> $file_headers['icon'] ? $file_headers['icon'] : 'block-default',
			            'keywords' 		=> $file_headers['keywords'] ? self::FormatMultipleBlockOptions($file_headers['keywords']) : array(),
			            'alignment'		=> $file_headers['alignment'] ? $file_headers['alignment'] : '',
			            'post_types'	=> $file_headers['post_types'] ? self::FormatMultipleBlockOptions($file_headers['keywords']) : array(),
		        	);

					// push block info into array
					$blocks[] = $blockinfo;
		    		
		    	}

	    	}

			return $blocks;

	    }

	    /**
	     * Format the ACF block array for registration
	     * 
	     * @param  array $block minimal  array for block generation 
	     * @param  string $text_domain the theme text-domain
	     * @return array $blockArray a formatted Gutenberg registration array
	     */
		public static function GenerateBlockArray( $block, $text_domain ) {

			$blockArray = array(
				'render_template' 	=> 'templates/blocks/' . $block['file_name'],
				'title' 			=> __( $block['title'], $text_domain),
				'description'		=> __( $block['description'], $text_domain),
				'category' 			=> $block['category'],
				'name' 				=> $block['name'],
				'icon'				=> $block['icon'],
				'align'				=> $block['alignment'],
				'keywords'			=> $block['keywords'],
				'post_types'		=> $block['post_types'],

				/**
				 * TODO More functionality for "supports" in block file header
				 * @link https://www.advancedcustomfields.com/resources/acf_register_block/
				 */

				'supports' 			=> array( 'align' => array('full', 'wide') )
			);

			return $blockArray;

		}

		/**
		 * Convert a Block Name into a lowercase string without spaces for usage as the block's title
		 * 
		 * @param string $block_title the block's human readable title
		 */
		public static function ConvertBlockTitleIntoBlockName($block_title) {

			// Replaces all spaces with underscores
			$block_title = str_replace(' ', '_', $block_title);
			// Removes special chars
			return strtolower( preg_replace('/[^A-Za-z0-9\_]/', '', $block_title) );

		}
		/**
		 * Explodes a comma separated list into an array
		 * 
		 * @param string $options a comma separated list
		 * @return array $options exploded from user input string
		 */
		public static function FormatMultipleBlockOptions($options) {

			$options = str_replace(', ', ',' , $options);
			$options = explode(",", $options);			

			return $options;

		}

		/**
		 * Registers ACF blocks with given paramaters
		 * 
		 * @param string $block_directory the directory which contains all the block templates
		 */
		public static function RegisterBlocks() {
			
			$blocks = self::BlockList( self::$block_directory );

			foreach ($blocks as $block) {
				acf_register_block( self::GenerateBlockArray($block, self::$text_domain) );	
			}

		}

	    /**
	     * Main return for all formatted block info and fields
	     *
	     * @param obj $block the acf block object
	     * @return array $block_data array of blockinfo and field data
	     */
	    public static function BlockData($block) {

			$block_data = array(
				'block_info' => self::blockInfo($block),
				'parsed_fields' => self::parseFields($block),
			);
			
			return $block_data;
	    }

		/**
		 * Gets the ACF field and only returns if the field data is not empty
		 *
		 * @param  field  var   the ACF field name
		 * @return $field mixed the ACF field data
		 */
		public static function getField( $field ) {

			$field = get_field($field);

			if ( is_array($field) && array_filter($field) ) {
				return $field;
			} else if (!is_array($field) && !empty($field)) {
				return $field;
			}
		}

		/**
		 * Gets all the registered fields from the block
		 *
		 * @param $block   the ACF block
		 * @return $fields all registered field labels associated with the block
		 */
		public static function getRegisteredFields($block) {

			// Get all the registered fields for the block
			$data = $block['data'];
			$fields = array();
			
			foreach ($data as $fieldID => $field) {
				// get the field object by ID
				$fieldObj = get_field_object($fieldID);
				// add the field name to our fields array
				$fields[] = $fieldObj['name'];
			}

			return $fields;
		}

		/**
		 * Creates an Array of all the ACF field data
		 *
		 * @param  $fields 		array ACF field names
		 * @return $fieldsArray array ACF field data
		 */
		public static function parseFields( $block ) {

			$fields = self::getRegisteredFields($block);
			$fieldsArray = array();

			foreach  ( $fields as $field ) { 
				$fieldsArray[$field] = self::getField( $field );
			}

			return $fieldsArray;
		}

		/**
		 * Gets the Gutenberg "block info"
		 *
		 * @param  $block 		array Gutenberg block "object"
		 * @return $blockInfo	array contains block name, id, custom classes and alignment
		 */
		public static function blockInfo( $block ) {

			$block_name 	= str_replace( 'acf/', '', $block['name'] );
			$block_id 		= $block_name . '-' . $block['id'];
			$block_align 	= $block['align'] ? 'align' . $block['align'] : '';
			$block_classes  = $block['className'];

			$blockinfo = array(
				'block_name' 	=> $block_name,
				'block_id' 		=> $block_id,
				'block_align' 	=> $block_align,
				'block_classes' => $block_classes,
			);

			return $blockinfo;
		}

	}

	new RegisterBlocks;

}