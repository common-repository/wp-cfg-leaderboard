<?php
/*
 * Template Name: WPCFG Leaderboard empty page 
 */
?>
<!doctype html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<? wp_head();?>
</head>

<body class="wpcfgstandalone">
	<?php while ( have_posts() ) : the_post(); ?>
				<h1><?php the_title(); ?></h1>
				<article id="post-<?php the_ID(); ?>">
							<?php the_content(); ?>
				</article>
	<?php endwhile; ?>
<?php wp_footer(); ?>
	</body>
</html>