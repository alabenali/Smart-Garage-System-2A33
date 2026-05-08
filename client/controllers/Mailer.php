<?php
// controllers/Mailer.php
// Déplacé depuis helpers/Mailer.php → controllers/Mailer.php
// Les chemins require_once pointent maintenant vers controllers/PHPMailer/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // PHPMailer est maintenant dans controllers/PHPMailer/
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
}

class Mailer {

    private static function createMailer(): PHPMailer {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mohamedghaithneji@gmail.com';
        $mail->Password   = 'nsabesxhbhplqyuw';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('mohamedghaithneji@gmail.com', 'Smart Garage');
        return $mail;
    }

    public static function sendRegisterCode(string $to, string $nom, string $code): bool {
        $body = self::htmlTemplate("Confirmez votre inscription", "
            <p>Bonjour <strong>" . htmlspecialchars($nom) . "</strong>,</p>
            <p>Merci de vous inscrire sur <strong>Smart Garage</strong> !</p>
            <p>Pour finaliser la création de votre compte, entrez le code de confirmation ci-dessous :</p>
            <div style='text-align:center;margin:30px 0;'>
              <div style='background:#00E5FF;color:#0a0a1a;padding:20px 40px;border-radius:12px;
                          display:inline-block;font-size:2.5rem;font-weight:900;letter-spacing:12px;'>
                {$code}
              </div>
            </div>
            <p style='text-align:center;color:#ccc;'>Ce code expire dans <strong>15 minutes</strong>.</p>
            <p style='color:#999;font-size:0.85rem;'>Si vous n'avez pas demandé la création d'un compte, ignorez cet email.</p>
        ");
        try {
            $mail = self::createMailer();
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = 'Smart Garage – Code de confirmation d\'inscription : ' . $code;
            $mail->Body    = $body;
            $mail->AltBody = 'Votre code d\'inscription Smart Garage : ' . $code . ' (expire dans 15 minutes)';
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendResetCode(string $to, string $nom, string $code): bool {
        $body = self::htmlTemplate("Réinitialisation du mot de passe", "
            <p>Bonjour <strong>" . htmlspecialchars($nom) . "</strong>,</p>
            <p>Vous avez demandé la réinitialisation de votre mot de passe sur <strong>Smart Garage</strong>.</p>
            <p>Voici votre code de vérification :</p>
            <div style='text-align:center;margin:30px 0;'>
              <div style='background:#00E5FF;color:#0a0a1a;padding:20px 40px;border-radius:12px;
                          display:inline-block;font-size:2.5rem;font-weight:900;letter-spacing:12px;'>
                {$code}
              </div>
            </div>
            <p style='text-align:center;color:#ccc;'>Ce code expire dans <strong>15 minutes</strong>.</p>
            <p style='color:#999;font-size:0.85rem;'>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
        ");
        try {
            $mail = self::createMailer();
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = 'Smart Garage – Code de réinitialisation : ' . $code;
            $mail->Body    = $body;
            $mail->AltBody = 'Votre code Smart Garage : ' . $code . ' (expire dans 15 minutes)';
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    private static function htmlTemplate(string $title, string $content): string {
        return "<!DOCTYPE html>
<html lang='fr'><head><meta charset='UTF-8'><title>{$title}</title></head>
<body style='margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f0f4f8;padding:40px 0;'>
    <tr><td align='center'>
      <table width='560' cellpadding='0' cellspacing='0'
             style='background:#1a1a2e;border-radius:12px;overflow:hidden;'>
        <tr>
          <td style='padding:32px;text-align:center;border-bottom:2px solid #00E5FF;'>
            <span style='font-size:2rem;'>🚗</span>
            <h1 style='color:#00E5FF;margin:8px 0 4px;font-size:1.5rem;'>{$title}</h1>
            <p style='color:#ccc;margin:0;font-size:0.9rem;'>Smart Garage – Espace Client</p>
          </td>
        </tr>
        <tr><td style='padding:32px;color:#e0e0e0;line-height:1.7;font-size:0.95rem;'>
          {$content}
        </td></tr>
        <tr><td style='padding:16px 32px;text-align:center;border-top:1px solid #333;'>
          <p style='color:#666;font-size:0.8rem;margin:0;'>© " . date('Y') . " Smart Garage. Tous droits réservés.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>";
    }

}
