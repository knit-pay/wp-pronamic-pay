<h3><?php esc_html_e( 'Dashboard', 'pronamic_ideal' ); ?></h3>

<p>
	<?php esc_html_e( 'On the Pronamic iDEAL dashboard you can restart this tour and see an overview of the latest pending payments.', 'pronamic_ideal' ); ?>
	<?php esc_html_e( 'It also gives you access to the “Getting Started” and “System Status” pages in case you have issues.', 'pronamic_ideal' ); ?>
	<?php esc_html_e( 'And you can follow the latest news from the Pronamic.eu weblog.', 'pronamic_ideal' ); ?>
</p>

<div class="wp-pointer-buttons pp-pointer-buttons">
	<span class="pp-pointer-buttons-right">
		<a href="<?php echo esc_attr( add_query_arg( 'post_type', 'pronamic_payment', admin_url( 'edit.php' ) ) ); ?>" class="button-primary pp-pointer-button-next"><?php esc_html_e( 'Next', 'pronamic_ideal' ); ?></a>

		<a href="<?php echo esc_attr( $this->get_close_url() ); ?>" class="button-secondary pp-pointer-button-close"><?php esc_html_e( 'Close', 'pronamic_ideal' ); ?></a>
	</span>
</div>