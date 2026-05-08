-- Migration : colonnes pour email verification + reset password
ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS `email_verified`       TINYINT(1)   NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `verification_token`   VARCHAR(64)  NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `reset_token`          VARCHAR(64)  NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `reset_token_expires`  DATETIME     NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `profile_picture`      VARCHAR(255) NULL DEFAULT NULL;

-- Index pour les lookups rapides
ALTER TABLE `user`
  ADD INDEX IF NOT EXISTS `idx_verification_token` (`verification_token`),
  ADD INDEX IF NOT EXISTS `idx_reset_token` (`reset_token`);

-- Migration : colonne google_id pour l'authentification Google OAuth
ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS `google_id` VARCHAR(64) NULL DEFAULT NULL AFTER `profile_picture`;

ALTER TABLE `user`
  ADD INDEX IF NOT EXISTS `idx_google_id` (`google_id`);
