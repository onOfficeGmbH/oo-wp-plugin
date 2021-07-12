<?php $dontEcho = array("objekttitel", "objektbeschreibung", "lage", "ausstatt_beschr", "sonstige_angaben"); ?>

<div class="oo-detailsheadline">
	<h2><?php esc_html_e('More Estates like this', 'onoffice');?></h2>
</div>
<div class="oo-listframe" id="oo-similarframe">
	<?php
	while ( $currentEstate = $pEstates->estateIterator() ) {
	$marketingStatus = $currentEstate['vermarktungsstatus'];
	unset($currentEstate['vermarktungsstatus']); ?>
		<div class="oo-listobject">
			<div class="oo-listobjectwrap">
				<?php
				$estatePictures = $pEstates->getEstatePictures();
				foreach ( $estatePictures as $id ) {
					$pictureValues = $pEstates->getEstatePictureValues( $id );
					echo '<a href="'.$pEstates->getEstateLink().'" style="background-image: url('.esc_url($pEstates->getEstatePictureUrl( $id )).');" class="oo-listimage">';
					if ($pictureValues['type'] === \onOffice\WPlugin\Types\ImageTypes::TITLE && $marketingStatus != '') {
						echo '<span>'.esc_html($marketingStatus).'</span>';
					}
					echo '</a>';
				} ?>
				<div class="oo-listinfo">
					<div class="oo-listtitle">
						<?php echo $currentEstate["objekttitel"]; ?>
					</div>
					<div class="oo-listinfotable">
						<?php foreach ( $currentEstate as $field => $value ) {
							if ( is_numeric( $value ) && 0 == $value ) {
								continue;
							}
							if ( in_array($field, $dontEcho) ) {
								continue;
							}
							if ( $value == "" || empty($value) ) {
								continue;
							}
							echo '<div class="oo-listtd">'.esc_html($pEstates->getFieldLabel( $field )) .'</div><div class="oo-listtd">'.(is_array($value) ? esc_html(implode(', ', $value)) : esc_html($value)).'</div>';
						} ?>
					</div>
					<div class="oo-detailslink">
						<a href="<?php echo $pEstates->getEstateLink(); ?>">
							<?php esc_html_e('Show Details', 'onoffice'); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
</div>
<div>
	<?php
	if (get_option('onoffice-pagination-paginationbyonoffice')) {
		wp_link_pages();
	}
	?>
</div>