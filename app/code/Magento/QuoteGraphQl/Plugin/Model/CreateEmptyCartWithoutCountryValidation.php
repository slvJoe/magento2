<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Plugin\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Closure;
use Exception;
use Magento\Quote\Model\QuoteManagement;

class CreateEmptyCartWithoutCountryValidation
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param CartRepositoryInterface $quoteRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly QuoteFactory $quoteFactory
    ) {
    }

    /**
     * Create empty cart for customer without country validation
     *
     * @param QuoteManagement $subject
     * @param Closure $proceed
     * @param int $customerId
     * @return bool|int
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCreateEmptyCartForCustomer(
        QuoteManagement $subject,
        Closure $proceed,
        int $customerId
    ): bool|int {
        $storeId = (int) $this->storeManager->getStore()->getStoreId();
        $quote = $this->createCustomerCart($customerId, $storeId);

        try {
            $this->quoteRepository->save($quote);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__("The quote can't be created."));
        }
        return (int)$quote->getId();
    }

    /**
     * Creates a cart for the currently logged-in customer.
     *
     * @param int $customerId
     * @param int $storeId
     * @return Quote Cart object.
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function createCustomerCart(int $customerId, int $storeId): Quote
    {
        try {
            $activeQuote = $this->quoteRepository->getActiveForCustomer($customerId);
        } catch (NoSuchEntityException $e) {
            $activeCustomer = $this->customerRepository->getById($customerId);
            $activeQuote = $this->quoteFactory->create();
            $activeQuote->setStoreId($storeId);
            $activeQuote->setCustomer($activeCustomer);
            $activeQuote->setCustomerIsGuest(0);
        }
        return $activeQuote;
    }
}
