<?php

namespace App\Service;

use App\Repository\ProductRepository;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CustomerRepository $customerRepository,
        private OrderRepository $orderRepository,
        private OrderItemRepository $orderItemRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getTotalProducts(): int
    {
        return $this->productRepository->count([]);
    }

    public function getTotalCustomers(): int
    {
        return $this->customerRepository->count([]);
    }

    public function getTotalOrders(): int
    {
        return $this->orderRepository->count([]);
    }

    public function getTotalRevenue(): float
    {
        $orders = $this->orderRepository->findAll();
        $total = 0.0;
        foreach ($orders as $order) {
            $total += $order->getTotal();
        }
        return $total;
    }

    public function getLowStockProducts(int $limit = 5): array
    {
        return $this->productRepository->createQueryBuilder('p')
            ->where('p.stock IS NOT NULL')
            ->orderBy('p.stock', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getMostSoldProducts(int $limit = 5): array
    {
        $qb = $this->orderItemRepository->createQueryBuilder('oi')
            ->select('p.id, p.label, SUM(oi.quantity) as totalQuantity')
            ->join('oi.product', 'p')
            ->groupBy('p.id, p.label')
            ->orderBy('totalQuantity', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getRecentOrders(int $limit = 5): array
    {
        return $this->orderRepository->createQueryBuilder('o')
            ->orderBy('o.orderDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getOrdersByStatus(): array
    {
        $qb = $this->orderRepository->createQueryBuilder('o')
            ->select('o.status, COUNT(o.id) as count')
            ->groupBy('o.status');

        $results = $qb->getQuery()->getResult();
        $statusCounts = [];
        foreach ($results as $result) {
            $statusCounts[$result['status']] = $result['count'];
        }
        return $statusCounts;
    }

    public function getAverageOrderValue(): float
    {
        $orders = $this->orderRepository->findAll();
        if (empty($orders)) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($orders as $order) {
            $total += $order->getTotal();
        }

        return $total / count($orders);
    }
}

