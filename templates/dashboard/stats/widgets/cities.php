<div class="element">
	<div class="pf-head round-icon">
		<div class="title-style-1">
			<i class="mi appartment"></i>
			<h5><?php _ex( 'Top Cities', 'User Dashboard', 'my-listing' ) ?></h5>
		</div>
	</div>
	<div class="pf-body">
		<?php if ( $cities = $stats->get('visits.cities') ): ?>
			<ul class="dash-table">
				<?php foreach ( $cities as $city ): ?>
					<li><i class="mi villa"></i>
						<?php printf(
							'</span> <strong>%s</strong> ('._x( '%s views', 'User Dashboard', 'my-listing' ).')',
							$city['name'],
							number_format_i18n( $city['count'] )
						) ?>
					</li>
				<?php endforeach ?>
			</ul>
		<?php endif ?>
	</div>
</div>