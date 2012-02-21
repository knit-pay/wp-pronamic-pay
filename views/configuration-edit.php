<?php 

$update = null;
$error = null;

// Configuration
if(empty($_POST)) {
	$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
} else {
	$id = filter_input(INPUT_POST, 'pronamic_ideal_configuration_id', FILTER_SANITIZE_STRING);
}

$configuration = Pronamic_WordPress_IDeal_ConfigurationsRepository::getConfigurationById($id);
if($configuration == null) {
	$configuration = new Pronamic_WordPress_IDeal_Configuration();
}

// Generator
if(empty($configuration->numberDaysValid)) {
	$configuration->numberDaysValid = 365;
}

if(empty($configuration->country)) {
	$language = get_option('WPLANG', WPLANG);

	$configuration->countryName = substr($language, 3);
}

if(empty($configuration->organization)) {
	$configuration->organization = get_bloginfo('name');
}

if(empty($configuration->eMailAddress)) {
	$configuration->eMailAddress = get_bloginfo('admin_email');
}

// Request
if(!empty($_POST) && check_admin_referer('pronamic_ideal_save_configuration', 'pronamic_ideal_nonce')) {
	$variantId = filter_input(INPUT_POST, 'pronamic_ideal_variant_id', FILTER_SANITIZE_STRING);
	$variant = Pronamic_WordPress_IDeal_ConfigurationsRepository::getVariantById($variantId);
	
	$configuration->setVariant($variant);
	$configuration->setMerchantId(filter_input(INPUT_POST, 'pronamic_ideal_merchant_id', FILTER_SANITIZE_STRING));
	$configuration->setSubId(filter_input(INPUT_POST, 'pronamic_ideal_sub_id', FILTER_SANITIZE_STRING));
	$configuration->mode = filter_input(INPUT_POST, 'pronamic_ideal_mode', FILTER_SANITIZE_STRING);

	// Basic
	$configuration->hashKey = filter_input(INPUT_POST, 'pronamic_ideal_hash_key', FILTER_SANITIZE_STRING);
	
	// Advanced
	if($_FILES['pronamic_ideal_private_key']['error'] == UPLOAD_ERR_OK) {
		$configuration->privateKey = file_get_contents($_FILES['pronamic_ideal_private_key']['tmp_name']);
	}

	$configuration->privateKeyPassword = filter_input(INPUT_POST, 'pronamic_ideal_private_key_password', FILTER_SANITIZE_STRING);

	if($_FILES['pronamic_ideal_private_certificate']['error'] == UPLOAD_ERR_OK) {
		$configuration->privateCertificate = file_get_contents($_FILES['pronamic_ideal_private_certificate']['tmp_name']);
	}
	
	// Generator
	$configuration->numberDaysValid = filter_input(INPUT_POST, 'pronamic_ideal_number_days_valid', FILTER_SANITIZE_STRING);
	$configuration->country = filter_input(INPUT_POST, 'pronamic_ideal_country', FILTER_SANITIZE_STRING);
	$configuration->stateOrProvince = filter_input(INPUT_POST, 'pronamic_ideal_state_or_province', FILTER_SANITIZE_STRING);
	$configuration->locality = filter_input(INPUT_POST, 'pronamic_ideal_locality', FILTER_SANITIZE_STRING);
	$configuration->organization = filter_input(INPUT_POST, 'pronamic_ideal_organization', FILTER_SANITIZE_STRING);
	$configuration->organizationUnit = filter_input(INPUT_POST, 'pronamic_ideal_organization_unit', FILTER_SANITIZE_STRING);
	$configuration->commonName = filter_input(INPUT_POST, 'pronamic_ideal_common_name', FILTER_SANITIZE_STRING);
	$configuration->eMailAddress = filter_input(INPUT_POST, 'pronamic_ideal_email_address', FILTER_SANITIZE_STRING);

	if(isset($_POST['generate'])) {
		$dn = array();
		
		if(!empty($configuration->country)) {
			$dn['countryName'] = $configuration->country;
		}
		
		if(!empty($configuration->stateOrProvince)) {
			$dn['stateOrProvinceName'] = $configuration->stateOrProvince;
		}
		
		if(!empty($configuration->locality)) {
			$dn['localityName'] = $configuration->locality;
		}
		
		if(!empty($configuration->organization)) {
			$dn['organizationName'] = $configuration->organization;
		}
		
		if(!empty($configuration->organizationUnit)) {
			$dn['organizationalUnitName'] = $configuration->organizationUnit;
		}
		
		if(!empty($configuration->commonName)) {
			$dn['commonName'] = $configuration->commonName;
		}
		
		if(!empty($configuration->eMailAddress)) {
			$dn['emailAddress'] = $configuration->eMailAddress;
		}

		$privateKeyResource = openssl_pkey_new();
		if($privateKeyResource !== false) {
			$csr = openssl_csr_new($dn, $privateKeyResource);
			
			$certificateResource = openssl_csr_sign($csr, null, $privateKeyResource, $configuration->numberDaysValid);
			
			if($certificateResource !== false) {
				$privateKeyPassword = filter_input(INPUT_POST, 'pronamic_ideal_generate_private_key_password', FILTER_SANITIZE_STRING);

				$privateCertificate = null;
				$exportedCertificate = openssl_x509_export($certificateResource, $privateCertificate);
								
				$privateKey = null;
				$exportedKey = openssl_pkey_export($privateKeyResource, $privateKey, $privateKeyPassword);

				if($exportedCertificate && $exportedKey) {
					$configuration->privateKey = $privateKey;
					$configuration->privateKeyPassword = $privateKeyPassword;
					$configuration->privateCertificate = $privateCertificate;
				}
			} else {
				$error = __('Unfortunately we could not generate a certificate resource from the given CSR (Certificate Signing Request).', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN);
			}
		} else {
			$error = __('Unfortunately we could not generate a private key.', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN);
		}
	}

	// Update
	$updated = Pronamic_WordPress_IDeal_ConfigurationsRepository::updateConfiguration($configuration);

	if($updated) {
		// Transient
		Pronamic_WordPress_IDeal_IDeal::deleteConfigurationTransient($configuration);

		$update = sprintf(
			__('Configuration updated, %s.', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN) , 
			sprintf('<a href="%s">', Pronamic_WordPress_IDeal_Admin::getConfigurationsLink()) . __('back to overview', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN) . '</a>'
		);
	} else {
		global $wpdb;
		$wpdb->print_error();
	}
}

