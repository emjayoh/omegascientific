<?php get_template_part('templates/head'); ?>
<body <?php body_class(); ?>>
<?php
// Use Bootstrap's navbar if enabled in config.php
if (current_theme_supports('bootstrap-top-navbar')) {
//	get_template_part('templates/header-top-navbar');
	get_template_part('templates/header-v2');
} else {
	get_template_part('templates/header');
}
?>
<div id="page-wrap">
	<div id="wrap" class="container mobile-container" role="document">
		<div id="content" class="row">
			<?php if (is_front_page()) { get_template_part('templates/home', 'hero'); } ?>
			<div id="main" class="<?php echo roots_main_class(); ?>" role="main">
				<?php include roots_template_path(); ?>
			</div>
			<?php if (roots_display_sidebar()) : ?>
				<aside id="sidebar" class="<?php echo roots_sidebar_class(); ?>" role="complementary">
					<?php get_template_part('templates/sidebar'); ?>
				</aside>
			<?php endif; ?>
		</div>
		<!-- /#content -->
	</div>
	<!-- /#wrap -->
</div>

<?php get_template_part('templates/footer'); ?>

</body>
</html>
