<!--<div id="top-stripe" class="mobile-container">-->
<!--	<div class="container">-->
<!--		<div class="clearfix">-->
<!--			<div id="phone-mobile" class="pull-left visible-phone">-->
<!--				<span><strong>800.799.5873</strong></span>-->
<!--			</div>-->
<!--			<div class="pull-right">-->
<!--				--><?php //if (is_user_logged_in()) { ?>
<!--					<a id="header-my-account" href="--><?php //echo get_permalink(35); ?><!--">My Account</a> | <a id="header-logout" href="--><?php //echo get_permalink(345); ?><!--">Logout</a>-->
<!--				--><?php //} else { ?>
<!--					<a id="header-login" href="--><?php //echo get_permalink(344); ?><!--">Login</a>-->
<!--				--><?php //} ?>
<!--			</div>-->
<!--		</div>-->
<!--	</div>-->
<!--</div>-->
<header id="banner" role="banner" class="mobile-container">
	<div class="container">
		<div class="row">
			<div class="span4">
				<a class="brand pull-left" href="<?php echo home_url(); ?>/" title="<?php bloginfo('name'); ?>">
					<img src="<?php bloginfo('template_url'); ?>/assets/img/omega-logo-sharp.png" alt="<?php bloginfo('name'); ?>" width="" height="" />
				</a>
			</div>
			<div id="header-right-wrap" class="pull-right">
				<div class="clearfix hidden-phone">
					<div id="phone-wrap" class="pull-left">
						<p><i class="icon-phone"></i>&nbsp;&nbsp;<img src="<?php bloginfo('template_url'); ?>/assets/img/phone-number.png" alt="" width="" height="" /></p>
					</div>
					<div id="user-meta-wrap" class="pull-right">
						<?php if (is_user_logged_in()) { ?>
							<a id="header-my-account" href="<?php echo get_permalink(35); ?>">My Account&nbsp;<i class="icon-user"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;<a id="header-logout" href="<?php echo get_permalink(345); ?>">Logout&nbsp;<i class="icon-external-link"></i></a>
						<?php } else { ?>
							<a id="header-login" href="<?php echo get_permalink(344); ?>">Login</a>
						<?php } ?>
					</div>
				</div>
				<div class="clearfix">
					<div class="menu-wrap">
						<!-- mfunc FRAGMENT_CACHING -->
						if (has_nav_menu('primary_navigation')) :
							wp_nav_menu(array('theme_location' => 'primary_navigation'));
						endif;
						<!-- /mfunc FRAGMENT_CACHING -->
						<?php
						/*if (has_nav_menu('primary_navigation')) :
							wp_nav_menu(array('theme_location' => 'primary_navigation'));
						endif;*/
						?>
					</div>
				</div>

			</div>
		</div>
	</div>
</header>