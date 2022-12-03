<?php

declare(strict_types=1);

namespace KG\ProductImageApi\Model;

use KG\ProductImageApi\Api\Data\ProductImageInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File\Mime;
use Psr\Log\LoggerInterface as PsrLogger;

/**
 * Class ProductImage.
 *
 * @package KG\ProductImageApi\Model
 */
class ProductImage implements ProductImageInterface
{
    private ProductRepositoryInterface $productRepository;
    private PsrLogger $logger;
    private Mime $mime;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param PsrLogger $logger
     * @param Mime $mime
     * @param Filesystem $filesystem
     *
     * @throws FileSystemException
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        PsrLogger $logger,
        Mime $mime,
        Filesystem $filesystem
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->mime = $mime;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @param string $sku
     *
     * @return array
     */
    public function getProductImage(string $sku): array
    {
        $response = [];
        try {
            if ($product = $this->productRepository->get($sku)) {
                /** @var Product $product */
                $imageUrl = $product->getImage();
                foreach ($product->getMediaGalleryImages()->getItems() as $item) {
                    if ($imageUrl == $item->getData('file')) {
                        $path = $item->getData('path');
                        $imageMimeType = $this->mime->getMimeType($path);
                        $imageContentBase64 = base64_encode($this->mediaDirectory->readFile($path));
                        $pathInfo = pathinfo($path);
                        $response = [
                            'entity' => $item->getData(),
                            'file' => $imageUrl,
                            'content' => [
                                'data' => [
                                    ImageContentInterface::NAME => $pathInfo['filename'],
                                    ImageContentInterface::BASE64_ENCODED_DATA => $imageContentBase64,
                                    ImageContentInterface::TYPE => $imageMimeType
                                ]
                            ]
                        ];
                    }
                }
            }
        } catch (NoSuchEntityException | LocalizedException  $e) {
            $this->logger->error(__($e->getMessage()));
        }

        return $response;
    }
}
