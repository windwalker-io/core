<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Mailer;

use Windwalker\Core\Asset\Asset;
use Windwalker\Core\Asset\AssetManager;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Widget\Widget;
use Windwalker\Core\Widget\WidgetHelper;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Utilities\Queue\PriorityQueue;

/**
 * The MailMessage class.
 *
 * @since  3.0
 */
class MailMessage
{
    /**
     * Property subject.
     *
     * @var  string
     */
    protected $subject;

    /**
     * Property to.
     *
     * @var  array
     */
    protected $to = [];

    /**
     * Property from.
     *
     * @var  array
     */
    protected $from = [];

    /**
     * Property cc.
     *
     * @var  array
     */
    protected $cc = [];

    /**
     * Property bcc.
     *
     * @var  array
     */
    protected $bcc = [];

    /**
     * Property replyto.
     *
     * @var  array
     */
    protected $replyTo = [];

    /**
     * Property content.
     *
     * @var  string
     */
    protected $body;

    /**
     * Property html.
     *
     * @var  bool
     */
    protected $html = true;

    /**
     * Property files.
     *
     * @var  MailAttachment[]
     */
    protected $files = [];

    /**
     * Property asset.
     *
     * @var AssetManager
     */
    protected $asset;

    /**
     * create
     *
     * @return  MailMessage
     */
    public static function create()
    {
        return new static();
    }

    /**
     * MailMessage constructor.
     *
     * @param string $subject
     * @param array  $content
     * @param bool   $html
     */
    public function __construct($subject = null, $content = null, $html = true)
    {
        $this->subject = $subject;
        $this->body    = $content;
        $this->html    = $html;
        $this->asset   = clone Asset::getInstance();

        $this->asset->reset();
    }

    /**
     * subject
     *
     * @param string $subject
     *
     * @return  static
     */
    public function subject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * to
     *
     * @param string|array $email
     * @param string       $name
     *
     * @return  static
     */
    public function to($email, $name = null)
    {
        $this->addEmail('to', $email, $name);

        return $this;
    }

    /**
     * from
     *
     * @param string|array $email
     * @param string       $name
     *
     * @return  static
     */
    public function from($email, $name = null)
    {
        $this->addEmail('from', $email, $name);

        return $this;
    }

    /**
     * cc
     *
     * @param string|array $email
     * @param string       $name
     *
     * @return  static
     */
    public function cc($email, $name = null)
    {
        $this->addEmail('cc', $email, $name);

        return $this;
    }

    /**
     * bcc
     *
     * @param string|array $email
     * @param string       $name
     *
     * @return  static
     */
    public function bcc($email, $name = null)
    {
        $this->addEmail('bcc', $email, $name);

        return $this;
    }

    /**
     * bcc
     *
     * @param string|array $email
     * @param string       $name
     *
     * @return  static
     */
    public function replyTo($email, $name = null)
    {
        $this->addEmail('replyTo', $email, $name);

        return $this;
    }

    /**
     * content
     *
     * @param string $body
     * @param bool   $html
     *
     * @return  static
     */
    public function body($body, $html = null)
    {
        if ($html !== null) {
            $this->html($html);
        }

        $this->body = $body;

        return $this;
    }

    /**
     * html
     *
     * @param bool $bool
     *
     * @return  static
     */
    public function html($bool)
    {
        $this->html = (bool) $bool;

        return $this;
    }

    /**
     * from
     *
     * @param string|MailAttachment $file
     * @param string                $name
     * @param string                $type
     *
     * @return static
     */
    public function attach($file, $name = null, $type = null)
    {
        if (!$file instanceof MailAttachment) {
            $file = new MailAttachment($file);
        }

        if ($name) {
            $file->setFilename($name);
        }

        if ($type) {
            $file->setContentType($type);
        }

        $this->files[] = $file;

        return $this;
    }

    /**
     * renderBody
     *
     * @param string                   $layout
     * @param array                    $data
     * @param string|RendererInterface $engine
     * @param string|AbstractPackage   $package
     * @param string                   $prefix
     *
     * @return static
     * @throws \ReflectionException
     */
    public function renderBody($layout, $data = [], $engine = null, $package = null, $prefix = 'mail')
    {
        $data['asset'] = $this->asset;

        $this->body(
            $this->getBodyRenderer($layout, $engine, $package, $prefix)
                ->render($data),
            true
        );

        return $this;
    }

    /**
     * getBodyRenderer
     *
     * @param string                 $layout
     * @param string                 $engine
     * @param string|AbstractPackage $package
     * @param string|null            $prefix
     *
     * @return  Widget
     *
     * @throws \ReflectionException
     *
     * @since  3.5.12
     */
    public function getBodyRenderer(string $layout, $engine = null, $package = null, ?string $prefix = 'mail'): Widget
    {
        $bcPath = null;

        if ($prefix !== 'mail') {
            $package = PackageHelper::getPackage($package);

            $bcPath = $package->getDir() . '/Templates/mail';
        }

        $widget = WidgetHelper::createWidget($layout, $engine, $package);
        $widget->setPathPrefix($prefix)
            ->registerPaths(true);

        if ($bcPath) {
            $widget->addPath($bcPath, PriorityQueue::BELOW_NORMAL);
        }

        return $widget;
    }

    /**
     * Method to get property Subject
     *
     * @return  string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Method to get property To
     *
     * @return  array
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Method to get property From
     *
     * @return  array
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Method to get property Cc
     *
     * @return  array
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Method to get property Bcc
     *
     * @return  array
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * Method to get property ReplyTo
     *
     * @return  array
     *
     * @since  3.3
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * Method to get property Content
     *
     * @return  string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Method to get property Html
     *
     * @return  boolean
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Method to get property Files
     *
     * @return  MailAttachment[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * add
     *
     * @param string       $field
     * @param string|array $email
     * @param string       $name
     *
     * @return  void
     */
    protected function addEmail($field, $email, $name = null)
    {
        if (is_array($email)) {
            foreach ($email as $mail => $name) {
                if (is_numeric($mail)) {
                    $mail = $name;
                    $name = null;
                }

                if ($mail === null) {
                    continue;
                }

                $this->$field($mail, $name);
            }

            return;
        }

        $email = Punycode::toAscii($email);

        $this->{$field}[$email] = $name;
    }

    /**
     * dump
     *
     * @return  array
     *
     * @since  3.5.2
     */
    public function dump(): array
    {
        return get_object_vars($this);
    }

    /**
     * Method to get property Asset
     *
     * @return  AssetManager
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getAsset(): AssetManager
    {
        return $this->asset;
    }
}
