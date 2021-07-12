<?php

/**
 *
 *    Copyright (C) 2016  onOffice Software AG
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 *  Default template
 */

/* @var $pEstates onOffice\WPlugin\EstateList */

?>
<h2><?php esc_html_e('Entities', 'onoffice');?></h2>
<?php while ( $currentEstate = $pEstates->estateIterator() ) : ?>
	<?php foreach ( $currentEstate as $field => $value ) :
		if ( is_numeric( $value ) && 0 == $value ) {
			continue;
		}
	?>
		<?php echo $pEstates->getFieldLabel( $field ) .': '.(is_array($value) ? esc_html(implode(', ', $value)) : esc_html($value)); ?><br>

	<?php endforeach; ?>
	<h3><?php esc_html_e('Contact person of entity:', 'onoffice');?></h3>
	<?php foreach ( $pEstates->getEstateContacts() as $contactData ) : ?>
	<ul>
		<b><?php echo $contactData['Vorname']; ?> <?php echo esc_html($contactData['Name']); ?></b>
		<li><?php esc_html_e('Phone:', 'onoffice');?><?php echo esc_html($contactData['defaultphone']); ?></li>
		<li><?php esc_html_e('Fax:', 'onoffice');?> <?php echo esc_html($contactData['defaultfax']); ?></li>
		<li>
		<?php esc_html_e('E-Mail:', 'onoffice');?> <?php echo esc_html($contactData['defaultemail']); ?></li>
	</ul>
<?php endforeach; ?>
<br>

<?php endwhile; ?>
<br>
<div>
	<?php
	if (get_option('onoffice-pagination-paginationbyonoffice')) {
		wp_link_pages();
	}
	?>
</div>