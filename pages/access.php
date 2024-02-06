<?php if ( is_user_logged_in() ): $current_user = wp_get_current_user(); ?>
					<div class="_access user-area">
						<div class="user-profile-dropdown dropdown">
							<div class="user-profile-name">
								<div class="avatar">
									<?php echo get_avatar( $current_user->ID ) ?>
								</div>
								<?php echo 'Hi '. esc_attr( $current_user->display_name ) ?>
							</div>

							<?php if ( has_nav_menu( 'mylisting-user-menu' ) ) : ?>
								<?php wp_nav_menu([
								    'theme_location' => 'mylisting-user-menu',
								    'container' 	 => false,
								    'depth'     	 => 0,
								    'menu_class'	 => 'menu-list',
								    'items_wrap' 	 => '<ul class="%2$s" aria-labelledby="user-dropdown-menu">%3$s</ul>'
								    ]); ?>
							<?php elseif ( class_exists('WooCommerce') ) : ?>
								<ul class="access-menu" aria-labelledby="user-dropdown-menu">
									<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
										<?php do_action( "case27/user-menu/{$endpoint}/before" ) ?>
										<li class="user-menu-<?php echo esc_attr( $endpoint ) ?>">
											<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"><?php echo esc_html( $label ); ?></a>
										</li>
										<?php do_action( "case27/user-menu/{$endpoint}/after" ) ?>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					</div>
<?php else: ?>
					<div class="user-area">
                    <?php require locate_template( 'templates/dashboard/form-login.php' ) ?>
					</div>

<?php endif ?>