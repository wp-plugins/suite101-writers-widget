<div class="suite101-widget">
  <div class="author-area">
    <h3 id="author-name"><a href="<?php echo $parsed_feed['author_uri'] ?>"><?php echo $parsed_feed['author_anchor'] ?></a></h3>
		<div class="suite101-logo">
		  <a href="<?php echo $parsed_feed['home_uri'] ?>">
				<img src="<?php echo $image_base_url ?>logo.png" alt="<?php echo $parsed_feed['image_alt'] ?>" width="100" height="16"/>
				<span class="home-anchor"><?php $parsed_feed['home_anchor'] ?></span>
		  </a>
    </div>		
	</div>
  <div class="suite101-section">
    <?php echo $stc_blurb ?>
	</div>
	<?php if (isset($parsed_feed['articles']) && count($parsed_feed['articles']) > 0): ?>
		  <?php $i = 0 ?>
			<?php $max = count($parsed_feed['articles']) - 1 ?>
		  <?php foreach($parsed_feed['articles'] as $article): ?>
        <div class="suite101-article<?php if ($i == $max) echo " last-article" ?>">
          <a href="<?php echo $article['link'] ?>"><?php echo $article['title'] ?></a>
          <p class="published"><?php echo $article['published'] ?></p>
				</div>
				<?php $i++ ?>
		  <?php endforeach ?>
		<?php endif ?>
  <div class="suite101-cta">
    <a href="<?php echo $parsed_feed['writer_uri'] ?>">
				<img src="<?php echo $image_base_url ?>write.png" title="Apply to Write for Suite101" alt="<?php echo $parsed_feed['writer_anchor'] ?>" />
		</a>
  </div>
</div>