<?php
/**
 * Name: 		Example Block
 * Icon: 		block-default
 * Alignment: 	full
 * Description: An example block.
 * Category: 	layout
 * Keywords:    test, another keyword
 */

// include these next three lines in a separate file: include('path/to/block/fields'), or include a function which returns the path: include( pathToBlockFields() );
$block_data = RegisterBlocks::BlockData($block);
$block_info = $block_data['block_info'];
extract( $block_data['parsed_fields'] );
?>
<section class="block <?php echo $block_info['block_name'];?> <?php echo $block_info['block_classes'];?>" id="<?php echo $block_info['block_id'];?>" >
	<div class="container">
		<div class="row">
			<?php echo $title; 			// mapped from ACF field handle "title" ?>
			<?php echo $description; 	// mapped from ACF field handle "description" ?>
		</div>
	</div>
</section>
