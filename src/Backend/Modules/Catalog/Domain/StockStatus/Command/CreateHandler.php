<?php

namespace Backend\Modules\Catalog\Domain\StockStatus\Command;

use Backend\Modules\Catalog\Domain\StockStatus\StockStatus;
use Backend\Modules\Catalog\Domain\StockStatus\StockStatusRepository;

final class CreateHandler
{
    /** @var StockStatusRepository */
    private $stockStatusRepository;

    public function __construct(StockStatusRepository $stockStatusRepository)
    {
        $this->stockStatusRepository = $stockStatusRepository;
    }

    public function handle(Create $createStockStatus): void
    {
        $stockStatus = StockStatus::fromDataTransferObject($createStockStatus);
        $this->stockStatusRepository->add($stockStatus);

        $createStockStatus->setStockStatusEntity($stockStatus);
    }
}
