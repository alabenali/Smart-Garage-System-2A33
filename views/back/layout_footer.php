
</div><!-- /.page-wrapper -->

<script src="assets/js/validation.js"></script>
<?php if (!empty($extraJs) && is_array($extraJs)): ?>
	<?php foreach ($extraJs as $js): ?>
		<script src="<?php echo htmlspecialchars($js); ?>"></script>
	<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
