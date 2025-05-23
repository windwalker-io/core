<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Command;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Environment\Environment;
use Windwalker\Utilities\Str;

/**
 * Trait CommandCronTrait
 */
trait CommandScheduleTrait
{
    #[Inject]
    protected ConsoleApplication $app;

    #[Inject]
    protected Environment $environment;

    public function getCronPosition(): string
    {
        $user = trim($this->app->runProcess('whoami')->getOutput());

        if (Environment::isLinux()) {
            $path = "/var/spool/cron/crontabs/{$user}";
        } elseif (Environment::isUnix()) {
            $path = "/usr/lib/cron/tabs/{$user}";
        } elseif (Environment::isWindows()) {
            throw new \RuntimeException('Schedule install not supports Windows.');
        }

        return $path;
    }

    public function getScheduleExpression(string $tz = '', bool|null|string $phpBinary = false): string
    {
        if ($phpBinary === true) {
            $php = (new PhpExecutableFinder())->find();
        } elseif ($phpBinary) {
            $php = $phpBinary;
        } else {
            $php = '';
        }

        $entry = $this->environment->getEntry();

        if ($tz) {
            $tz = '--tz=' . $tz;
        }

        return "* * * * * $php $entry schedule:run {$tz} >> /dev/null 2>&1";
    }

    protected function cronExists(string $cronContent, array &$matches = null): false|int
    {
        $expr = $this->getScheduleExpression('__TZ__', '__PHP__');

        $regex = $this->buildExpressionFindingRegex($expr);

        return preg_match($regex, $cronContent, $matches);
    }

    protected function removeExpression(string $cronContent): string
    {
        $expr = $this->getScheduleExpression('__TZ__', '__PHP__');

        $regex = $this->buildExpressionFindingRegex($expr);

        return (string) preg_replace($regex, '', $cronContent);
    }

    /**
     * @param  string  $expr
     *
     * @return  string
     */
    protected function buildExpressionFindingRegex(string $expr): string
    {
        $expr = str_replace('--tz=__TZ__', '__TZ__', $expr);

        $regex = preg_quote($expr, '/');
        $regex = Str::collapseWhitespaces($regex);

        $regex = str_replace('__TZ__', '(--tz=[\w\/]+)?', $regex);
        $regex = str_replace('__PHP__', '(.*)', $regex);

        return '/' . str_replace(' ', "\\s*", $regex) . '/';
    }

    protected function replaceCrontab(string $content): Process
    {
        return $this->app->mustRunProcess(
            sprintf(
                'echo "%s" | crontab -',
                rtrim($content) . "\n"
            )
        );
    }

    protected function appendCrontab(string $expr): Process
    {
        return $this->app->mustRunProcess(
            sprintf(
                'crontab -l | { cat; echo "%s"; } | crontab -',
                $expr
            )
        );
    }
}
