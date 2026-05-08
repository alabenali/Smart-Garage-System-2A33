
		</div>
	</main>
</div>

<script src="views/js/validation.js"></script>
<?php if (!empty($extraJs) && is_array($extraJs)): ?>
	<?php foreach ($extraJs as $js): ?>
		<script src="<?php echo htmlspecialchars($js); ?>"></script>
	<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../client/views/backoffice/ai_helper_launcher.php'; ?>
</body>
</html>
