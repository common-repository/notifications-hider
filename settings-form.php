<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo = plugins_url( 'img/logo.png', __FILE__ );
?>
<div class="wrap">
<img style="width: 100px;border-radius: 50%;" src="<?php echo esc_url( $logo ); ?>" alt="">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form method="post" action="options.php">
		<?php
		settings_fields( 'NHider__options_group' );
		do_settings_sections( 'NHider' );
		submit_button();
		?>
	</form>
</div>
