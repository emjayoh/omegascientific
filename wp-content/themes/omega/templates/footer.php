<footer id="content-info" role="contentinfo">
	<div id="footer-top" class="container mobile-container">
		<div class="row">
			<div class="span3">
				<?php dynamic_sidebar('sidebar-footer-section-1'); ?>
			</div>
			<div class="span3">
				<?php dynamic_sidebar('sidebar-footer-section-2'); ?>
			</div>
			<div class="span3">
				<?php dynamic_sidebar('sidebar-footer-section-3'); ?>
			</div>
			<div class="span3">
				<?php dynamic_sidebar('sidebar-footer-section-4'); ?>
			</div>
		</div>
	</div>
	<div id="footer-bottom">
		<div class="container">
			<div id="footer-copyright-wrap" class="pull-left">
				<p id="footer-copyright">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>
			</div>
			<div id="footer-meta-links-wrap" class="pull-right">
				<ul id="footer-meta-links" class="clearfix">
					<li><a href="<?php echo get_permalink(302); ?>">Terms</a></li>
					<li><a href="<?php echo get_permalink(304); ?>">Privacy</a></li>
					<li><a href="<?php echo get_permalink(577); ?>">Sitemap</a></li>
				</ul>
			</div>
		</div>
	</div>
</footer>

<?php if (GOOGLE_ANALYTICS_ID) : ?>
	<script>
		var _gaq = [
			['_setAccount', '<?php echo GOOGLE_ANALYTICS_ID; ?>'],
			['_trackPageview']
		];
		(function (d, t) {
			var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
			g.src = ('https:' == location.protocol ? '//ssl' : '//www') + '.google-analytics.com/ga.js';
			s.parentNode.insertBefore(g, s)
		}(document, 'script'));
	</script>
<?php endif; ?>

<?php wp_footer(); ?>
