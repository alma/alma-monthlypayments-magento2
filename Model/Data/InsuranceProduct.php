<?php

namespace Alma\MonthlyPayments\Model\Data;

use Alma\API\Entities\Insurance\Contract;
use Alma\API\Entities\Insurance\File;
use Alma\MonthlyPayments\Helpers\Functions;
use Magento\Catalog\Api\Data\ProductInterface;

class InsuranceProduct
{
    /**
     * @var mixed
     */
    private $linkToken;
    /**
     * @var string
     */
    private $parent;
    /**
     * @var Contract
     */
    private $contract;

    /**
     * @param Contract $contract
     * @param string $parent
     * @param int|null $linkToken
     */
    public function __construct(
        Contract $contract,
        ProductInterface     $parent,
        int      $linkToken = null
    )
    {
        $this->linkToken = $linkToken;
        $this->parent = $parent;
        $this->contract = $contract;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->contract->getId();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->contract->getName();
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->contract->getPrice();
    }

    /**
     * @return float
     */
    public function getFloatPrice(): float
    {
        return (float)($this->getPrice() / 100);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'price' => $this->getPrice(),
            'duration_year' => $this->getDurationYear(),
            'link' => $this->getLinkToken(),
            'parent_name' => $this->getParentName(),
            'parent_price' => $this->getParentPrice(),
            'files' => $this->getFiles()
        ];
    }

    /**
     * @return string|null
     */
    public function getLinkToken(): ?string
    {
        return $this->linkToken;
    }

    /**
     * @param string $linkToken
     * @return void
     */
    public function setLinkToken(string $linkToken): void
    {
        $this->linkToken = $linkToken;
    }

    /**
     * @return string
     */
    public function getParentName(): string
    {
        return $this->parent->getName();
    }

    /**
     * @return int
     */
    public function getParentPrice(): int
    {
        return Functions::priceToCents($this->parent->getPrice());
    }

    public function getDurationYear(): int
    {
        return $this->contract->getProtectionDurationInYear();
    }

    public function getContract(): Contract
    {
        return $this->contract;
    }

    public function getFiles(): array
    {
        $filesArray = [];
        /** @var File $file */
        foreach ($this->contract->getFiles() as $file) {
            $fileArray['name'] = $file->getName();
            $fileArray['type'] = $file->getType();
            $fileArray['url'] = $file->getPublicUrl();
            $filesArray[] = $fileArray;
        }
        return $filesArray;
    }
}
