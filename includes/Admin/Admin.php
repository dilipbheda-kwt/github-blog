<?php
/**
 * Admin Class
 *
 * @package WordPress
 */

namespace GH_Blog\Admin;

if ( ! class_exists( 'Admin' ) ) {

	/**
	 * Declare class `Admin`
	 */
	class Admin {

		/**
		 * Global options.
		 *
		 * @var $options
		 */
		public $options;

		/**
		 * Setting group
		 *
		 * @var $option_group  string
		 */
		private $option_group = '__gh_blog';

		/**
		 * Option section
		 *
		 * @var $option_section string
		 */
		private $option_section = '__gh_blog_section';

		/**
		 * Option name
		 *
		 * @var $option_name string
		 */
		private $option_name = '__gh_blog';

		/**
		 * Setting fields
		 *
		 * @var $fields array
		 */
		private $fields = array(
			'auto_key'  => array(
				'title' => 'Oauth Key',
				'desc'  => '<a href="https://github.com/settings/tokens" target="_blank">Click to get auth key</a>',
				'type'  => 'password',
			),
			'gh_owner'  => array(
				'title' => 'Repo Owner',
				'desc'  => 'Repositories owner',
				'type'  => 'text',
			),
			'gh_repo'  => array(
				'title' => 'Repo',
				'desc'  => 'Target repositories',
				'type'  => 'text',
			),
		);
		
		/**
		 * Class construct.
		 */
		public function __construct() {
			// Get options
			$this->options 	= array_filter( get_option( $this->option_name ) ? get_option( $this->option_name ) : array() );
			// Add admin menu.
			add_action( 'admin_menu', array( $this, 'register_settings' ) );
		}

		/**
		 * Option Page.
		 */
		public function register_settings() {
			$setting_name = __( 'Github Blog', 'github-blog' );
			// Add option page.
			$hook = add_submenu_page( 'options-general.php', $setting_name, $setting_name, 'edit_themes', 'github-blog', array( $this, 'view_admin_settings' ) );
			// Load page.
			add_action( 'load-' . $hook, array( $this, 'gh_blog_page' ) );

			// Register setting.
			register_setting( $this->option_group, $this->option_name, array( $this, 'sanitize_settings' ) );
			// Add setting section.
			add_settings_section( $this->option_section, '', '__return_false', $this->option_group );
			// Add field
			foreach ( $this->fields as $key => $field ) {
				add_settings_field(
					$key,
					__( $field['title'], 'github-blog' ),
					array( $this, 'settings_field_input' ),
					$this->option_group,
					$this->option_section,
					array(
						'id' 	=> $key,
						'label_for' => $key,
						'desc' => $field['desc'],
						'type' => $field['type']
					)
				);
			}
		}

		/**
		 * escape value using sanitize_text_field
		 *
		 * @param      array  $args   The arguments
		 *
		 * @return     array  ( return post data )
		 */
		public function sanitize_settings( $args ) {
			return array_map( 'sanitize_text_field', $args );
		}

		/**
		 * Setting option input fields.
		 *
		 * @param array $args The arguments
		 */
		public function settings_field_input( $args ) {
			global $doing_option;
			$id 	= isset( $args['id'] ) ? $args['id'] : '';
			$type 	= isset( $args['type'] ) ? $args['type'] : '';
			$desc 	= isset( $args['desc'] ) ? $args['desc'] : '';
			$options = $this->options;
			$value = isset( $options[$id] ) && $doing_option == false ? $options[$id] : '';
			echo "<input id='$id' name='{$this->option_name}[{$id}]' size='40' type='{$type}' value='{$value}' required/>";
			if ( $desc ) {
				echo "<p class='description'>" . __( $desc, 'github-blog' ) . "</div>";
			}
			if ( 'gh_repo' === $id && ! empty( $value ) ) {
				echo '<p style="margin-top: 40px;"><a class="button button-primary" href="' . add_query_arg( array( 'page' => 'github-blog', 'sync_blog' => true ), admin_url( 'admin.php' ) ) . '">' . __( 'Click to import OR update blog', 'github-blog' ) . '</a></p>';
			}
		}

		/**
		 * Main Settings panel
		 *
		 * @since 	1.0
		 */
		public function view_admin_settings() {
			global $doing_option; ?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"></div>
				<h2><?php echo esc_html( 'Github Blog' ); ?></h2>
				<form action="options.php" method="post">
					<?php
					//settings_errors();
					settings_fields( $this->option_group );
					do_settings_sections( $this->option_group );
					submit_button( __( 'Save', 'github-blog' ) );
					?>
				</form>
			</div>
			<?php
		}

		/**
		 * Load Github blog page.
		 */
		public function gh_blog_page() {
			if ( ! wp_doing_ajax() && isset( $_GET['sync_blog'] ) && $_GET['sync_blog'] ) {
				if ( isset( $this->options['auto_key'] ) && ! empty( $this->options['auto_key'] ) ) {
					require_once plugin_dir_path( __FILE__ ) . '../../lib/github-php-client-master/client/GitHubClient.php';
					$client = new \GitHubClient();
					$client->setAuthType( 'x-oauth-basic' );
					$client->setOauthKey( $this->options['auto_key'] );
					$client->setPage();
					$client->setPageSize(1);
					$commits = $client->repos->commits->listCommitsOnRepository( 'dilipbheda', 'our-blog' );
					$github_tree = new \GitHubGitTrees( $client );

					foreach ( $commits as $commit ) {
						$tree = $github_tree->getTree( $this->options['gh_owner'], $this->options['gh_repo'], $commit->getSha() );
						$trees = $tree->getTree();
						if ( ! empty( $trees ) ) {
							foreach ( $trees as $t ) {
								$blog_name = get_bloginfo( 'name' );
								$opts = array(
									'http'	=>	array(
										'header' => "User-Agent:$blog_name/1.0\r\n",
									),
								);
								$context = stream_context_create( $opts );
								$ext = pathinfo( $t->getPath(), PATHINFO_EXTENSION );
								if ( 'md' ==  $ext ) {
									$file_data = file_get_contents( $t->getUrl(), false, $context );
									$file_data = json_decode( $file_data );
									if ( ! empty( $file_data ) ) {
										$content = base64_decode( $file_data->content );
										$content = explode( "\n", $content );
										$content = array_filter( $content );
										$title = reset( $content );
										$title = str_replace( '#', '', $title );
										$title = trim( $title );
										unset( $content[0] );
										$content = array_map( function( $c ) {
											return '<p>' . $c . '</p>';
										}, $content );
										$content = implode( '', $content );
										$post_exist = post_exists( $title, '', '', 'post' );
										if ( empty( $post_exist ) ) {
											$post_id = wp_insert_post(
												array(
													'post_title' => $title,
													'post_content' => $content,
													'post_status'   => 'publish',
													'post_name' => sanitize_title( $title ),
												)
											);
										} else {
											$post_id = $post_exist;
											wp_update_post(
												array(
													'ID' => $post_exist,
													'post_title' => $title,
													'post_content' => $content,
													'post_status'   => 'publish',
												)
											);
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
}
