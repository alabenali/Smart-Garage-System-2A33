
</div><!-- /.page-wrapper -->

<script src="views/js/validation.js"></script>
<?php if (!empty($extraJs) && is_array($extraJs)): ?>
	<?php foreach ($extraJs as $js): ?>
		<?php
		$jsVersion = time();
		if (strpos($js, 'views/js/') === 0) {
			$relativeJs = substr($js, strlen('views/js/'));
			$absJsPath = __DIR__ . '/../js/' . $relativeJs;
			$jsVersion = @filemtime($absJsPath) ?: time();
		}
		?>
		<script src="<?php echo htmlspecialchars($js); ?>?v=<?php echo $jsVersion; ?>"></script>
	<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
