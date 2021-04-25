<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Http;

use Windwalker\Http\Response\HtmlResponse;

/**
 * The ViewResponse class.
 */
class ViewResponse extends HtmlResponse
{
    /**
     * Constructor.
     *
     * @param  array  $data
     * @param  int    $status   The status code.
     * @param  array  $headers  The custom headers.
     */
    public function __construct(protected array $data = [], $status = 200, array $headers = [])
    {
        parent::__construct('', $status, $headers);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param  array  $data
     *
     * @return  static  Return self to support chaining.
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
