<?php

namespace Alma\MonthlyPayments\Model\Data;

use Alma\API\Entities\Insurance\Contract;
use Alma\API\Entities\Insurance\File;
use Alma\MonthlyPayments\Helpers\Functions;

class InsuranceProduct
{
    /**
     * @var mixed
     */
    private $linkToken;
    /**
     * @var Contract
     */
    private $contract;
    /**
     * @var float
     */
    private $parentPrice;
    /**
     * @var string
     */
    private $parentName;

    /**
     * @param Contract $contract
     * @param string $parentName
     * @param float $parentPrice
     * @param int|null $linkToken
     */
    public function __construct(
        Contract         $contract,
        string           $parentName,
        float            $parentPrice,
        int              $linkToken = null
    )
    {
        $this->linkToken = $linkToken;
        $this->parentName = $parentName;
        $this->parentPrice = $parentPrice;
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
        return $this->parentName;
    }

    /**
     * @return int
     */
    public function getParentPrice(): int
    {
        return Functions::priceToCents($this->parentPrice);
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
