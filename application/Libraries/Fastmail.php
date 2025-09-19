<?php

namespace App\Libraries;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use System\Libraries\Render;

class Fastmail
{
    protected $mailer;
    protected $config;
    protected $theme;

    public function __construct($config = [])
    {
        // Get email config from config file if no custom config provided
        $option_email = option('email');
        $option_email = array_column($option_email, 'email_value', 'email_key');
        $this->config = !empty($config) ? $config : $option_email;
        $this->theme = config('theme'); // Get current theme from config
        $this->mailer = new PHPMailer(true); // Create an instance of PHPMailer
        $this->_setup();
    }

    /**
     * Setup configuration for PHPMailer
     */
    protected function _setup()
    {
        try {
            // Setup SMTP server configuration
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['mail_host'] ?? '';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['mail_username'] ?? '';
            $this->mailer->Password = $this->config['mail_password'] ?? '';
            $this->mailer->SMTPSecure = $this->config['mail_encryption'] ?? 'tls';
            $this->mailer->Port = $this->config['mail_port'] ?? 587;
            $this->mailer->CharSet  = $this->config['mail_charset'] ?? 'UTF-8';

            // Set default sender address
            if (!empty($this->config['mail_from_address'])) {
                $this->mailer->setFrom($this->config['mail_from_address'], $this->config['mail_from_name'] ?? '');
            }
        } catch (Exception $e) {
            throw new \System\Core\AppException('Fastmail Setup Error: ' . $e->getMessage());
        }
    }

    /**
     * Send email using HTML template from views
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $template Template file name (without .php extension)
     * @param array $data Data array passed to template
     * @param array $options Additional options (cc, bcc, attachments, isHtml)
     * @return bool Returns true if sent successfully, false if failed
     */
    public function send($to, $subject, $template, $data = [], $options = [])
    {
        try {
            $this->mailer->clearAllRecipients(); // Clear all recipients before adding new
            $this->mailer->addAddress($to);

            // Add CC if exists
            if (isset($options['cc']) && is_array($options['cc'])) {
                foreach ($options['cc'] as $cc) {
                    $this->mailer->addCC($cc);
                }
            }

            // Add BCC if exists
            if (isset($options['bcc']) && is_array($options['bcc'])) {
                foreach ($options['bcc'] as $bcc) {
                    $this->mailer->addBCC($bcc);
                }
            }

            // Add attachments if exists
            if (isset($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $file) {
                    $this->mailer->addAttachment($file);
                }
            }

            // Get HTML content from template
            $body = $this->render($template, $data);

            // Set subject and content
            $this->mailer->isHTML($options['isHtml'] ?? true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->SMTPDebug = 2;
            // Send email
            return $this->mailer->send();
        } catch (Exception $e) {
            // Log error if needed
            \System\Libraries\Logger::error($e->getMessage(), $e->getFile(), $e->getLine());
            return false;
        }
    }

    /**
     * Render HTML template for email
     *
     * @param string $template Template file name (without .php extension)
     * @param array $data Data array passed to template
     * @return string Rendered HTML content
     */
    protected function render($template, $data = [])
    {
        // Use Render to get content from template
        return Render::component('Common/Email/'.$template, $data);
    }
}
