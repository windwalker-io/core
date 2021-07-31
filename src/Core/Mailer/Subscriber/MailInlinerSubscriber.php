<?php

/**
 * Part of unicorn project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Mailer\Subscriber;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Windwalker\Core\Mailer\Event\BeforeSendEvent;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Filter\OutputFilter;

/**
 * The MailInlinerSubscriber class.
 */
#[EventSubscriber]
class MailInlinerSubscriber
{
    #[ListenTo(BeforeSendEvent::class)]
    public function beforeSend(BeforeSendEvent $event): void
    {
        if (!class_exists(CssToInlineStyles::class)) {
            throw new \LogicException(
                'Please install tijsverkoyen/css-to-inline-styles ^2.0 first.'
            );
        }

        $message = $event->getMessage();

        $body = $message->getHtmlBody();

        preg_match_all('/<style.*?>(.*?)<\/style>/s', $body, $matches, PREG_SET_ORDER);

        // Remove script & style
        $body = OutputFilter::stripScript($body);
        $body = OutputFilter::stripStyle($body);
        $body = OutputFilter::stripLinks($body);

        $css = '';

        try {
            if ($asset = $message->getAsset()) {
                // Loop outside styles
                foreach ($asset->getStyles() as $style) {
                    [$path, $minPath] = $asset->normalizeUri($asset->addSysPath($style['url']));

                    if (is_file($minPath)) {
                        $css .= file_get_contents($minPath) . "\n";
                    } elseif (is_file($path)) {
                        $css .= file_get_contents($path) . "\n";
                    }
                }
            }
        } catch (\UnexpectedValueException $e) {
            // No action
        }

        // Loop internal styles
        foreach ($matches as $match) {
            if (!isset($match[1])) {
                continue;
            }

            $css .= $match[1] . "\n";
        }

        $message->html((new CssToInlineStyles())->convert($body, $css));
    }
}
