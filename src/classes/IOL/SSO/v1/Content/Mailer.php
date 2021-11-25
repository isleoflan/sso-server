<?php

declare(strict_types=1);

namespace IOL\SSO\v1\Content;

use http\Env;
use IOL\SSO\v1\DataSource\Database;
use IOL\SSO\v1\DataSource\Environment;
use IOL\SSO\v1\DataSource\File;
use IOL\SSO\v1\DataType\Date;
use IOL\SSO\v1\DataType\UUID;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    private string $template;
    private string $templateContentText;
    private string $templateContentHtml;

    private string $prerenderedHtml;

    private array $attachments = [];

    private string $uuid;

    private string $subject;
    private string $receiver;

    private array $templateSettings = [];
    private PHPMailer $mailer;

    private static string $smtpDebug = '';

    public function __construct()
    {
        $this->mailer = new PHPMailer();


        $this->mailer->SMTPDebug = 2;
        $this->mailer->Debugoutput = static function ($str, $level): void {
            Mailer::$smtpDebug .= '[' . $level . '] ' . $str;
        };

        $this->mailer->DKIM_private = File::getBasePath() . '/dkim_private.pem';
        $this->mailer->DKIM_passphrase = '';
        $this->mailer->DKIM_identity = $this->mailer->From;
        $this->mailer->isHTML(true);
        $this->mailer->XMailer = 'IOLMail v1.6.7';

        $this->mailer->isSMTP();

        $this->mailer->SMTPAuth = true;
        $this->mailer->Host = Environment::get('SMTP_HOST');
        $this->mailer->Username = Environment::get('SMTP_USER');
        $this->mailer->Password = Environment::get('SMTP_PASSWORD');
        $this->mailer->SMTPSecure = Environment::get('SMTP_SECURITY');
        $this->mailer->Port = Environment::get('SMTP_PORT');
        try {
            $this->mailer->setFrom(Environment::get('MAIL_FROM_EMAIL'), utf8_decode(Environment::get('MAIL_FROM_DISPLAY_NAME')));
        } catch (\Exception) {
        }
        $this->mailer->DKIM_domain = Environment::get('DKIM_DOMAIN');
        $this->mailer->DKIM_selector = Environment::get('DKIM_SELECTOR');

        if (!is_null(Environment::get('MAIL_REPLY_TO'))) {
            try {
                $this->mailer->addReplyTo(Environment::get('MAIL_REPLY_TO'));
            } catch (Exception) {
            }
        }


        do {
            $this->uuid = UUID::v4();
        } while (file_exists(File::getBasePath() . '/../../../mails/' . $this->uuid . '.html'));

        $this->addTemplateSetting('webversion', Environment::get('MAIL_WEBVIEW_URL') . $this->uuid . '.html');
    }

    public function send(): void
    {
        //$this->insertLog();

        try {
            $this->mailer->addAddress($this->getReceiver());
        } catch (\Exception) {
        }
        $this->mailer->Subject = $this->getSubject();
        $this->mailer->Body = utf8_decode($this->renderMail());

        foreach ($this->attachments as $attachment) {
            try {
                $this->mailer->addAttachment($attachment);
            } catch (\Exception) {
            }
        }

        $errorMessage = null;
        try {
            $this->mailer->send();
        } catch (Exception $e) {
            $errorMessage = $e->errorMessage(); //Pretty error messages from PHPMailer
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage(); //Boring error messages from anything else!
        }

        $this->saveHtml();

        //$this->updateLog($errorMessage);
    }
