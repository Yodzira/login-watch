<?php
/**
 * Plugin boot + admin.
 *
 * @package LoginWatch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGW_Plugin {

	public static function boot() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( 'LGW_Admin', 'menu' ) );
			add_action( 'admin_post_lgw_save', array( 'LGW_Admin', 'handle_save' ) );
		}
		LGW_Watcher::boot();
	}
}

class LGW_Admin {

	public static function menu() {
		add_menu_page( 'Login Watch', 'Login Watch', 'manage_options', 'login-watch', array( __CLASS__, 'render' ), 'dashicons-lock' );
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'login-watch' ) );
		}
		check_admin_referer( 'lgw_save' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in Settings::save().
		LGW_Settings::save( isset( $_POST['lgw'] ) ? (array) $_POST['lgw'] : array() );
		wp_safe_redirect( admin_url( 'admin.php?page=login-watch&saved=1' ) );
		exit;
	}

	public static function render() {
		$just_saved = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag.
		$rows       = LGW_Store::recent( 100 );
		?>
		<div class="wrap">
			<h1>Login Watch</h1>

			<?php if ( '1' === $just_saved ) : ?>
				<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:20px">
				<input type="hidden" name="action" value="lgw_save">
				<?php wp_nonce_field( 'lgw_save' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th>Telegram (optional)</th>
						<td>
							<input type="text" name="lgw[token]" value="<?php echo esc_attr( LGW_Settings::get()['token'] ); ?>" class="regular-text" placeholder="Bot token">
							<input type="text" name="lgw[chat]" value="<?php echo esc_attr( LGW_Settings::get()['chat'] ); ?>" class="regular-text" placeholder="Chat ID">
							<p class="description">Admin logins, failed bursts and new admins go here + to the admin email.</p>
						</td>
					</tr>
				</table>
				<button type="submit" class="button button-primary">Save</button>
			</form>

			<h2>Events (last 100)</h2>
			<?php if ( ! $rows ) : ?>
				<p><em>No events yet.</em></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:1100px">
					<thead><tr><th style="width:140px">When</th><th style="width:130px">Kind</th><th style="width:140px">Login</th><th style="width:150px">IP (masked)</th><th>Detail</th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['logged_at'] ); ?></td>
							<td><?php echo esc_html( $row['kind'] ); ?></td>
							<td><?php echo esc_html( $row['user_login'] ); ?></td>
							<td><?php echo esc_html( $row['ip_masked'] ); ?></td>
							<td><?php echo esc_html( $row['detail'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
