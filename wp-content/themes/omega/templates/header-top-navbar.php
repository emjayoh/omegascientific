<header id="banner" role="banner">
    <div class="container">
        <div class="row">
            <div class="span6">
                <a class="brand pull-left" href="<?php echo home_url(); ?>/" title="<?php bloginfo('name'); ?>">
                    <img src="<?php bloginfo('template_url'); ?>/assets/img/omega-logo-sharp.png" alt="<?php bloginfo('name'); ?>" width="" height="" />
                </a>
            </div>
            <div class="span6">
                <div id="meta-wrap-1" class="row">
					<div class="span6">
						<div class="pull-right">
							<?php if (is_user_logged_in()) { ?>
							<a id="header-my-account" href="<?php echo get_permalink(35); ?>">My Account</a> | <a id="header-logout" href="<?php echo get_permalink(345); ?>">Logout</a>
							<?php } else { ?>
							<a id="header-login" href="<?php echo get_permalink(344); ?>">Login</a>
							<?php } ?>
						</div>
					</div>
                </div>
                <div id="meta-wrap-2" class="row">
					<div class="span3">
						<div id="phone-wrap" class="pull-right">
							<p><img src="<?php bloginfo('template_url'); ?>/assets/img/phone-number.png" alt="" width="" height="" /></p>
						</div>
					</div>
                    <div class="span3">
                        <div id="search-wrap" class="pull-right">
                            <?php get_search_form(true); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<div class="row">
			<div class="span12">
				<!-- mfunc -->
				<?php
				if (has_nav_menu('primary_navigation')) :
					wp_nav_menu(array('theme_location' => 'primary_navigation'));
				endif;
				?>
				<!-- /mfunc -->
			</div>
		</div>
    </div>
<!--    <div class="navbar navbar-inner">-->
<!--        <div id="container" class="container">-->
<!--            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">-->
<!--                <span class="icon-bar"></span>-->
<!--                <span class="icon-bar"></span>-->
<!--                <span class="icon-bar"></span>-->
<!--            </a>-->
<!--            <nav id="nav-main" class="nav-collapse" role="navigation">-->
                <?php
//                if (has_nav_menu('primary_navigation')) :
//                    wp_nav_menu(array('theme_location' => 'primary_navigation', 'menu_class' => 'nav'));
//                endif;
                ?>
<!--            </nav>-->
<!--        </div>-->
<!--    </div>-->
</header>