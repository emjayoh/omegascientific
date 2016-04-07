<!DOCTYPE html>
<!--[if lt IE 7]>
<html class="no-js lt-ie9 lt-ie8 lt-ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7]>
<html class="no-js lt-ie9 lt-ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8]>
<html class="no-js lt-ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" <?php language_attributes(); ?>> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <title><?php wp_title('|'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<!--    <style type="text/css">-->
<!--        .hide-this {display:none;}-->
<!--    </style>-->

    <link rel="shortcut icon" href="<?php bloginfo('stylesheet_directory'); ?>/favicon.ico"/>
	<link href="https://plus.google.com/107033727331169211683" rel="publisher"/>

    <script src="<?php echo get_template_directory_uri(); ?>/assets/js/vendor/modernizr-2.6.2.min.js"></script>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="<?php echo get_template_directory_uri(); ?>/assets/js/vendor/jquery-1.8.2.min.js"><\/script>')</script>

    <?php wp_head(); ?>

    <?php if (have_posts()) : ?>
    <link rel="alternate" type="application/rss+xml" title="<?php echo get_bloginfo('name'); ?> Feed" href="<?php echo home_url() ?>/feed/">
    <?php endif; ?>
<!--    <script type="text/javascript">-->
<!--        $('html').addClass('hide-this');-->
<!--        $(document).ready(function() {-->
<!--            $('html').show();-->
<!--        });-->
<!--    </script>-->
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-41500135-1', 'omegascientific.com');
  ga('send', 'pageview');

</script>
</head>
