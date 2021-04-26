<?php
/**
 * Readme.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay
 */

header( 'Content-Type: text/plain' );

$data = file_get_contents( __DIR__ . '/../../package.json' );

// Check if file could be read.
if ( false === $data ) {
	return;
}

$pkg = json_decode( $data );

?>
=== Pronamic Pay ===
Contributors: pronamic, remcotolsma 
Tags: ideal, bank, payment, gravity forms, forms, payment, woocommerce, recurring-payments, shopp, rabobank, friesland bank, ing, mollie, omnikassa, wpsc, wpecommerce, commerce, e-commerce, cart
Donate link: https://www.pronamic.eu/donate/?for=wp-plugin-pronamic-pay&source=wp-plugin-readme-txt
Requires at least: 4.7
Tested up to: 5.7
Requires PHP: 5.6
Stable tag: <?php echo $pkg->version, "\r\n"; ?>

<?php require __DIR__ . '/../general/description-short.php'; ?>


== Description ==

<?php require 'description-long.php'; ?>


== Installation ==

<?php require 'installation.php'; ?>


== Screenshots ==

<?php require 'screenshots.php'; ?>


<?php require 'other-notes.php'; ?>


== Changelog ==

<?php require 'changelog.php'; ?>


== Links ==

<?php require __DIR__ . '/../general/links.php'; ?>
