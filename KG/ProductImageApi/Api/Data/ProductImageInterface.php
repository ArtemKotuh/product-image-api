<?php

declare(strict_types=1);

namespace KG\ProductImageApi\Api\Data;

interface ProductImageInterface
{
    /**
     * @param string $sku
     *
     * @return array
     */
    public function getProductImage(string $sku): array;
}