/* TODO: mail log

    public function insertLog(): void
    {
        $now = new Date('u');
        $database = Database::getInstance();
        $database->insert(
            'mail_log',
            [
                'id' => $this->uuid,
                'create_time' => $now->format(Date::DATETIME_FORMAT_MICRO),
                'send_time' => null,
                'receiver' => $this->getReceiver(),
                'subject' => $this->getSubject(),
                'template' => $this->getTemplate(),
                'success' => null,
                'smtp_debug' => null,
            ]
        );
    }

    public function updateLog(?string $errorMessage): void
    {
        $now = new Date('u');
        $database = Database::getInstance();
        $database->where('id', $this->uuid);
        $database->update(
            'mail_log',
            [
                'send_time' => $now->format(Date::DATETIME_FORMAT_MICRO),
                'success' => is_null($errorMessage),
                'errors' => $errorMessage,
                'smtp_debug' => Mailer::$smtpDebug,
            ]
        );
    }
*/
    public function saveHtml(): void
    {
        $mailContent = $this->prerenderedHtml;
        $mailContent = str_replace(['[[', ']]'], '', $mailContent);

        file_put_contents(File::getBasePath() . '/../../../mails/' . $this->uuid . '.html', $mailContent);
    }

    public function attach($path): void
    {
        $this->attachments[$path] = $path;
    }

    public function getFooter(): bool|string
    {
        return file_get_contents(
            File::getBasePath() . '/mail/assets/mail-footer.html'
        );
    }

    public function getHeader(): bool|string
    {
        return file_get_contents(
            File::getBasePath() . '/mail/assets/mail-header.html'
        );
    }

    public function renderMail(): array|string
    {
        $mailContent = $this->getHeader() . $this->getTemplateContentHtml() . $this->getFooter();

        foreach ($this->templateSettings as $setting => $value) {
            $mailContent = str_replace('{{' . strtoupper($setting) . '}}', $value, $mailContent);
        }

        $this->prerenderedHtml = $mailContent;

        foreach ($this->getAllInlineImages($mailContent) as $image) {
            $imagePath = str_replace(['[[', ']]'], '', $image);
            $imageName = str_replace(['/assets/', '.jpg', '.png'], '', $imagePath);
            $mailContent = str_replace($image, 'cid:' . $imageName, $mailContent);

            try {
                $this->mailer->AddEmbeddedImage(File::getBasePath() . '/../../../mails' . $imagePath, $imageName);
            } catch (\Exception) {
            }
        }

        return $mailContent;
    }

    public function getAllInlineImages($content): array
    {
        $matches = [];
        preg_match_all("/(\[\[.*]])/m", $content, $matches);

        return $matches[0];
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
        $this->setTemplateContentHtml(
            file_get_contents(
                File::getBasePath() . '/mail/templates/' . $this->getTemplate() . '.html'
            )
        );
        //$this->setTemplateContentText(file_get_contents("/var/www/api/mail-templates-general/".$this->getTemplate().".txt"));
    }

    public function setPreheader(string $preheader): void
    {
        $this->addTemplateSetting('preheader', $preheader);
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return string
     */
    public function getTemplateContentHtml(): string
    {
        return $this->templateContentHtml;
    }

    /**
     * @param string $templateContentText
     */
    public function setTemplateContentText(string $templateContentText): void
    {
        $this->templateContentText = $templateContentText;
    }

    /**
     * @return string
     */
    public function getTemplateContentText(): string
    {
        return $this->templateContentText;
    }

    /**
     * @param string $receiver
     */
    public function setReceiver(string $receiver): void
    {
        $this->receiver = $receiver;
    }

    /**
     * @return string
     */
    public function getReceiver(): string
    {
        return $this->receiver;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return utf8_decode($this->subject);
    }

    /**
     * @param array $templateSettings
     */
    public function setTemplateSettings(array $templateSettings): void
    {
        $this->templateSettings = $templateSettings;
    }

    public function addTemplateSetting(string $template_setting, string $value): void
    {
        $this->templateSettings[$template_setting] = $value;
    }

    public function addCustomHeader(string $string): void
    {
        try {
            $this->mailer->addCustomHeader($string);
        } catch (\Exception) {
        }
    }

    public function setPriority($val): void
    {
        $this->mailer->Priority = $val;
    }

    /**
     * @param string $template_content_html
     */
    private function setTemplateContentHtml(string $template_content_html): void
    {
        $this->templateContentHtml = $template_content_html;
    }
}