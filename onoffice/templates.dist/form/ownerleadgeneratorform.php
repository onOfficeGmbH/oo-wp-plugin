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

use onOffice\WPlugin\Form;
use onOffice\WPlugin\FormPost;
use onOffice\WPlugin\Types\FieldTypes;

add_thickbox();

$addressValues = array();
$estateValues = array();
$miscValues = array();

if ($pForm->getFormStatus() === FormPost::MESSAGE_SUCCESS) {
	esc_html_e('The form was sent successfully.', 'onoffice');
	echo '<br>';
} else {
	if ($pForm->getFormStatus() === FormPost::MESSAGE_ERROR) {
		esc_html_e('There was an error sending the form.', 'onoffice');
		echo '<br>';
	} elseif ($pForm->getFormStatus() === FormPost::MESSAGE_RECAPTCHA_SPAM) {
		esc_html_e('The form wasn\'t sent because spam was detected.', 'onoffice');
		echo '<br>';
	}

	/* @var $pForm Form */
	foreach ( $pForm->getInputFields() as $input => $table ) {
		if ( $pForm->isMissingField( $input )  &&
			$pForm->getFormStatus() == FormPost::MESSAGE_REQUIRED_FIELDS_MISSING) {
			echo sprintf(__('Please enter a value for %s.', 'onoffice'), $pForm->getFieldLabel( $input )).'<br>';
		}

		$line = '';

		$selectTypes = array(
			FieldTypes::FIELD_TYPE_MULTISELECT,
			FieldTypes::FIELD_TYPE_SINGLESELECT,
		);

		$typeCurrentInput = $pForm->getFieldType( $input );
		$isRequired = $pForm->isRequiredField( $input );
		$addition = $isRequired ? '*' : '';
		$requiredAttribute = $isRequired ? ' required' : '';

		if ( in_array( $typeCurrentInput, $selectTypes, true ) ) {
			$line = $pForm->getFieldLabel( $input ).$addition.': ';

			$permittedValues = $pForm->getPermittedValues( $input, true );
			$selectedValue = $pForm->getFieldValue( $input, true );
			$line .= '<select size="1" name="'.esc_html($input).'"'.$requiredAttribute.'>';

			foreach ( $permittedValues as $key => $value ) {
				if ( is_array( $selectedValue ) ) {
					$isSelected = in_array( $key, $selectedValue, true );
				} else {
					$isSelected = $selectedValue == $key;
				}
				$line .= '<option value="'.esc_html($key).'"'.($isSelected ? ' selected' : '').'>'
					.esc_html($value).'</option>';
			}
			$line .= '</select>';
		} else {
			$inputType = 'text';
			$value = 'value="'.$pForm->getFieldValue( $input ).'"';

			if ($typeCurrentInput == FieldTypes::FIELD_TYPE_BOOLEAN) {
				$inputType = 'checkbox';
				$value = $pForm->getFieldValue( $input, true ) == 1 ? 'checked="checked"' : '';
				$value .= ' value="y"';
			}

			$line .= $pForm->getFieldLabel( $input ).$addition.': ';
			$line .= '<input type="'.$inputType.'" name="'.esc_attr($input).'" '.$value.$requiredAttribute.'><br>';
		}

		if ($table == 'address') {
			$addressValues []= $line;
		} elseif ($table == 'estate') {
			$estateValues []= $line;
		} else {
			$miscValues []= $line;
		}
	}
}
?>

<script>
	$(document).ready(function() {
		var oOPaging = new onOffice.paging('leadform');
		oOPaging.setFormId('leadgeneratorform');
		oOPaging.setup();
	});
</script>

<div id="onoffice-lead" style="display:none;">
	<p>
		<form name="leadgenerator" action="" method="post" id="leadgeneratorform">
			<input type="hidden" name="oo_formid" value="<?php echo $pForm->getFormId(); ?>">
			<input type="hidden" name="oo_formno" value="<?php echo $pForm->getFormNo(); ?>">
			<div id="leadform">
				<?php
					if ($pForm->getFormStatus() === FormPost::MESSAGE_ERROR) {
						echo 'ERROR!';
					}
				?>

				<div class="lead-lightbox lead-page-1">
					<h2><?php echo esc_html__('Your contact details', 'onoffice'); ?></h2>
					<p>
						<?php echo implode('<br>', $addressValues); ?>
					</p>
				</div>

				<div class="lead-lightbox lead-page-2">
					<h2><?php echo esc_html__('Information about your property', 'onoffice'); ?></h2>
					<p>
						<?php echo implode('<br>', $estateValues); ?>
					</p>
					<p>
						<?php echo implode('<br>', $miscValues); ?>
					</p>
					<p>
						<div style="float:right">
							<?php
							$pForm->setGenericSetting('formId', 'leadgeneratorform');
							include(ONOFFICE_PLUGIN_DIR.'/templates.dist/form/formsubmit.php');
							?>
						</div>
					</p>
				</div>

				<span class="leadform-back" style="float:left; cursor:pointer;">
					<?php echo esc_html__('Back', 'onoffice'); ?>
				</span>
				<span class="leadform-forward" style="float:right; cursor:pointer;">
					<?php echo esc_html__('Next', 'onoffice'); ?>
				</span>
			</div>
		</form>
	</p>
</div>

<?php

if (in_array($pForm->getFormStatus(), [
		null,
		FormPost::MESSAGE_ERROR,
		FormPost::MESSAGE_REQUIRED_FIELDS_MISSING,
	])) {
	echo '<a href="#TB_inline?width=700&height=650&inlineId=onoffice-lead" class="thickbox">';
	echo esc_html__('Open the Form', 'onoffice');
	echo '</a>';
}