?>
<div class="wrap">
	<?php screen_icon(Pronamic_WordPress_IDeal_Plugin::SLUG); ?>

	<h2>
		<?php _e('iDEAL Configuration', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
	</h2>

	<?php if($update): ?>
	
	<div class="updated inline below-h2">
		<p><?php echo $update; ?></p>
	</div>

	<?php endif; ?>

	<?php if($error): ?>
	
	<div class="error inline below-h2">
		<p><?php echo $error; ?></p>
	</div>

	<?php endif; ?>

	<form id="pronamic-ideal-configration-editor" enctype="multipart/form-data" action="" method="post">
		<?php wp_nonce_field('pronamic_ideal_save_configuration', 'pronamic_ideal_nonce'); ?>
		<input name="pronamic_ideal_configuration_id" value="<?php echo esc_attr($configuration->getId()); ?>" type="hidden" />

		<h3>
			<?php _e('General', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
		</h3>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="pronamic_ideal_variant_id">
						<?php _e('Variant', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
					</label>
				</th>
				<td>
					<?php $variantId = $configuration->getVariant() == null ? '' : $configuration->getVariant()->getId(); ?>
	                <select id="pronamic_ideal_variant_id" name="pronamic_ideal_variant_id">
	                	<option value=""></option>
	                	<?php foreach(Pronamic_WordPress_IDeal_ConfigurationsRepository::getProviders() as $provider): ?>
						<optgroup label="<?php echo $provider->getName(); ?>">
							<?php foreach($provider->getVariants() as $variant): ?>
							<option data-ideal-method="<?php echo $variant->getMethod(); ?>" value="<?php echo $variant->getId(); ?>" <?php selected($variantId, $variant->getId()); ?>><?php echo $variant->getName(); ?></option>
							<?php endforeach; ?>
						</optgroup>
						<?php endforeach; ?>
	                </select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pronamic_ideal_merchant_id">
						<?php _e('Merchant ID', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
					</label>
				</th>
				<td>
	                <input id="pronamic_ideal_merchant_id" name="pronamic_ideal_merchant_id" value="<?php echo $configuration->getMerchantId(); ?>" type="text" />

					<span class="description">
						<br />
						<?php _e('You receive the merchant ID (also known as: acceptant ID) from your iDEAL provider.', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pronamic_ideal_sub_id">
						<?php _e('Sub ID', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
					</label>
				</th>
				<td>
	                <input id="pronamic_ideal_sub_id" name="pronamic_ideal_sub_id" value="<?php echo $configuration->getSubId(); ?>" type="text" />

					<span class="description">
						<br />
						<?php printf(__('You receive the sub ID from your iDEAL provider, the default is: %s.', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN), 0); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="pronamic_ideal_mode">
						<?php _e('Mode', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
					</label>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
							<?php _e('Mode', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</legend>
					
						<p>		
							<label>
								<input type="radio" value="<?php echo Pronamic_IDeal_IDeal::MODE_LIVE; ?>" name="pronamic_ideal_mode" <?php checked($configuration->mode, Pronamic_IDeal_IDeal::MODE_LIVE); ?> />
								<?php _e('Live', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
							</label><br />
			
							<label>
								<input type="radio" value="<?php echo Pronamic_IDeal_IDeal::MODE_TEST; ?>" name="pronamic_ideal_mode" <?php checked($configuration->mode, Pronamic_IDeal_IDeal::MODE_TEST); ?> />
								<?php _e('Test', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
							</label>
						</p>
					</fieldset>
				</td>
			</tr>
		</table>

		<div class="extra-settings method-basic method-omnikassa">
			<h3>
				<?php _e('Basic', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
			</h3>
	
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_hash_key">
							<?php _e('Hash Key', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td>
						<input id="pronamic_ideal_hash_key" name="pronamic_ideal_hash_key" value="<?php echo $configuration->hashKey; ?>" type="text" />
	
						<span class="description">
							<br />
							<?php _e('You configure the hash key (also known as: key or secret key) in the iDEAL dashboard of your iDEAL provider.', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</span>
					</td>
				</tr>
			</table>
		</div>

		<div class="extra-settings method-advanced">
			<h3>
				<?php _e('Advanced', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
			</h3>
	
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_private_key_password">
							<?php _e('Private Key Password', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_private_key_password" name="pronamic_ideal_private_key_password" value="<?php echo $configuration->privateKeyPassword; ?>" type="text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_private_key">
							<?php _e('Private Key', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td>
						<input id="pronamic_ideal_private_key" name="pronamic_ideal_private_key" type="file" />
						
						<p>
							<pre class="security-data"><?php echo $configuration->privateKey; ?></pre>
						</p>
						<?php 

						submit_button(
							__('Download Private Key', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN) , 
							'secondary' , 'download_private_key' 
						);

						?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_private_certificate">
							<?php _e('Private Certificate', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td>
						<input id="pronamic_ideal_private_certificate" name="pronamic_ideal_private_certificate" type="file" />
						
						<p>
							<pre class="security-data"><?php echo $configuration->privateCertificate; ?></pre>
						</p>
						<?php 
						
						if(!empty($configuration->privateCertificate)) {
							$fingerprint = Pronamic_IDeal_Security::getShaFingerprint($configuration->privateCertificate);
							$fingerprint = str_split($fingerprint, 2);
							$fingerprint = implode(':', $fingerprint);
						
							echo sprintf(__('SHA Fingerprint: %s', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN), $fingerprint), '<br />';
						}

						submit_button(
							__('Download Private Certificate', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN) , 
							'secondary' , 'download_private_certificate'
						);

						?>
					</td>
				</tr>
			</table>
		</div>

		<?php 
		
		submit_button(
			empty($configuration->id) ? __('Save', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN) : __('Update', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN) , 
			'primary' , 
			'submit'
		);
	
		?>

		<div class="extra-settings method-advanced">
			<h4>
				<?php _e('Private Key and Certificate Generator', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
			</h4>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_generate_private_key_password">
							<?php _e('Private Key Password', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_generate_private_key_password" name="pronamic_ideal_generate_private_key_password" value="<?php echo $configuration->privateKeyPassword; ?>" type="text" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_days">
							<?php _e('Number Days Valid', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_number_days_valid" name="pronamic_ideal_number_days_valid" value="<?php echo $configuration->numberDaysValid; ?>" type="text" />

						<span class="description">
							<br />
							<?php _e('specify the length of time for which the generated certificate will be valid, in days. ', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_country_name">
							<?php _e('Country', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_country" name="pronamic_ideal_country" value="<?php echo $configuration->country; ?>" type="text" />

						<span class="description">
							<br />
							<?php _e('2 letter code [NL]', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_state_or_province">
							<?php _e('State or Province', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_state_or_province" name="pronamic_ideal_state_or_province" value="<?php echo $configuration->stateOrProvince; ?>" type="text" />

						<span class="description">
							<br />
							<?php _e('full name [Friesland]', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_locality">
							<?php _e('Locality', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_locality" name="pronamic_ideal_locality" value="<?php echo $configuration->locality; ?>" type="text" />

						<span class="description">
							<br />
							<?php _e('eg, city', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_organization">
							<?php _e('Organization', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_organization" name="pronamic_ideal_organization" value="<?php echo $configuration->organization; ?>" type="text" />

						<span class="description">
							<br />
							<?php _e('eg, company [Pronamic]', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_organization_unit">
							<?php _e('Organization Unit', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_organization_unit" name="pronamic_ideal_organization_unit" value="<?php echo $configuration->organizationUnit; ?>" type="text" />

						<span class="description">
							<br />
							<?php _e('eg, section', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_common_name">
							<?php _e('Common Name', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_common_name" name="pronamic_ideal_common_name" value="<?php echo $configuration->commonName; ?>" type="text" />

						<span class="description">
							<br />
							<?php _e('eg, YOUR name', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
							<?php _e('Do you have an iDEAL subscription with Rabobank or ING Bank, please fill in the domainname of your website.', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
							<?php _e('Do you have an iDEAL subscription with ABN AMRO, please fill in "ideal_<strong>company</strong>", where "company" is your company name (as specified in the request for the subscription). The value must not exceed 25 characters.', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="pronamic_ideal_email_address">
							<?php _e('Email Address', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN); ?>
						</label>
					</th>
					<td> 
						<input id="pronamic_ideal_email_address" name="pronamic_ideal_email_address" value="<?php echo $configuration->eMailAddress; ?>" type="text" />
					</td>
				</tr>
			</table>

			<?php 
			
			submit_button(
				__('Generate', Pronamic_WordPress_IDeal_Plugin::TEXT_DOMAIN) ,  
				'secundary' , 
				'generate'
			);
		
			?>
		</div>
	</form>
</div>